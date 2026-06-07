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

    public function url(string $path): string
    {
        $basePath = $this->globals['basePath'] ?? '';
        return $basePath . '/' . ltrim($path, '/');
    }

    public function render(string $template, array $data = []): string
    {
        $data = array_merge($this->globals, $data);
        extract($data);

        // Determine if it's an AJAX request
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

        // If it's a module view and NOT an AJAX request, we should probably wrap it in layout
        // But the current modules call render('layout', ['content' => ...])
        // This is a bit backwards if we want to support both.

        // Let's redefine: modules call render('module_template', $data)
        // Renderer wraps it in layout if not AJAX.

        $templatePath = $this->findTemplate($template);

        ob_start();
        include $templatePath;
        $content = ob_get_clean();

        if ($isAjax || $template === 'layout' || $template === 'login') {
            return $content;
        }

        // Wrap in layout
        return $this->render('layout', array_merge($data, ['content' => $content]));
    }

    private function findTemplate(string $template): string
    {
        // Check core templates
        $path = $this->viewsPath . $template . '.php';
        if (file_exists($path)) return $path;

        // Check module templates (template name like "Accommodation/index")
        $parts = explode('/', $template);
        if (count($parts) === 2) {
            $path = __DIR__ . '/../../modules/' . $parts[0] . '/views/' . $parts[1] . '.php';
            if (file_exists($path)) return $path;
        }

        throw new \Exception("Template not found: $template");
    }
}
