<?php

namespace App\Services\Export;

use App\Models\Assessment;
use App\Scoring\ScimDefinition;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PDF and Excel exports. Contents are minimised: no alias, no email, no internal ids;
 * the patient is identified by System ID and the user's own Research ID only.
 * Every text value is written as a typed string cell, so a value such as "=HYPERLINK(...)"
 * can never become a spreadsheet formula (formula injection).
 */
final class AssessmentExporter
{
    private const ITEM_KEYS = ['v1', 'v2', 'v3', 'v4', 'v5', 'v6', 'v7', 'v8', 'v9', 'v10', 'v11', 'v12', 'v13', 'v14', 'v15', 'v16', 'v17', 'v18', 'v19'];

    /**
     * @param  iterable<Assessment>  $assessments  with result and patient loaded
     */
    public function xlsx(iterable $assessments, string $filename, bool $withPatient = true): StreamedResponse
    {
        return response()->streamDownload(function () use ($assessments, $withPatient) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->getCurrentSheet()->setName('Assessments');

            $writer->addRow(Row::fromValuesWithStyle($this->header($withPatient), new Style(fontBold: true)));

            foreach ($assessments as $a) {
                $writer->addRow(new Row($this->cells($a, $withPatient)));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
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
    private function header(bool $withPatient): array
    {
        return array_merge(
            $withPatient ? ['System ID', 'Research ID'] : [],
            ['Assessment date', 'SCIM version', 'Type', 'Gender', 'Year of birth', 'Age', 'Neurological level', 'AIS', 'AMS'],
            array_map('strtoupper', self::ITEM_KEYS),
            ['Self-care', 'Respiration & sphincter', 'Mobility', 'SCIM total', 'SCI-ARMI', 'Grade', 'Algorithm'],
        );
    }

    /**
     * @return list<Cell>
     */
    private function cells(Assessment $a, bool $withPatient): array
    {
        $s = fn (?string $v) => new StringCell((string) $v);
        $n = fn (int|float|string|null $v) => $v === null || $v === '' ? new StringCell('') : new NumericCell(+$v);
        $r = $a->result;

        $cells = $withPatient ? [$s($a->patient?->system_id), $s($a->patient?->research_id)] : [];

        $cells = array_merge($cells, [
            $s($a->assessed_on->toDateString()),
            $s($a->scim_version->value),
            $s($a->patient_id === null ? 'Quick' : ($a->is_first ? 'First' : 'Follow-up')),
            $s($a->gender->label()),
            $n($a->patient?->birth_year),
            $n($a->age),
            $s($a->nli?->value),
            $s($a->ais?->value),
            $n($a->ams),
        ]);

        foreach (self::ITEM_KEYS as $k) {
            $cells[] = $n($a->answers[$k] ?? null);
        }

        return array_merge($cells, [
            $n($r->self_care),
            $n($r->respiration_sphincter),
            $n($r->mobility),
            $n($r->total),
            $n($r->sciarmi_total),
            $s($r->grade),
            $s($a->calc_version),
        ]);
    }
}
