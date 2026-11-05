<?php

namespace App\Services\Export;

use App\Models\Assessment;
use App\Scoring\ScimDefinition;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PDF, Excel and CSV exports. Contents are minimised per audience:
 *   MODE_USER  — the doctor's own data: System ID + their own Research ID;
 *   MODE_QUICK — anonymous quick assessment: no patient columns;
 *   MODE_ADMIN — research export: System ID, institution, country, profession; never Research ID or alias.
 * Text is always written as text: in Excel as typed string cells, in CSV with a leading
 * apostrophe for values starting with = + - @ (spreadsheet formula injection).
 */
final class AssessmentExporter
{
    public const MODE_USER = 'user';

    public const MODE_QUICK = 'quick';

    public const MODE_ADMIN = 'admin';

    private const ITEM_KEYS = ['v1', 'v2', 'v3', 'v4', 'v5', 'v6', 'v7', 'v8', 'v9', 'v10', 'v11', 'v12', 'v13', 'v14', 'v15', 'v16', 'v17', 'v18', 'v19'];

    /**
     * @param  iterable<Assessment>  $assessments  with result (+ patient; + user.profile relations for admin)
     */
    public function xlsx(iterable $assessments, string $filename, string $mode = self::MODE_USER): StreamedResponse
    {
        return response()->streamDownload(function () use ($assessments, $mode) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->getCurrentSheet()->setName('Assessments');
            $writer->addRow(Row::fromValuesWithStyle($this->header($mode), new Style(fontBold: true)));

            foreach ($assessments as $a) {
                $writer->addRow(new Row(array_map(
                    fn ($v) => is_int($v) || is_float($v) ? new NumericCell($v) : new StringCell((string) $v),
                    $this->values($a, $mode),
                )));
            }

            $writer->close();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * @param  iterable<Assessment>  $assessments
     */
    public function csv(iterable $assessments, string $filename, string $mode = self::MODE_ADMIN): StreamedResponse
    {
        return CsvWriter::download($filename, $this->header($mode), (function () use ($assessments, $mode) {
            foreach ($assessments as $a) {
                yield $this->values($a, $mode);
            }
        })());
    }

    public function pdf(Assessment $assessment, string $filename): Response
    {
        return Pdf::loadView('pdf.assessment', [
            'assessment' => $assessment,
            'definition' => ScimDefinition::for($assessment->scim_version),
        ])->setPaper('a4')->download($filename);
    }

    /**
     * @param  iterable<Assessment>  $assessments
     * @param  array<string, mixed>  $filters
     */
    public function pdfList(iterable $assessments, array $filters, string $filename): Response
    {
        return Pdf::loadView('pdf.assessments', ['assessments' => $assessments, 'filters' => $filters])
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    /**
     * @return list<string>
     */
    public function header(string $mode): array
    {
        return array_merge(
            match ($mode) {
                self::MODE_USER => ['System ID', 'Research ID'],
                self::MODE_ADMIN => ['System ID', 'Source', 'Institution', 'ROR ID', 'Country', 'Profession'],
                default => [],
            },
            ['Assessment date', 'SCIM version', 'Type', 'Gender', 'Year of birth', 'Age', 'Neurological level', 'AIS', 'AMS'],
            array_map('strtoupper', self::ITEM_KEYS),
            ['Self-care', 'Respiration & sphincter', 'Mobility', 'SCIM total', 'SCI-ARMI', 'Grade', 'Algorithm'],
        );
    }

    /**
     * @return list<int|float|string|null>
     */
    public function values(Assessment $a, string $mode): array
    {
        $r = $a->result;
        $profile = $mode === self::MODE_ADMIN ? $a->user?->profile : null;
        $institution = $mode === self::MODE_ADMIN ? ($a->patient?->institution ?? $profile?->institution) : null;

        $prefix = match ($mode) {
            self::MODE_USER => [$a->patient?->system_id, $a->patient?->research_id],
            self::MODE_ADMIN => [
                $a->patient?->system_id,
                $a->source->value,
                $institution?->name,
                $institution?->ror_id,
                $profile?->country_code,
                $profile?->profession?->name,
            ],
            default => [],
        };

        $items = array_map(fn ($k) => isset($a->answers[$k]) ? (int) $a->answers[$k] : null, self::ITEM_KEYS);

        return array_merge($prefix, [
            $a->assessed_on->toDateString(),
            $a->scim_version->value,
            $a->patient_id === null ? ucfirst($a->source->value) : ($a->is_first ? 'First' : 'Follow-up'),
            $a->gender->label(),
            $a->patient?->birth_year,
            $a->age,
            $a->nli?->value,
            $a->ais?->value,
            $a->ams,
        ], $items, [
            $r->self_care,
            $r->respiration_sphincter,
            $r->mobility,
            $r->total,
            $r->sciarmi_total === null ? null : (float) $r->sciarmi_total,
            $r->grade,
            $a->calc_version,
        ]);
    }
}
