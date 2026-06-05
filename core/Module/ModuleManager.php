<?php

declare(strict_types=1);

namespace App\Module;

use App\Core\Container;

class ModuleManager
{
    private Container $container;
    private string $modulesPath;
    private array $modules = [];

    public function __construct(Container $container, string $modulesPath)
    {
        $this->container = $container;
        $this->modulesPath = $modulesPath;
    }

    public function loadModules(): void
    {
        if (!is_dir($this->modulesPath)) {
            return;
        }

        $dirs = scandir($this->modulesPath);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;

            $moduleDir = $this->modulesPath . '/' . $dir;
            $manifestPath = $moduleDir . '/manifest.json';

            if (is_dir($moduleDir) && file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                $moduleClass = $manifest['class'] ?? "App\\Modules\\$dir\\Module";

                if (class_exists($moduleClass)) {
                    $module = new $moduleClass($this->container, $manifest);
                    $module->boot();
                    $this->modules[$dir] = $module;
                }
            }
        }
    }

    public function getModules(): array
    {
        return $this->modules;
    }
}
