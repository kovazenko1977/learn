<?php

class Permissions {
    public static function check($user, $permission, $storage) {
        if (!$user) return false;
        // Administrator always has full access
        if ($user['role'] === 'Administrator') return true;

        $groupId = $user['group_id'] ?? null;
        if (!$groupId) {
            return self::getDefaultRolePermission($user['role'], $permission);
        }

        $group = $storage->getById('groups', $groupId);
        if (!$group) {
            return self::getDefaultRolePermission($user['role'], $permission);
        }

        // Decode permissions if stored as JSON string in MySQL
        $perms = $group['permissions'] ?? [];
        if (is_string($perms)) {
            $perms = json_decode($perms, true) ?: [];
        }

        return !empty($perms[$permission]);
    }

    private static function getDefaultRolePermission($role, $permission) {
        $defaults = [
            'Administrator' => [
                'can_view_all_tasks' => true,
                'can_create_tasks' => true,
                'can_assign_executors' => true,
                'can_comment_tasks' => true,
                'can_edit_tasks' => true,
                'can_delete_tasks' => true,
                'can_access_chat' => true,
                'can_view_analytics' => true
            ],
            'Department Head' => [
                'can_view_all_tasks' => true,
                'can_create_tasks' => true,
                'can_assign_executors' => true,
                'can_comment_tasks' => true,
                'can_edit_tasks' => true,
                'can_delete_tasks' => false,
                'can_access_chat' => true,
                'can_view_analytics' => true
            ],
            'Responsible Employee' => [
                'can_view_all_tasks' => false,
                'can_create_tasks' => true,
                'can_assign_executors' => false,
                'can_comment_tasks' => true,
                'can_edit_tasks' => false,
                'can_delete_tasks' => false,
                'can_access_chat' => true,
                'can_view_analytics' => false
            ],
            'Executor' => [
                'can_view_all_tasks' => false,
                'can_create_tasks' => false,
                'can_assign_executors' => false,
                'can_comment_tasks' => true,
                'can_edit_tasks' => false,
                'can_delete_tasks' => false,
                'can_access_chat' => true,
                'can_view_analytics' => false
            ]
        ];
        return !empty($defaults[$role][$permission]);
    }
}
