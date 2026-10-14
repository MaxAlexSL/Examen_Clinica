<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Catálogo de permisos: código => [nombre legible, módulo].
     */
    public const PERMISSIONS = [
        'usuarios.ver' => ['Ver usuarios', 'Usuarios'],
        'usuarios.crear' => ['Crear usuarios', 'Usuarios'],
        'usuarios.editar' => ['Editar usuarios', 'Usuarios'],
        'roles.ver' => ['Ver roles', 'Roles'],
        'roles.crear' => ['Crear roles', 'Roles'],
        'roles.editar' => ['Editar roles', 'Roles'],
        'pacientes.ver' => ['Ver pacientes', 'Pacientes'],
        'pacientes.crear' => ['Afiliar pacientes', 'Pacientes'],
        'pacientes.editar' => ['Editar pacientes', 'Pacientes'],
        'pacientes.desactivar' => ['Desactivar pacientes', 'Pacientes'],
        'productos.ver' => ['Ver productos', 'Productos'],
        'productos.crear' => ['Crear productos', 'Productos'],
        'productos.editar' => ['Editar productos', 'Productos'],
        'productos.desactivar' => ['Desactivar productos', 'Productos'],
        'ventas.crear' => ['Registrar ventas', 'Ventas'],
        'ventas.ver' => ['Ver ventas propias', 'Ventas'],
        'ventas.ver_todas' => ['Ver todas las ventas', 'Ventas'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name => [$displayName, $module]) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['display_name' => $displayName, 'module' => $module],
            );
        }
    }
}
