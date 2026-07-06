<?php

namespace Tests\Feature;

use App\Services\Imports\DelimitedTableReader;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SpreadsheetImportTest extends TestCase
{
    public function test_reads_an_xlsx_file_into_headers_and_rows(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'import_').'.xlsx';

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['Sample Code', 'Species'],
            ['A1B2-AS-1', 'Chicken'],
            ['A1B2-AS-2', 'Cattle'],
        ]);
        (new Xlsx($spreadsheet))->save($path);

        $file = new UploadedFile($path, 'samples.xlsx', null, null, true);

        $result = (new DelimitedTableReader)->read($file);

        @unlink($path);

        $this->assertSame(['Sample Code', 'Species'], $result['headers']);
        $this->assertSame([
            ['A1B2-AS-1', 'Chicken'],
            ['A1B2-AS-2', 'Cattle'],
        ], $result['rows']);
    }

    public function test_skips_fully_empty_rows_in_a_spreadsheet(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'import_').'.xlsx';

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['Sample Code', 'Species'],
            ['A1B2-AS-1', 'Chicken'],
            ['', ''],
            ['A1B2-AS-2', 'Cattle'],
        ]);
        (new Xlsx($spreadsheet))->save($path);

        $file = new UploadedFile($path, 'samples.xlsx', null, null, true);

        $result = (new DelimitedTableReader)->read($file);

        @unlink($path);

        $this->assertCount(2, $result['rows']);
    }
}
