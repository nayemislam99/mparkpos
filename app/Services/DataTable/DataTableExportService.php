<?php

namespace App\Services\DataTable;

use App\Services\DataTable\Export\CsvExporter;
use App\Services\DataTable\Export\PdfExporter;
use App\Services\DataTable\Export\SpreadsheetExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DataTableExportService
{
    public function __construct(
        private DataTableService $tables,
        private CsvExporter $csv,
        private SpreadsheetExporter $spreadsheet,
        private PdfExporter $pdf,
    ) {}

    public function download(
        Request $request,
        string $key,
        string $format,
        string $language,
        array $definition,
    ): Response {
        $columns = $this->exportColumns($definition);
        $headers = array_map(fn (string $key): string => $columns[$key]['title'], array_keys($columns));
        $rows = $this->tables->rowsForExport($request, $definition)
            ->map(fn (object $row): array => array_map(
                fn (string $key): string => $this->plainText((string) data_get($row, $key, '')),
                array_keys($columns),
            ))
            ->all();
        $baseName = preg_replace('/[^a-z0-9_-]+/i', '-', $key).'-'.now()->format('Ymd-His');

        return match (strtolower($format)) {
            'csv' => $this->csv->download($baseName.'.csv', $headers, $rows),
            'xlsx' => $this->spreadsheet->download($baseName.'.xlsx', $headers, $rows),
            'pdf' => $this->pdf->download($baseName.'.pdf', $headers, $rows, $this->title($key, $language)),
            default => throw new NotFoundHttpException('Unsupported export format.'),
        };
    }

    private function exportColumns(array $definition): array
    {
        $columns = $definition['columns'];
        $selected = $definition['export_columns'] ?? array_keys(array_diff_key($columns, [
            'actions' => true,
            'sn' => true,
            'DT_RowIndex' => true,
        ]));
        $titles = $definition['column_titles'] ?? [];
        $export = [];

        foreach ($selected as $key) {
            if (isset($columns[$key]) && ! in_array($key, ['actions', 'sn', 'DT_RowIndex'], true)) {
                $export[$key] = [
                    'title' => (string) ($titles[$key] ?? ucfirst(str_replace('_', ' ', $key))),
                ];
            }
        }

        return $export;
    }

    private function plainText(string $value): string
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function title(string $key, string $language): string
    {
        $title = ucfirst(str_replace(['_', '-'], ' ', $key));

        return $language === 'bn' ? $title.' — রিপোর্ট' : $title.' Report';
    }
}
