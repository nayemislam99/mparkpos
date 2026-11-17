<?php

namespace App\Services\DataTable\Export;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;

class PdfExporter
{
    public function download(string $filename, array $headers, array $rows, string $title): Response
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('admin.datatable.export-pdf', compact('headers', 'rows', 'title'))->render(), 'UTF-8');
        $pdf->setPaper('A4', count($headers) > 8 ? 'landscape' : 'portrait');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
