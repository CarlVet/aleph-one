<?php

namespace App\Services\Imports;

use Illuminate\Http\UploadedFile;

class DelimitedTableReader
{
    /**
     * @return array{headers: list<string>, rows: list<list<string>>}
     */
    public function read(UploadedFile $file): array
    {
        $ext = strtolower((string) $file->getClientOriginalExtension());

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                return $this->readSpreadsheet($file);
            }

            throw new \RuntimeException('Only CSV files can be imported right now. In Excel: File → Save As → choose "CSV" from the file format dropdown, then upload the CSV.');
        }

        return $this->readCsvLike($file);
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string>>}
     */
    private function readCsvLike(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Unable to read uploaded file.');
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new \RuntimeException('Unable to open uploaded file.');
        }

        try {
            $firstLine = null;
            while (($line = fgets($handle)) !== false) {
                if (trim($line) !== '') {
                    $firstLine = $line;
                    break;
                }
            }
            if ($firstLine === null) {
                return ['headers' => [], 'rows' => []];
            }

            $delimiter = $this->detectDelimiter($firstLine);

            // Excel/LibreOffice sometimes exports a delimiter hint line like: "sep=;"
            $trimmed = trim($firstLine);
            if (preg_match('/^sep\s*=\s*(.)\s*$/i', $trimmed, $m)) {
                $delimiter = (string) $m[1];
                $headers = fgetcsv($handle, 0, $delimiter);
            } else {
                $headers = str_getcsv($firstLine, $delimiter);
            }

            if (! is_array($headers)) {
                return ['headers' => [], 'rows' => []];
            }

            $headers = array_values(array_map(fn ($h) => trim((string) $h), $headers));

            $rows = [];
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (! is_array($row)) {
                    continue;
                }

                $cells = array_values(array_map(fn ($c) => trim((string) $c), $row));

                // Skip completely empty rows
                $hasAny = false;
                foreach ($cells as $c) {
                    if ($c !== '') {
                        $hasAny = true;
                        break;
                    }
                }
                if (! $hasAny) {
                    continue;
                }

                $rows[] = $cells;
            }

            return ['headers' => $headers, 'rows' => $rows];
        } finally {
            fclose($handle);
        }
    }

    private function detectDelimiter(string $firstLine): string
    {
        $candidates = [',', ';', "\t", '|'];
        $best = ',';
        $bestCount = -1;

        foreach ($candidates as $d) {
            $count = substr_count($firstLine, $d);
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $d;
            }
        }

        return $best;
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string>>}
     */
    private function readSpreadsheet(UploadedFile $file): array
    {
        /** @var class-string $io */
        $io = 'PhpOffice\\PhpSpreadsheet\\IOFactory';
        /** @phpstan-ignore-next-line */
        $spreadsheet = $io::load($file->getRealPath());

        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);

        if (! is_array($data) || count($data) === 0) {
            return ['headers' => [], 'rows' => []];
        }

        $first = array_shift($data);
        $headers = array_values(array_map(fn ($v) => trim((string) $v), array_values($first)));

        $rows = [];
        foreach ($data as $row) {
            $cells = array_values(array_map(fn ($v) => trim((string) $v), array_values($row)));

            $hasAny = false;
            foreach ($cells as $c) {
                if ($c !== '') {
                    $hasAny = true;
                    break;
                }
            }
            if (! $hasAny) {
                continue;
            }

            $rows[] = $cells;
        }

        return ['headers' => $headers, 'rows' => $rows];
    }
}
