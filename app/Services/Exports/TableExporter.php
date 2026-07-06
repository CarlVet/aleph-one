<?php

namespace App\Services\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a tabular dataset (a header row plus data rows) as a downloadable
 * CSV or XLSX file. CSV is written directly to the output stream for a low
 * memory footprint; XLSX is produced with PhpSpreadsheet (already required for
 * the import path) so no additional dependency is introduced.
 */
class TableExporter
{
    /** @var list<string> */
    public const FORMATS = ['csv', 'xlsx'];

    /**
     * @param  list<string>  $headers
     * @param  iterable<array<int, scalar|\Stringable|null>>  $rows
     */
    public static function download(string $basename, array $headers, iterable $rows, string $format = 'csv'): StreamedResponse
    {
        $format = in_array($format, self::FORMATS, true) ? $format : 'csv';

        return $format === 'xlsx'
            ? self::streamXlsx($basename, $headers, $rows)
            : self::streamCsv($basename, $headers, $rows);
    }

    /**
     * @param  list<string>  $headers
     * @param  iterable<array<int, scalar|\Stringable|null>>  $rows
     */
    private static function streamCsv(string $basename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, array_values((array) $row));
            }

            fclose($handle);
        }, "{$basename}.csv", ['Content-Type' => 'text/csv']);
    }

    /**
     * @param  list<string>  $headers
     * @param  iterable<array<int, scalar|\Stringable|null>>  $rows
     */
    private static function streamXlsx(string $basename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray($headers, null, 'A1');

            $rowIndex = 2;
            foreach ($rows as $row) {
                $sheet->fromArray(array_values((array) $row), null, 'A'.$rowIndex);
                $rowIndex++;
            }

            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, "{$basename}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
