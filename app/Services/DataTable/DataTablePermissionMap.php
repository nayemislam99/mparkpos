<?php

namespace App\Services\DataTable;

use App\Models\User;

class DataTablePermissionMap
{
    public function allows(User $user, array $definition, string $ability = 'view'): bool
    {
        $permission = $definition['permissions'][$ability] ?? null;

        return is_string($permission) && $permission !== '' && $user->can($permission);
    }
}
