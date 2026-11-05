<?php

namespace Tests\Feature;

use App\Enums\AisGrade;
use App\Enums\AssessmentSource;
use App\Enums\NeurologicalLevel;
use App\Models\Assessment;
use App\Services\Legacy\MysqlDumpReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyImportTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): string
    {
        return base_path('tests/Fixtures/drupal-mini.sql');
    }

    public function test_dump_reader_parses_quoted_values_and_escapes(): void
    {
        $rows = iterator_to_array((new MysqlDumpReader($this->fixture()))->rows('webform_submitted_data'), false);

        $this->assertContains("Dr. O'Brien", array_column($rows, 4));
        $this->assertCount(3 * 27, $rows);
    }

    public function test_import_creates_anonymous_assessments_and_skips_invalid(): void
    {
        $this->artisan('legacy:import-drupal', ['dump' => $this->fixture()])->assertSuccessful();

        $a = Assessment::query()->with('result')->sole(); // #1 valid; #2 bad age; #3 bad answer; #4 draft
        $this->assertSame(AssessmentSource::Legacy, $a->source);
        $this->assertNull($a->user_id);
        $this->assertNull($a->patient_id);
        $this->assertSame('2012-01-01', $a->assessed_on->toDateString());
        $this->assertSame(40, $a->age);
        $this->assertSame(NeurologicalLevel::T4, $a->nli);   // "Th4"
        $this->assertSame(AisGrade::A, $a->ais);             // "a"
        $this->assertSame(50, $a->result->total);
        $this->assertSame('66.70', (string) $a->result->sciarmi_total);
        $this->assertSame('E', $a->result->grade);
    }

    public function test_personal_data_of_the_old_site_is_not_imported(): void
    {
        $this->artisan('legacy:import-drupal', ['dump' => $this->fixture()]);

        $everything = json_encode([
            DB::table('assessments')->get(),
            DB::table('assessment_results')->get(),
            DB::table('user_consents')->get(),
            DB::table('audit_logs')->get(),
            DB::table('users')->get(),
        ]);

        foreach (["O'Brien", 'doctor@hospital.example', '123456789', '10.0.0.1', 'fakehash', 'admin@example.org'] as $pii) {
            $this->assertStringNotContainsString($pii, $everything);
        }
        $this->assertSame(0, DB::table('users')->count());
    }

    public function test_import_is_idempotent_and_dry_run_writes_nothing(): void
    {
        $this->artisan('legacy:import-drupal', ['dump' => $this->fixture(), '--dry-run' => true])->assertSuccessful();
        $this->assertSame(0, Assessment::query()->count());

        $this->artisan('legacy:import-drupal', ['dump' => $this->fixture()]);
        $this->artisan('legacy:import-drupal', ['dump' => $this->fixture()]);
        $this->assertSame(1, Assessment::query()->count());
    }
}
