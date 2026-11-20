<?php

namespace Tests\Feature\Security;

use App\Models\Admin;
use App\Models\Assessment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SyntheticDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_refused_without_flag_and_on_production_domain(): void
    {
        config(['scim.allow_synthetic_data' => false, 'app.url' => 'https://dev.scimscore.com']);
        $this->assertSame(1, Artisan::call('dev:synthetic-data', ['--force' => true]));

        config(['scim.allow_synthetic_data' => true, 'app.url' => 'https://scimscore.com']);
        $this->assertSame(1, Artisan::call('dev:synthetic-data', ['--force' => true]));
        $this->assertSame(0, Patient::query()->count());
    }

    public function test_wipes_real_data_and_creates_synthetic_data(): void
    {
        config(['scim.allow_synthetic_data' => true, 'app.url' => 'https://dev.scimscore.com']);
        $real = User::factory()->withProfile()->create(['email' => 'real.doctor@clinic.org']);
        Patient::factory()->for($real)->create();
        Admin::query()->create(['email' => 'boss@scim.org', 'name' => 'Boss']);

        $this->assertSame(0, Artisan::call('dev:synthetic-data', ['--wipe' => true, '--force' => true, '--users' => 2, '--patients' => 3, '--quick' => 4]), Artisan::output());

        $this->assertNull(User::query()->whereBlind('email', 'real.doctor@clinic.org')->first());
        $this->assertNotNull(User::query()->whereBlind('email', 'demo1@example.test')->first());
        $this->assertSame(6, Patient::query()->count());
        $this->assertGreaterThanOrEqual(6 + 4, Assessment::query()->count());
        $this->assertSame(1, Admin::query()->count(), 'admins are kept');

        // Running again without --wipe does not duplicate demo data.
        $this->assertSame(0, Artisan::call('dev:synthetic-data', ['--force' => true, '--users' => 2, '--patients' => 3, '--quick' => 0]));
        $this->assertSame(6, Patient::query()->count());

        $this->assertSame(0, Artisan::call('audit:verify'), Artisan::output());
    }
}
