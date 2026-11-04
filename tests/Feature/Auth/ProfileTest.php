<?php

namespace Tests\Feature\Auth;

use App\Mail\EmailChangedMail;
use App\Mail\OtpCodeMail;
use App\Models\Country;
use App\Models\Institution;
use App\Models\Profession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private Profession $profession;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Mail::fake();
        Country::query()->create(['code' => 'IL', 'name' => 'Israel']);
        $this->profession = Profession::query()->create(['name' => 'Physician', 'sort' => 1]);

        Http::fake([
            'api.ror.org/v2/organizations/04mhzgx49' => Http::response([
                'id' => 'https://ror.org/04mhzgx49',
                'names' => [['value' => 'Tel Aviv University', 'types' => ['ror_display', 'label']]],
                'locations' => [['geonames_details' => ['country_code' => 'IL', 'name' => 'Tel Aviv']]],
            ]),
            'api.ror.org/v2/organizations?*' => Http::response(['items' => [[
                'id' => 'https://ror.org/04mhzgx49',
                'names' => [['value' => 'Tel Aviv University', 'types' => ['ror_display']]],
                'locations' => [['geonames_details' => ['country_code' => 'IL', 'name' => 'Tel Aviv']]],
            ]]]),
            '*' => Http::response([], 404),
        ]);
    }

    /** @return array<string, mixed> */
    private function form(array $over = []): array
    {
        return $over + ['profession_id' => $this->profession->id, 'country_code' => 'IL'];
    }

    public function test_complete_profile_with_ror_institution(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile', $this->form(['institution' => 'ror:04mhzgx49']))->assertRedirect('/dashboard');

        $inst = $user->fresh()->profile->institution;
        $this->assertSame('04mhzgx49', $inst->ror_id);
        $this->assertSame('Tel Aviv University', $inst->name); // name from ROR, not from the browser
        $this->assertFalse($inst->is_custom);
    }

    public function test_complete_profile_with_custom_institution_reuses_same_name(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->actingAs($a)->put('/profile', $this->form(['institution' => 'custom', 'institution_custom_name' => 'Rehab  Center North']))->assertRedirect('/dashboard');
        $this->actingAs($b)->put('/profile', $this->form(['institution' => 'custom', 'institution_custom_name' => 'rehab center north']))->assertRedirect('/dashboard');

        $this->assertSame(1, Institution::query()->where('is_custom', true)->count());
        $this->assertSame($a->fresh()->profile->institution_id, $b->fresh()->profile->institution_id);
    }

    public function test_invalid_institution_reference_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile', $this->form(['institution' => 'ror:99xxxxxx']))->assertSessionHasErrors('institution');
        $this->actingAs($user)->put('/profile', $this->form(['institution' => 'local:999']))->assertSessionHasErrors('institution');
        $this->actingAs($user)->put('/profile', $this->form(['institution' => 'drop table']))->assertSessionHasErrors('institution');
        $this->assertNull($user->fresh()->profile);
    }

    public function test_country_must_come_from_the_list(): void
    {
        $this->actingAs(User::factory()->create())
            ->put('/profile', $this->form(['country_code' => 'XX', 'institution' => 'custom', 'institution_custom_name' => 'Somewhere']))
            ->assertSessionHasErrors('country_code');
    }

    public function test_institution_search_returns_local_and_ror_results(): void
    {
        Institution::query()->create(['name' => 'Tel Aviv Sourasky Medical Center', 'ror_id' => '04nd58p63', 'country_code' => 'IL']);

        $res = $this->actingAs(User::factory()->create())->getJson('/institutions/search?q=Tel Aviv')->assertOk();

        $this->assertSame('Tel Aviv Sourasky Medical Center', $res->json('local.0.name'));
        $this->assertSame('ror:04mhzgx49', $res->json('ror.0.ref'));
    }

    public function test_institution_search_requires_login(): void
    {
        $this->getJson('/institutions/search?q=Tel')->assertUnauthorized();
    }

    public function test_email_change_flow(): void
    {
        $user = User::factory()->withProfile()->create(['email' => 'old@clinic.org']);

        $this->actingAs($user)->post('/profile/email', ['new_email' => 'new@clinic.org'])->assertRedirect('/profile');

        $code = null;
        Mail::assertSent(OtpCodeMail::class, function (OtpCodeMail $m) use (&$code) {
            $code = $m->code;

            return $m->hasTo('new@clinic.org');
        });

        $this->post('/profile/email/confirm', ['code' => $code])->assertRedirect('/profile');

        $this->assertSame('new@clinic.org', $user->fresh()->email);
        Mail::assertSent(EmailChangedMail::class, fn ($m) => $m->hasTo('old@clinic.org'));
    }

    public function test_email_already_used_by_another_account_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@clinic.org']);
        $user = User::factory()->withProfile()->create();

        $this->actingAs($user)->post('/profile/email', ['new_email' => 'Taken@clinic.org'])->assertSessionHasErrors('new_email');
        Mail::assertNothingSent();
    }

    public function test_wrong_email_change_code_does_not_change_email(): void
    {
        $user = User::factory()->withProfile()->create(['email' => 'old@clinic.org']);
        $this->actingAs($user)->post('/profile/email', ['new_email' => 'new@clinic.org']);

        $this->post('/profile/email/confirm', ['code' => '000000x'])->assertSessionHasErrors('code');
        $this->assertSame('old@clinic.org', $user->fresh()->email);
    }
}
