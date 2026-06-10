<?php

declare(strict_types=1);

namespace App\Utils;

class UpdateManager
{
    private string $modulesPath;
    private string $pluginsPath;

    public function __construct(string $modulesPath, string $pluginsPath)
    {
        $this->modulesPath = $modulesPath;
        $this->pluginsPath = $pluginsPath;
    }

    public function installFromZip(string $zipPath, string $type = 'module'): bool
    {
        $targetPath = $type === 'module' ? $this->modulesPath : $this->pluginsPath;

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === TRUE) {
            $zip->extractTo($targetPath);
            $zip->close();
            return true;
        }
        return false;
    }
}
