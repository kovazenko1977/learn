<?php

declare(strict_types=1);

namespace App\Core;

use App\Auth\Session;

class Security
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function generateCsrfToken(): string
    {
        if (!$this->session->has('csrf_token')) {
            $token = bin2hex(random_bytes(32));
            $this->session->set('csrf_token', $token);
        }
        return $this->session->get('csrf_token');
    }

    public function validateCsrfToken(?string $token): bool
    {
        $storedToken = $this->session->get('csrf_token');
        return $token !== null && hash_equals($storedToken, $token);
    }
}
