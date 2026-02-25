<?php

namespace Medical\Core\Managers;

use Medical\Core\JsonStore;

class SnapshotManager {
    private $dataDir;
    private $snapshotDir;

    public function __construct() {
        $this->dataDir = __DIR__ . '/../../data/';
        $this->snapshotDir = $this->dataDir . 'snapshots/';
        if (!file_exists($this->snapshotDir)) {
            mkdir($this->snapshotDir, 0777, true);
        }
    }

    public function createSnapshot($note = '') {
        $id = date('Ymd_His');
        $filename = "snapshot_{$id}.zip";
        $filepath = $this->snapshotDir . $filename;

        $zip = new \ZipArchive();
        if ($zip->open($filepath, \ZipArchive::CREATE) !== TRUE) {
            return false;
        }

        // Add all JSON files from data directory
        $files = glob($this->dataDir . '*.json');
        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }

        // Add a metadata file
        $metadata = [
            'id' => $id,
            'timestamp' => date('Y-m-d H:i:s'),
            'note' => $note,
            'user' => \Medical\Core\Auth::getUser()['name'] ?? 'System'
        ];
        $zip->addFromString('metadata.json', json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $zip->close();

        // Log the action
        (new LogManager())->log('Создан снимок системы (версия)', ['snapshot_id' => $id, 'note' => $note]);

        return $id;
    }

    public function listSnapshots() {
        $files = glob($this->snapshotDir . 'snapshot_*.zip');
        $snapshots = [];
        foreach ($files as $file) {
            $zip = new \ZipArchive();
            if ($zip->open($file) === TRUE) {
                $metaContent = $zip->getFromName('metadata.json');
                if ($metaContent) {
                    $meta = json_decode($metaContent, true);
                    $meta['filename'] = basename($file);
                    $snapshots[] = $meta;
                }
                $zip->close();
            }
        }
        // Sort by timestamp desc
        usort($snapshots, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });
        return $snapshots;
    }

    public function restoreFromSnapshot($id) {
        $filename = "snapshot_{$id}.zip";
        $filepath = $this->snapshotDir . $filename;

        if (!file_exists($filepath)) return false;

        $zip = new \ZipArchive();
        if ($zip->open($filepath) === TRUE) {
            // Extract all except metadata.json
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat['name'] !== 'metadata.json') {
                    $zip->extractTo($this->dataDir, $stat['name']);
                }
            }
            $zip->close();
            (new LogManager())->log('Система откачена к версии', ['snapshot_id' => $id]);
            return true;
        }
        return false;
    }

    public function deleteSnapshot($id) {
        $filename = "snapshot_{$id}.zip";
        $filepath = $this->snapshotDir . $filename;
        if (file_exists($filepath)) {
            unlink($filepath);
            return true;
        }
        return false;
    }
}
