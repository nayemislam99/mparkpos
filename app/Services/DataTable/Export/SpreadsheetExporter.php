<?php

namespace App\Services\DataTable\Export;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpreadsheetExporter
{
    public function download(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();

            foreach ($headers as $column => $header) {
                $letter = $this->columnLetter($column + 1);
                $sheet->setCellValue($letter.'1', $header);
                $sheet->getStyle($letter.'1')->getFont()->setBold(true);
            }

            foreach ($rows as $rowIndex => $row) {
                foreach ($row as $column => $value) {
                    $sheet->setCellValueExplicit(
                        $this->columnLetter($column + 1).($rowIndex + 2),
                        (string) $value,
                        DataType::TYPE_STRING,
                    );
                }
            }

            foreach (array_keys($headers) as $column) {
                $sheet->getColumnDimension($this->columnLetter($column + 1))->setAutoSize(true);
            }

            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function columnLetter(int $number): string
    {
        $letter = '';

        while ($number > 0) {
            $number--;
            $letter = chr(65 + ($number % 26)).$letter;
            $number = intdiv($number, 26);
        }

        return $letter;
    }
}
