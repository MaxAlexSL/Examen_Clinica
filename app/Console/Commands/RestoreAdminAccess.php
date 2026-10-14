<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Comando de emergencia: recupera el acceso de administrador si el sistema se quedó sin él.
 * Solo se ejecuta desde el servidor (consola), nunca desde la web.
 *
 * Es el ÚNICO lugar que usa un nombre de rol: si no existe ningún rol con todos los
 * permisos, crea "Administrador". La aplicación sigue autorizando solo por permisos.
 */
class RestoreAdminAccess extends Command
{
    protected $signature = 'acceso:restaurar-admin {email : Correo del usuario que recuperará el acceso}';

    protected $description = 'Activa al usuario y le asigna un rol con todos los permisos';

    private const FALLBACK_ROLE_NAME = 'Administrador';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error("No existe un usuario con el correo {$this->argument('email')}.");

            return self::FAILURE;
        }

        $role = DB::transaction(function () use ($user) {
            $allPermissions = Permission::all();

            // Primero se busca un rol que ya tenga TODOS los permisos.
            $role = Role::withCount('permissions')->get()
                ->first(fn (Role $role) => $role->permissions_count === $allPermissions->count());

            // Si no hay ninguno, se crea (o se completa) el rol de emergencia.
            if ($role === null) {
                $role = Role::firstOrCreate(['name' => self::FALLBACK_ROLE_NAME, 'guard_name' => 'web']);
                $role->syncPermissions($allPermissions);
            }

            $user->forceFill(['is_active' => true])->save();
            $user->syncRoles([$role]);

            return $role;
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Acceso restaurado: {$user->email} está ACTIVO y tiene el rol \"{$role->name}\" con todos los permisos.");

        return self::SUCCESS;
    }
}
