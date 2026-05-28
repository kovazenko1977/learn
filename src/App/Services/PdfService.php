<?php
namespace App\Services;
use TCPDF;
class PdfService {
    private $pdf;
    public function generateLabel($width, $height, $elements, $unit = 'mm') {
        $this->pdf = new TCPDF('P', $unit, [$width, $height], true, 'UTF-8', false);
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->SetMargins(0, 0, 0);
        $this->pdf->SetAutoPageBreak(false, 0);
        $this->pdf->AddPage();
        foreach ($elements as $el) {
            $this->renderElement($el);
        }
        return $this->pdf->Output('', 'S');
    }
    private function renderElement($el) {
        $type = $el['type'] ?? 'text';
        switch ($type) {
            case 'text': $this->renderText($el); break;
            case 'barcode': $this->renderBarcode($el); break;
            case 'image':
            case 'sign': $this->renderImage($el); break;
        }
    }
    private function renderText($el) {
        $font = $el['fontFamily'] ?? 'dejavusans';
        $style = $el['fontStyle'] ?? '';
        $size = $el['fontSize'] ?? 10;
        $this->pdf->SetFont($font, $style, $size);
        $align = 'L';
        if (isset($el['align'])) { $align = strtoupper(substr($el['align'], 0, 1)); }
        $this->pdf->MultiCell($el['width'] ?? 0, $el['height'] ?? 0, $el['content'], 0, $align, false, 1, $el['x'], $el['y'], true, 0, false, true, $el['height'] ?? 0, 'M');
    }
    private function renderBarcode($el) {
        $style = ['position' => '', 'align' => 'C', 'stretch' => false, 'fitwidth' => true, 'cellfitalign' => '', 'border' => false, 'hpadding' => 'auto', 'vpadding' => 'auto', 'fgcolor' => [0,0,0], 'bgcolor' => false, 'text' => true, 'font' => 'helvetica', 'fontsize' => 8, 'stretchtext' => 4];
        $type = strtoupper($el['barcodeType'] ?? 'CODE128');
        if ($type === 'QR' || $type === 'DATAMATRIX') {
            $this->pdf->write2DBarcode($el['content'], $type === 'QR' ? 'QRCODE,L' : 'DATAMATRIX', $el['x'], $el['y'], $el['width'], $el['height'], $style, 'N');
        } else {
            $tcpdfType = $type === 'EAN13' ? 'EAN13' : 'C128';
            $this->pdf->write1DBarcode($el['content'], $tcpdfType, $el['x'], $el['y'], $el['width'], $el['height'], 0.4, $style, 'N');
        }
    }
    private function renderImage($el) {
        if (isset($el['src'])) {
            $src = $el['src'];
            if (strpos($src, '/assets') === 0) { $src = dirname(__DIR__, 2) . '/public' . $src; }
            if (file_exists($src)) { $this->pdf->Image($src, $el['x'], $el['y'], $el['width'], $el['height']); }
        }
    }
}
