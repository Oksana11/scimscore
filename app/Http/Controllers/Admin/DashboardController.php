<?php

namespace App\Http\Controllers\Admin;

use App\Models\Assessment;
use App\Models\Patient;
use App\Models\User;
use App\Services\Admin\AdminStatistics;
use App\Services\Export\CsvWriter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends AdminController
{
    public function index(Request $request, AdminStatistics $stats): View
    {
        $f = $this->filters($request);

        return view('admin.dashboard', [
            'filters' => $f,
            'totals' => [
                'Users' => User::query()->count(),
                'Active users' => User::query()->where('is_active', true)->count(),
                'Patients' => Patient::query()->count(),
                'Assessments' => Assessment::query()->count(),
            ],
            'breakdowns' => $stats->breakdowns($f),
        ]);
    }

    public function export(Request $request, AdminStatistics $stats, string $format): StreamedResponse|Response
    {
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 404);
        $f = $this->filters($request);
        $breakdowns = $stats->breakdowns($f);
        $name = 'scim-statistics-'.now()->format('Ymd-His').'.'.$format;
        $this->log('admin.export_statistics', context: ['format' => $format]);

        if ($format === 'pdf') {
            return Pdf::loadView('pdf.statistics', ['breakdowns' => $breakdowns, 'filters' => $f])->setPaper('a4')->download($name);
        }

        $rows = [];
        foreach ($breakdowns as $title => $items) {
            foreach ($items as $i) {
                $rows[] = [$title, $i['label'], $i['count']];
            }
        }

        if ($format === 'csv') {
            return CsvWriter::download($name, ['Breakdown', 'Value', 'Count'], $rows);
        }

        return response()->streamDownload(function () use ($rows) {
            $w = new Writer;
            $w->openToFile('php://output');
            $w->addRow(Row::fromValuesWithStyle(['Breakdown', 'Value', 'Count'], new Style(fontBold: true)));
            foreach ($rows as [$t, $l, $c]) {
                $w->addRow(new Row([new StringCell($t), new StringCell($l), new NumericCell($c)]));
            }
            $w->close();
        }, $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * @return array{date_from: ?string, date_to: ?string, period: string}
     */
    private function filters(Request $request): array
    {
        $v = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'period' => ['nullable', Rule::in(['daily', 'monthly', 'yearly'])],
        ]);

        return ['date_from' => $v['date_from'] ?? null, 'date_to' => $v['date_to'] ?? null, 'period' => $v['period'] ?? 'yearly'];
    }
}
