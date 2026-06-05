<?php

declare(strict_types=1);

namespace App\Module;

interface ModuleInterface
{
    public function getName(): string;
    public function boot(): void;
    public function install(): void;
    public function update(): void;
    public function enable(): void;
    public function disable(): void;
}
