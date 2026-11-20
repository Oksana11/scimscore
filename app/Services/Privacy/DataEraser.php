<?php

namespace App\Services\Privacy;

use App\Models\Assessment;
use App\Models\EmailChangeRequest;
use App\Models\Patient;
use App\Models\SupportMessage;
use App\Models\User;
use App\Support\Security\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Right to erasure.
 *  - Account: the person is removed (email, profile, pending changes, sessions, support
 *    messages); their patients and assessments stay as anonymous research data with
 *    Research ID and alias wiped, so nothing links them to a person any more.
 *  - Patient: deleted completely with all assessments.
 */
class DataEraser
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function eraseAccount(User $user, string $by): void
    {
        DB::transaction(function () use ($user, $by) {
            $patients = Patient::query()->where('user_id', $user->id)->count();

            Patient::query()->where('user_id', $user->id)->update([
                'research_id' => null, 'research_id_bidx' => null,
                'alias' => null, 'alias_bidx' => null,
            ]);
            $user->profile()->delete();
            EmailChangeRequest::query()->where('user_id', $user->id)->delete();
            SupportMessage::query()->where('user_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();

            // Tombstone instead of a hard delete: research rows keep their (now anonymous) owner id.
            $user->forceFill([
                'email' => "deleted+{$user->public_id}@deleted.invalid",
                'is_active' => false,
                'deactivated_at' => $user->deactivated_at ?? now(),
                'anonymized_at' => now(),
                'last_login_at' => null,
            ])->save();

            $this->audit->log('privacy.account_erased', $user, ['by' => $by, 'patients_anonymized' => $patients]);
        });
    }

    public function deletePatient(Patient $patient, string $by): void
    {
        DB::transaction(function () use ($patient, $by) {
            $ids = Assessment::query()->where('patient_id', $patient->id)->pluck('id');
            DB::table('assessment_results')->whereIn('assessment_id', $ids)->delete();
            Assessment::query()->whereIn('id', $ids)->delete();

            $this->audit->log('privacy.patient_deleted', $patient, ['by' => $by, 'assessments' => $ids->count()]);
            $patient->delete();
        });
    }
}
