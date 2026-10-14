<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AdminGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El sistema nunca debe quedarse sin un usuario ACTIVO capaz de administrar roles y usuarios.
 * En el seeder, el único administrador es clinica@example.com (rol Administrador).
 */
class AdminGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function userByEmail(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function adminRole(): Role
    {
        return Role::findByName('Administrador');
    }

    /**
     * Usuario con un rol que tiene solo algunos permisos (no es "administrador" para AdminGuard).
     *
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(string $roleName, array $permissions): User
    {
        Role::create(['name' => $roleName, 'guard_name' => 'web'])->syncPermissions($permissions);
        $user = User::factory()->create();
        $user->syncRoles([$roleName]);

        return $user;
    }

    private function roleManager(): User
    {
        return $this->userWithPermissions('Gestor de roles', ['roles.ver', 'roles.editar']);
    }

    private function userManager(): User
    {
        return $this->userWithPermissions('Gestor de usuarios', ['usuarios.ver', 'usuarios.editar']);
    }

    /**
     * @param  array<int, string>  $remove
     * @return array<int, string>
     */
    private function allPermissionsExcept(array $remove): array
    {
        return Permission::pluck('name')->diff($remove)->values()->all();
    }

    private function updateUser(User $actor, User $target, string $role, bool $active)
    {
        return $this->actingAs($actor)->put("/usuarios/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'is_active' => $active ? '1' : '0',
            'role' => $role,
        ]);
    }

    public function test_quitar_roles_editar_o_roles_ver_al_unico_rol_administrador_se_rechaza(): void
    {
        $role = $this->adminRole();
        $manager = $this->roleManager();

        foreach (['roles.editar', 'roles.ver'] as $permission) {
            $this->actingAs($manager)
                ->put("/roles/{$role->id}", ['name' => $role->name, 'permissions' => $this->allPermissionsExcept([$permission])])
                ->assertSessionHasErrors(['permissions' => AdminGuard::MESSAGE]);

            $this->assertTrue($role->fresh()->hasPermissionTo($permission));
        }
    }

    public function test_quitar_un_permiso_no_critico_al_rol_administrador_se_permite(): void
    {
        $role = $this->adminRole();

        $this->actingAs($this->roleManager())
            ->put("/roles/{$role->id}", ['name' => $role->name, 'permissions' => $this->allPermissionsExcept(['ventas.crear'])])
            ->assertSessionHasNoErrors();

        $this->assertFalse($role->fresh()->hasPermissionTo('ventas.crear'));
    }

    public function test_cambiar_de_rol_al_ultimo_administrador_se_rechaza(): void
    {
        $admin = $this->userByEmail('clinica@example.com');

        $this->updateUser($this->userManager(), $admin, 'Vendedor', true)
            ->assertSessionHasErrors(['role' => AdminGuard::MESSAGE]);

        $this->assertSame(['Administrador'], $admin->fresh()->getRoleNames()->all());
    }

    public function test_desactivar_al_ultimo_administrador_se_rechaza(): void
    {
        $admin = $this->userByEmail('clinica@example.com');

        $this->updateUser($this->userManager(), $admin, 'Administrador', false)
            ->assertSessionHasErrors(['is_active' => AdminGuard::MESSAGE]);

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_un_segundo_administrador_inactivo_no_cuenta(): void
    {
        $admin = $this->userByEmail('clinica@example.com');
        User::factory()->create(['is_active' => false])->syncRoles(['Administrador']);

        $this->updateUser($this->userManager(), $admin, 'Vendedor', true)
            ->assertSessionHasErrors(['role' => AdminGuard::MESSAGE]);
    }

    public function test_con_dos_administradores_activos_si_se_permite(): void
    {
        $admin = $this->userByEmail('clinica@example.com');
        $secondAdmin = User::factory()->create();
        $secondAdmin->syncRoles(['Administrador']);

        // El segundo administrador cambia de rol al primero…
        $this->updateUser($secondAdmin, $admin, 'Vendedor', true)->assertSessionHasNoErrors();
        $this->assertSame(['Vendedor'], $admin->fresh()->getRoleNames()->all());

        // …y ahora él es el último: el gestor de usuarios ya no puede desactivarlo.
        $this->updateUser($this->userManager(), $secondAdmin, 'Administrador', false)
            ->assertSessionHasErrors(['is_active' => AdminGuard::MESSAGE]);
    }

    public function test_con_otro_rol_administrador_si_se_permite_quitar_permisos_criticos(): void
    {
        $role = $this->adminRole();
        // Otro rol con los permisos críticos y un usuario activo que lo tiene.
        $this->userWithPermissions('Soporte', AdminGuard::CRITICAL_PERMISSIONS);

        $this->actingAs($this->roleManager())
            ->put("/roles/{$role->id}", ['name' => $role->name, 'permissions' => $this->allPermissionsExcept(['roles.editar'])])
            ->assertSessionHasNoErrors();

        $this->assertFalse($role->fresh()->hasPermissionTo('roles.editar'));
    }

    public function test_el_comando_restaura_el_acceso_de_administrador(): void
    {
        // Se simula un sistema roto: el rol Administrador perdió permisos y el usuario está inactivo.
        $this->adminRole()->syncPermissions(['ventas.ver']);
        $user = $this->userByEmail('vendedor1@example.com');
        $user->update(['is_active' => false]);

        $this->artisan('acceso:restaurar-admin', ['email' => $user->email])
            ->expectsOutputToContain('Acceso restaurado')
            ->assertSuccessful();

        $user->refresh();
        $this->assertTrue($user->is_active);
        $this->assertCount(1, $user->roles);
        $this->assertSame(Permission::count(), $user->getAllPermissions()->count());

        // Y puede volver a administrar desde la web.
        $this->actingAs($user)->get('/roles')->assertOk();
        $this->actingAs($user)->get('/usuarios')->assertOk();
    }

    public function test_el_comando_falla_si_el_correo_no_existe(): void
    {
        $this->artisan('acceso:restaurar-admin', ['email' => 'nadie@example.com'])
            ->expectsOutputToContain('No existe un usuario')
            ->assertFailed();
    }
}
