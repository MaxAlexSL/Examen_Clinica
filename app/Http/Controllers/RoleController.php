<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Gestión de roles y su matriz de permisos. Los permisos se validan en las rutas.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get();

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('roles.create', [
            'role' => new Role(),
            'permissionsByModule' => $this->permissionsByModule(),
            'selected' => [],
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);

            // syncPermissions limpia automáticamente la caché de permisos de Spatie.
            $role->syncPermissions($data['permissions'] ?? []);
        });

        return redirect()->route('roles.index')->with('status', 'Rol creado correctamente.');
    }

    public function edit(Role $role): View
    {
        return view('roles.edit', [
            'role' => $role,
            'permissionsByModule' => $this->permissionsByModule(),
            'selected' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $role) {
            $role->update(['name' => $data['name']]);
            $role->syncPermissions($data['permissions'] ?? []);
        });

        return redirect()->route('roles.index')->with('status', 'Rol actualizado correctamente.');
    }

    /**
     * Permisos agrupados por módulo, en el orden del catálogo.
     */
    private function permissionsByModule()
    {
        return Permission::orderBy('id')->get()->groupBy('module');
    }
}
