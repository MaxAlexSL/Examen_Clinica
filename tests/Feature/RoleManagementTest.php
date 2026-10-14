<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Permisos, roles y usuarios de ejemplo (dentro de la transacción de cada prueba).
        $this->seed();
    }

    private function admin(): User
    {
        return User::where('email', 'clinica@example.com')->firstOrFail();
    }

    public function test_seller_can_not_access_roles_index(): void
    {
        $seller = User::where('email', 'vendedor1@example.com')->firstOrFail();

        $this->actingAs($seller)->get('/roles')->assertForbidden();
    }

    // RN-02: un rol sin permisos no opera.
    public function test_role_without_permissions_can_not_access_users(): void
    {
        Role::create(['name' => 'Sin permisos', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->syncRoles(['Sin permisos']);

        $this->actingAs($user)->get('/usuarios')->assertForbidden();
    }

    // RN-02: quitar un permiso bloquea la acción en la siguiente petición.
    public function test_removing_a_permission_blocks_the_next_request(): void
    {
        $role = Role::create(['name' => 'Auditor', 'guard_name' => 'web']);
        $role->syncPermissions(['usuarios.ver']);

        $user = User::factory()->create();
        $user->syncRoles(['Auditor']);

        $this->actingAs($user)->get('/usuarios')->assertOk();

        // El administrador le quita el permiso desde el módulo de roles.
        $this->actingAs($this->admin())
            ->put("/roles/{$role->id}", ['name' => 'Auditor', 'permissions' => []])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)->get('/usuarios')->assertForbidden();
    }

    public function test_admin_can_list_roles_with_counts(): void
    {
        $this->actingAs($this->admin())
            ->get('/roles')
            ->assertOk()
            ->assertSee('Administrador')
            ->assertSee('Vendedor');
    }

    public function test_admin_creates_role_with_permissions(): void
    {
        $this->actingAs($this->admin())
            ->post('/roles', [
                'name' => 'Caja',
                'permissions' => ['ventas.crear', 'ventas.ver'],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('roles.index'));

        $role = Role::findByName('Caja');
        $this->assertEqualsCanonicalizing(['ventas.crear', 'ventas.ver'], $role->permissions->pluck('name')->all());
    }

    public function test_unknown_permissions_are_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post('/roles', [
                'name' => 'Hacker',
                'permissions' => ['usuarios.ver', 'permiso.inventado'],
            ])
            ->assertSessionHasErrors('permissions.1');

        $this->assertDatabaseMissing('roles', ['name' => 'Hacker']);
    }

    public function test_role_name_must_be_unique(): void
    {
        $this->actingAs($this->admin())
            ->post('/roles', ['name' => 'Vendedor', 'permissions' => []])
            ->assertSessionHasErrors('name');
    }

    public function test_user_can_not_remove_roles_editar_from_own_role(): void
    {
        $role = Role::findByName('Administrador');

        $this->actingAs($this->admin())
            ->put("/roles/{$role->id}", [
                'name' => 'Administrador',
                'permissions' => ['roles.ver', 'usuarios.ver'],
            ])
            ->assertSessionHasErrors('permissions');

        $this->assertTrue($role->fresh()->hasPermissionTo('roles.editar'));
        $this->assertCount(17, $role->fresh()->permissions);
    }
}
