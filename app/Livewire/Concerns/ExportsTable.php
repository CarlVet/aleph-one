<?php

namespace App\Livewire\Concerns;

use App\Services\Exports\TableExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gives a Livewire index component a single entry point to stream its records
 * as a CSV or XLSX download, keeping the per-component code limited to defining
 * the header row and the data rows.
 */
trait ExportsTable
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<array<int, scalar|\Stringable|null>>  $rows
     */
    protected function exportTable(string $basename, array $headers, iterable $rows, string $format = 'csv'): StreamedResponse
    {
        return TableExporter::download($basename, $headers, $rows, $format);
    }
}
