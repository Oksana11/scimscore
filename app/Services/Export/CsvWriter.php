<?php

namespace App\Services\Export;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class CsvWriter
{
    /**
     * @param  list<string>  $header
     * @param  iterable<list<int|float|string|null>>  $rows
     */
    public static function download(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, $header, escape: '');
            foreach ($rows as $row) {
                fputcsv($out, array_map([self::class, 'safe'], $row), escape: '');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Neutralises spreadsheet formulas in text values (CSV injection).
     */
    public static function safe(int|float|string|null $value): int|float|string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
