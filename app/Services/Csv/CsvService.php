<?php

namespace App\Services\Csv;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvService
{
    /**
     * Stream a CSV file download with UTF-8 BOM so Excel opens properly.
     *
     * @param string $filename
     * @param array<int, string> $headers
     * @param iterable<int, array<int, mixed>> $rows
     * @return StreamedResponse
     */
    public function export(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // Write UTF-8 BOM for Microsoft Excel / Unicode compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Write Header row
            fputcsv($handle, $headers);

            // Write Data rows
            foreach ($rows as $row) {
                fputcsv($handle, (array) $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Parse an uploaded CSV file into associative array rows.
     *
     * @param UploadedFile $file
     * @return array<int, array<string, string>>
     */
    public function parse(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (!$path || !file_exists($path)) {
            return [];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        // Check for and skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Read headers
        $headerRow = fgetcsv($handle);
        if ($headerRow === false || empty($headerRow)) {
            fclose($handle);
            return [];
        }

        $headers = array_map(function ($h) {
            return strtolower(trim((string) $h));
        }, $headerRow);

        $records = [];
        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (empty(array_filter($row, fn ($val) => trim((string) $val) !== ''))) {
                continue;
            }

            $record = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $record[$header] = isset($row[$index]) ? trim((string) $row[$index]) : '';
                }
            }

            $records[] = $record;
        }

        fclose($handle);

        return $records;
    }
}
