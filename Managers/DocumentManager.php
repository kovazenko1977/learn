<?php
namespace Managers;

class DocumentManager {
    public function generate($type, $orderId) {
        $filename = "{$type}_{$orderId}.pdf";
        // Mock PDF generation
        return [
            'success' => true,
            'type' => $type,
            'order_id' => $orderId,
            'file_url' => "Uploads/{$filename}",
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
}
