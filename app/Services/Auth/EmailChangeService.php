<?php

namespace App\Services\Auth;

use App\Enums\OtpPurpose;
use App\Mail\EmailChangedMail;
use App\Mail\OtpCodeMail;
use App\Models\EmailChangeRequest;
use App\Models\User;
use App\Support\Security\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Email change: OTP goes to the NEW address; after confirmation the old
 * address receives a notice. Patients and assessments stay with the account.
 */
final class EmailChangeService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function isTaken(string $email): bool
    {
        return User::query()->whereBlind('email', $email)->exists();
    }

    public function start(User $user, string $newEmail): void
    {
        $code = OtpCode::generate();

        DB::transaction(function () use ($user, $newEmail, $code): void {
            EmailChangeRequest::query()
                ->where('user_id', $user->id)
                ->whereNull('confirmed_at')
                ->where('expires_at', '>', now())
                ->update(['expires_at' => now()]);

            $request = new EmailChangeRequest([
                'user_id' => $user->id,
                'new_email' => $newEmail,
                'expires_at' => now()->addMinutes((int) config('scim.otp.ttl_minutes')),
            ]);
            $request->code_hash = OtpCode::hash($code, $this->scope($user, $request->blindIndexFor('new_email', $newEmail)));
            $request->save();
        });

        Mail::to($newEmail)->send(new OtpCodeMail($code, OtpPurpose::EmailChange));
        $this->audit->log('profile.email_change_requested', $user);
    }

    public function pending(User $user): ?EmailChangeRequest
    {
        return EmailChangeRequest::query()
            ->where('user_id', $user->id)
            ->whereNull('confirmed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    /**
     * @return 'ok'|'invalid'|'taken'
     */
    public function confirm(User $user, string $code): string
    {
        $result = DB::transaction(function () use ($user, $code): array {
            $request = EmailChangeRequest::query()
                ->where('user_id', $user->id)
                ->whereNull('confirmed_at')
                ->where('expires_at', '>', now())
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $request) {
                return ['invalid', null];
            }

            $request->attempts++;

            if (! OtpCode::matches($code, $this->scope($user, $request->new_email_bidx), $request->code_hash)) {
                if ($request->attempts >= (int) config('scim.otp.max_attempts')) {
                    $request->expires_at = now();
                }
                $request->save();

                return ['invalid', null];
            }

            if ($this->isTaken($request->new_email)) {
                $request->expires_at = now();
                $request->save();

                return ['taken', null];
            }

            $oldEmail = $user->email;
            $user->email = $request->new_email;
            $user->email_verified_at = now();
            $user->save();

            $request->confirmed_at = now();
            $request->save();

            return ['ok', $oldEmail];
        });

        [$status, $oldEmail] = $result;

        if ($status === 'ok') {
            Mail::to($oldEmail)->send(new EmailChangedMail);
            $this->audit->log('profile.email_changed', $user);
        }

        return $status;
    }

    private function scope(User $user, string $newEmailBidx): string
    {
        return OtpPurpose::EmailChange->value.'|'.$user->id.'|'.$newEmailBidx;
    }
}
