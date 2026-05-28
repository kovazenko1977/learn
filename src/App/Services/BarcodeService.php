<?php
namespace App\Services;

use Picqer\Barcode\BarcodeGeneratorSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class BarcodeService {
    private $generator;

    public function __construct() {
        $this->generator = new BarcodeGeneratorSVG();
    }

    public function generate($text, $type = 'CODE128') {
        if ($type === 'QR' || $type === 'DATAMATRIX') {
            $options = new QROptions([
                'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                'addQuietzone' => true,
            ]);
            $qr = new QRCode($options);
            return $qr->render($text);
        }

        $barcodeType = $this->mapType($type);
        try {
            $svg = $this->generator->getBarcode($text, $barcodeType);
            return preg_replace('/<\?xml.*?\?>/', '', $svg);
        } catch (\Exception $e) {
            return $this->generate($text, 'QR');
        }
    }

    private function mapType($type) {
        $map = [
            'EAN13' => $this->generator::TYPE_EAN_13,
            'CODE128' => $this->generator::TYPE_CODE_128,
        ];
        return $map[$type] ?? $this->generator::TYPE_CODE_128;
    }
}
