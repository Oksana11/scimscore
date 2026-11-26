<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Support\Security\AuditLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('admin:create {email} {name}')]
#[Description('Create a system administrator (signs in at /admin with email code + authenticator app)')]
class AdminCreate extends Command
{
    public function handle(AuditLogger $audit): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email.');

            return self::FAILURE;
        }

        if (Admin::query()->whereBlind('email', $email)->exists()) {
            $this->error('An administrator with this email already exists.');

            return self::FAILURE;
        }

        $admin = Admin::query()->create(['email' => $email, 'name' => (string) $this->argument('name')]);
        $audit->log('admin.created', $admin, actorType: 'system');

        $this->info('Administrator created. Sign in at '.url('/admin/login').' — the authenticator app is set up at first sign-in.');

        return self::SUCCESS;
    }
}
