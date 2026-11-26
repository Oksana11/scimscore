<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Support\Security\AuditLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('admin:reset-mfa {email}')]
#[Description('Reset an administrator\'s authenticator app (lost phone); re-enrolled at next sign-in')]
class AdminResetMfa extends Command
{
    public function handle(AuditLogger $audit): int
    {
        $admin = Admin::query()->whereBlind('email', (string) $this->argument('email'))->first();

        if (! $admin) {
            $this->error('Administrator not found.');

            return self::FAILURE;
        }

        $admin->forceFill(['totp_secret' => null, 'totp_confirmed_at' => null, 'totp_last_step' => null])->save();
        $audit->log('admin.mfa_reset', $admin, actorType: 'system');
        $this->info('Authenticator reset.');

        return self::SUCCESS;
    }
}
