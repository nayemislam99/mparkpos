<?php

namespace App\Services\DataTable\Export;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    public function download(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headers);

            foreach ($rows as $row) {
                fputcsv($stream, array_map(function (string $value): string {
                    return preg_match('/^[\s]*[=+\-@]/', $value) ? "'".$value : $value;
                }, $row));
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
