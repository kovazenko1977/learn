<?php

declare(strict_types=1);

namespace App\View;

class Renderer
{
    private string $viewsPath;
    private array $globals = [];

    public function __construct(string $viewsPath)
    {
        $this->viewsPath = rtrim($viewsPath, '/') . '/';
    }

    public function setGlobal(string $key, mixed $value): void
    {
        $this->globals[$key] = $value;
    }

    public function render(string $template, array $data = []): string
    {
        $data = array_merge($this->globals, $data);
        extract($data);

        $templatePath = $this->viewsPath . $template . '.php';
        if (!file_exists($templatePath)) {
            // Fallback for modules? For now just throw error
            throw new \Exception("Template not found: $templatePath");
        }

        ob_start();
        include $templatePath;
        return ob_get_clean();
    }
}
