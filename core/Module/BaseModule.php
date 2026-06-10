<?php

declare(strict_types=1);

namespace App\Module;

use App\Core\Container;

abstract class BaseModule implements ModuleInterface
{
    protected Container $container;
    protected array $manifest;

    public function __construct(Container $container, array $manifest)
    {
        $this->container = $container;
        $this->manifest = $manifest;
    }

    public function getName(): string
    {
        return $this->manifest['name'] ?? static::class;
    }

    public function boot(): void
    {
        // Default boot logic
    }

    public function install(): void {}
    public function update(): void {}
    public function enable(): void {}
    public function disable(): void {}
}
