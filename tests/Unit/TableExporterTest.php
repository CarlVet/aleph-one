<?php

namespace Tests\Unit;

use App\Services\Exports\TableExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class TableExporterTest extends TestCase
{
    private function capture(StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return ob_get_clean();
    }

    public function test_csv_download_contains_header_and_rows(): void
    {
        $response = TableExporter::download('animals', ['Code', 'Name'], [
            ['A1', 'Zebra'],
            ['A2', 'Lion, male'],
        ], 'csv');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('animals.csv', (string) $response->headers->get('Content-Disposition'));

        $content = $this->capture($response);
        $this->assertStringContainsString('Code,Name', $content);
        $this->assertStringContainsString('A1,Zebra', $content);
        // A value containing the delimiter must be quoted.
        $this->assertStringContainsString('"Lion, male"', $content);
    }

    public function test_csv_normalizes_associative_rows_to_values(): void
    {
        $response = TableExporter::download('x', ['A', 'B'], [
            ['first' => '1', 'second' => '2'],
        ], 'csv');

        $this->assertStringContainsString('1,2', $this->capture($response));
    }

    public function test_xlsx_download_is_a_valid_office_open_xml_package(): void
    {
        $response = TableExporter::download('animals', ['Code', 'Name'], [
            ['A1', 'Zebra'],
        ], 'xlsx');

        $this->assertStringContainsString('animals.xlsx', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );

        $content = $this->capture($response);
        // XLSX is a ZIP container: it must start with the PK signature.
        $this->assertStringStartsWith("PK\x03\x04", $content);
        $this->assertGreaterThan(200, strlen($content));
    }

    public function test_unknown_format_falls_back_to_csv(): void
    {
        $response = TableExporter::download('report', ['A'], [['1']], 'json');

        $this->assertSame('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('report.csv', (string) $response->headers->get('Content-Disposition'));
    }
}
