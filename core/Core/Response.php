<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    private int $statusCode = 200;
    private string $content = '';
    private array $headers = [];

    public function setStatusCode(int $code): void
    {
        $this->statusCode = $code;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    public function json(mixed $data): void
    {
        $this->setHeader('Content-Type', 'application/json');
        $this->setContent(json_encode($data));
        $this->send();
    }

    public function redirect(string $url): void
    {
        // Специальная обработка для AJAX-запросов из окон
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            $this->setHeader('X-Redirect', $url);
            $this->send();
            return;
        }

        header('Location: ' . $url);
        exit;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        echo $this->content;
        exit;
    }
}
