<?php

declare(strict_types=1);

namespace App\Auth;

class RBAC
{
    private array $roles = [
        'admin' => ['*'],
        'manager' => ['view_dashboard', 'manage_guests', 'manage_rooms', 'manage_bookings'],
        'receptionist' => ['view_dashboard', 'manage_guests', 'manage_bookings'],
        'doctor' => ['view_dashboard', 'manage_medical'],
        'user' => ['view_dashboard'],
    ];

    public function can(string $role, string $permission): bool
    {
        if (!isset($this->roles[$role])) {
            return false;
        }

        if (in_array('*', $this->roles[$role])) {
            return true;
        }

        return in_array($permission, $this->roles[$role]);
    }

    public function hasRole(string $currentRole, string $requiredRole): bool
    {
        if ($currentRole === 'admin') {
            return true;
        }

        return $currentRole === $requiredRole;
    }
}
