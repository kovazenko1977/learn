<?php
namespace App\Services;

use TCPDF;

class PDFService {
    public function generateLabel($template, $product) {
        // template dimensions are in mm
        $pdf = new TCPDF('P', 'mm', [$template['width'], $template['height']], true, 'UTF-8', false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $importService = new ImportService();

        foreach ($template['elements'] as $el) {
            $x = $el['x'];
            $y = $el['y'];
            $w = $el['width'];
            $h = $el['height'];

            if ($el['type'] === 'text') {
                $content = $importService->substitute($el['content'], $product);
                $fontSize = $el['fontSize'] ?? 10;
                $pdf->SetFont('dejavusans', '', $fontSize);
                // Simple color handling
                if (isset($el['fill']) && preg_match('/#([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2})/i', $el['fill'], $m)) {
                    $pdf->SetTextColor(hexdec($m[1]), hexdec($m[2]), hexdec($m[3]));
                } else {
                    $pdf->SetTextColor(0, 0, 0);
                }
                $pdf->Text($x, $y, $content);
            } elseif ($el['type'] === 'rect') {
                $style = 'D'; // Default: Draw border
                if (isset($el['fill']) && $el['fill'] !== 'transparent') {
                    $style = 'FD'; // Fill and Draw
                    if (preg_match('/#([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2})/i', $el['fill'], $m)) {
                        $pdf->SetFillColor(hexdec($m[1]), hexdec($m[2]), hexdec($m[3]));
                    }
                }
                $pdf->Rect($x, $y, $w, $h, $style);
            } elseif ($el['type'] === 'line') {
                $pdf->Line($x, $y, $x + $w, $y);
            } elseif ($el['type'] === 'barcode') {
                $content = $importService->substitute($el['content'], $product);
                $style = [
                    'border' => false,
                    'padding' => 0,
                    'fgcolor' => [0, 0, 0],
                    'bgcolor' => false,
                ];
                $btype = $el['barcodeType'] ?? 'CODE128';

                if ($btype === 'QR' || $btype === 'DATAMATRIX') {
                    $pdf->write2DBarcode($content, $btype, $x, $y, $w, $h, $style, 'N');
                } else {
                    $tcpdf_type = ($btype === 'EAN13') ? 'EAN13' : 'C128';
                    $pdf->write1DBarcode($content, $tcpdf_type, $x, $y, $w, $h, 0.4, $style, 'N');
                }
            } elseif ($el['type'] === 'sign') {
                $svgFile = __DIR__ . '/../../../public/assets/signs/' . $el['content'] . '.svg';
                if (file_exists($svgFile)) {
                    $pdf->ImageSVG($svgFile, $x, $y, $w, $h);
                }
            }
        }

        return $pdf->Output('', 'S');
    }
}
