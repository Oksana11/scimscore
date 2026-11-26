<?php

namespace App\Services\Auth;

use App\Enums\OtpPurpose;
use App\Mail\OtpCodeMail;
use App\Models\OtpRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Issues and verifies email one-time codes.
 *  - only HMACs of the email and the code are stored;
 *  - a code is single use, expires after config('scim.otp.ttl_minutes');
 *  - after config('scim.otp.max_attempts') wrong entries the code is burned;
 *  - issuing a new code invalidates the previous one.
 */
final class OtpService
{
    public function send(OtpPurpose $purpose, string $email): void
    {
        $bidx = $this->emailIndex($email);
        $code = OtpCode::generate();

        DB::transaction(function () use ($purpose, $bidx, $code): void {
            OtpRequest::query()
                ->where('email_bidx', $bidx)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            OtpRequest::query()->create([
                'purpose' => $purpose,
                'email_bidx' => $bidx,
                'code_hash' => OtpCode::hash($code, $purpose->value.'|'.$bidx),
                'expires_at' => now()->addMinutes((int) config('scim.otp.ttl_minutes')),
            ]);
        });

        Mail::to($email)->send(new OtpCodeMail($code, $purpose));
    }

    public function verify(OtpPurpose $purpose, string $email, string $code): bool
    {
        $bidx = $this->emailIndex($email);

        return DB::transaction(function () use ($purpose, $bidx, $code): bool {
            $otp = OtpRequest::query()
                ->where('email_bidx', $bidx)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $otp) {
                return false;
            }

            $otp->attempts++;

            if (OtpCode::matches($code, $purpose->value.'|'.$bidx, $otp->code_hash)) {
                $otp->consumed_at = now();
                $otp->save();

                return true;
            }

            if ($otp->attempts >= (int) config('scim.otp.max_attempts')) {
                $otp->consumed_at = now(); // burned
            }
            $otp->save();

            return false;
        });
    }

    private function emailIndex(string $email): string
    {
        return (string) (new User)->blindIndexFor('email', $email);
    }
}
