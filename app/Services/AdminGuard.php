<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * Evita que el sistema se quede sin administrador.
 *
 * "Administrador" no es un nombre de rol: es cualquier usuario ACTIVO cuyo rol tenga
 * TODOS los permisos críticos (CRITICAL_PERMISSIONS). Antes de guardar un cambio se
 * simula cómo quedaría el sistema y se comprueba que siga existiendo al menos uno.
 */
class AdminGuard
{
    public const CRITICAL_PERMISSIONS = ['roles.ver', 'roles.editar', 'usuarios.ver', 'usuarios.editar'];

    public const MESSAGE = 'No se puede guardar: el sistema quedaría sin ningún usuario que pueda administrar roles y usuarios.';

    /**
     * ¿Se pueden guardar estos permisos en el rol sin dejar el sistema sin administrador?
     *
     * @param  array<int, string>  $permissions  Permisos que tendrá el rol tras el cambio.
     */
    public function allowsRolePermissions(Role $role, array $permissions): bool
    {
        $adminRoleIds = $this->adminRoleIds()->reject(fn ($id) => $id === $role->id);

        if ($this->isAdminPermissionSet($permissions)) {
            $adminRoleIds->push($role->id);
        }

        return $this->activeAdminExists($adminRoleIds);
    }

    /**
     * ¿Se puede guardar el rol y el estado del usuario sin dejar el sistema sin administrador?
     */
    public function allowsUserChange(User $user, string $roleName, bool $isActive): bool
    {
        $adminRoleIds = $this->adminRoleIds();
        $newRole = Role::where('name', $roleName)->first();

        // Tras el cambio, el propio usuario seguirá siendo administrador.
        if ($isActive && $newRole !== null && $adminRoleIds->contains($newRole->id)) {
            return true;
        }

        // Si no, debe quedar otro administrador activo.
        return $this->activeAdminExists($adminRoleIds, except: $user);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function isAdminPermissionSet(array $permissions): bool
    {
        return array_diff(self::CRITICAL_PERMISSIONS, $permissions) === [];
    }

    /**
     * Ids de los roles que hoy tienen todos los permisos críticos.
     *
     * @return Collection<int, int>
     */
    private function adminRoleIds(): Collection
    {
        return Role::query()
            ->whereHas(
                'permissions',
                fn ($query) => $query->whereIn('name', self::CRITICAL_PERMISSIONS),
                '>=',
                count(self::CRITICAL_PERMISSIONS),
            )
            ->pluck('id');
    }

    /**
     * @param  Collection<int, int>  $adminRoleIds
     */
    private function activeAdminExists(Collection $adminRoleIds, ?User $except = null): bool
    {
        if ($adminRoleIds->isEmpty()) {
            return false;
        }

        return User::query()
            ->where('is_active', true)
            ->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->whereHas('roles', fn ($query) => $query->whereKey($adminRoleIds->all()))
            ->exists();
    }
}
