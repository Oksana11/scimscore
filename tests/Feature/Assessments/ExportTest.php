<?php

namespace Tests\Feature\Assessments;

use App\Models\Assessment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->user = User::factory()->withProfile()->create();
    }

    private function assessment(Patient $patient, array $attrs, array $result): Assessment
    {
        $a = Assessment::factory()->forPatient($patient)->create($attrs);
        $a->result()->create($result + ['self_care' => 0, 'respiration_sphincter' => 0, 'mobility' => 0]);

        return $a;
    }

    /** Returns the XML of the first worksheet. */
    private function xlsxSheet(string $binary): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $binary);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'Not a valid XLSX (zip) file');
        $xml = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($path);

        return $xml;
    }

    public function test_filters_limit_results_to_matching_own_assessments(): void
    {
        $p = Patient::factory()->for($this->user)->create(['research_id' => 'R1']);
        $this->assessment($p, ['assessed_on' => '2025-01-10', 'is_first' => true], ['total' => 20, 'grade' => 'C']);
        $this->assessment($p, ['assessed_on' => '2025-05-10'], ['total' => 70, 'grade' => 'F']);
        $this->assessment(Patient::factory()->create(['research_id' => 'OTHER']), [], ['total' => 70]);

        $this->actingAs($this->user);

        $this->get('/assessments')->assertOk()->assertSee('2 assessments');
        $this->get('/assessments?total_from=50')->assertSee('1 assessments')->assertSee('10 May 2025')->assertDontSee('10 Jan 2025');
        $this->get('/assessments?type=first')->assertSee('1 assessments')->assertSee('10 Jan 2025');
        $this->get('/assessments?grade_from=D&grade_to=H')->assertSee('1 assessments');
        $this->get('/assessments?date_from=2025-02-01&date_to=2025-12-31')->assertSee('1 assessments');
        $this->get('/assessments?total_from=80')->assertSee('No assessments match the filters.');
    }

    public function test_invalid_filter_is_rejected(): void
    {
        $this->actingAs($this->user)->from('/assessments')->get('/assessments?total_from=500')->assertSessionHasErrors('total_from');
    }

    public function test_excel_export_contains_only_own_filtered_rows_and_no_formulas(): void
    {
        $p = Patient::factory()->for($this->user)->create(['research_id' => '=HYPERLINK("http://x")', 'alias' => 'Secret Alias']);
        $this->assessment($p, ['answers' => ['v1' => 3]], ['total' => 55, 'grade' => 'E']);
        $this->assessment(Patient::factory()->create(['research_id' => 'OTHERS']), [], ['total' => 55]);

        $res = $this->actingAs($this->user)->get('/assessments/export/xlsx?total_from=50');
        $res->assertOk();
        $this->assertStringContainsString('spreadsheetml', (string) $res->headers->get('Content-Type'));

        $xml = $this->xlsxSheet($res->streamedContent());
        $this->assertStringContainsString($p->system_id, $xml);
        $this->assertStringNotContainsString('OTHERS', $xml);
        $this->assertStringNotContainsString('Secret Alias', $xml); // alias is not exported
        $this->assertStringNotContainsString('<f>', $xml);           // no formula cells
    }

    public function test_pdf_exports(): void
    {
        $p = Patient::factory()->for($this->user)->create();
        $a = $this->assessment($p, [], ['total' => 40]);

        $this->actingAs($this->user);

        $list = $this->get('/assessments/export/pdf');
        $list->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $list->getContent());

        $one = $this->get("/assessments/{$a->public_id}/pdf");
        $one->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $one->getContent());

        $this->get("/assessments/{$a->public_id}/xlsx")->assertOk();
    }

    public function test_exports_of_other_users_assessments_answer_404(): void
    {
        $theirs = $this->assessment(Patient::factory()->create(), [], ['total' => 10]);

        $this->actingAs($this->user);
        $this->get("/assessments/{$theirs->public_id}/pdf")->assertNotFound();
        $this->get("/assessments/{$theirs->public_id}/xlsx")->assertNotFound();
    }

    public function test_quick_assessment_exports_only_in_creating_session(): void
    {
        $answers = array_combine(array_map(fn ($i) => 'v'.$i, range(1, 19)), array_fill(0, 19, 0));
        $this->post('/quick', [
            'scim_version' => 'III', 'assessed_on' => now()->toDateString(), 'gender' => 2, 'age' => 30,
            'nli' => 'C5', 'ais' => 'B', 'answers' => $answers, 'accept_terms' => '1',
        ])->assertRedirect();
        $id = Assessment::query()->sole()->public_id;

        $this->get("/quick/{$id}/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get("/quick/{$id}/xlsx")->assertOk();

        $this->flushSession();
        $this->get("/quick/{$id}/pdf")->assertNotFound();
    }

    public function test_exports_are_audited(): void
    {
        $this->actingAs($this->user)->get('/assessments/export/xlsx?version=III')->assertOk();

        $this->assertDatabaseHas('audit_logs', ['action' => 'export.xlsx', 'actor_id' => $this->user->id]);
    }
}
