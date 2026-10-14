<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $matrix = [
            'Administrador' => array_keys(PermissionSeeder::PERMISSIONS),
            'Admisión' => [
                'pacientes.ver',
                'pacientes.crear',
                'pacientes.editar',
                'pacientes.desactivar',
            ],
            'Vendedor' => [
                'pacientes.ver',
                'productos.ver',
                'ventas.crear',
                'ventas.ver',
            ],
        ];

        foreach ($matrix as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }
    }
}
