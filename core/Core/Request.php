<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    private string $basePath = '';

    public function __construct()
    {
        $this->detectBasePath();
    }

    private function detectBasePath(): void
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $this->basePath = str_replace('\\', '/', dirname($scriptName));
        if ($this->basePath === '/') {
            $this->basePath = '';
        }
    }

    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function getUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Remove query string
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        $uri = rawurldecode($uri);

        // Strip base path
        if ($this->basePath !== '' && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }

        return $uri === '' ? '/' : $uri;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getBody(): array
    {
        if ($this->getMethod() === 'GET') {
            return $_GET;
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }

        return $_POST;
    }
}
