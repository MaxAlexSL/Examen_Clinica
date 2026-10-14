<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Permisos, roles y usuarios de ejemplo (dentro de la transacción de cada prueba).
        $this->seed();
    }

    private function userByEmail(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_seller_can_not_access_users_index(): void
    {
        $seller = $this->userByEmail('vendedor1@example.com');

        $this->actingAs($seller)
            ->get('/usuarios')
            ->assertForbidden()
            ->assertSee('No tienes permiso para esta acción');
    }

    // Caso 02: el vendedor no puede cambiar su propio rol.
    public function test_seller_can_not_change_own_role(): void
    {
        $seller = $this->userByEmail('vendedor1@example.com');

        $this->actingAs($seller)
            ->put("/usuarios/{$seller->id}", [
                'name' => $seller->name,
                'email' => $seller->email,
                'is_active' => '1',
                'role' => 'Administrador',
            ])
            ->assertForbidden();

        $this->assertSame(['Vendedor'], $seller->fresh()->getRoleNames()->all());
    }

    public function test_admin_can_list_users(): void
    {
        $admin = $this->userByEmail('clinica@example.com');

        $this->actingAs($admin)
            ->get('/usuarios?q=vendedor')
            ->assertOk()
            ->assertSee('vendedor1@example.com')
            ->assertDontSee('admision@example.com');
    }

    public function test_admin_creates_user_with_role_and_hashed_password(): void
    {
        $admin = $this->userByEmail('clinica@example.com');

        $this->actingAs($admin)
            ->post('/usuarios', [
                'name' => 'Nuevo Usuario',
                'email' => 'nuevo@example.com',
                'password' => 'secreto123',
                'password_confirmation' => 'secreto123',
                'is_active' => '1',
                'role' => 'Admisión',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'));

        $user = $this->userByEmail('nuevo@example.com');

        $this->assertSame(['Admisión'], $user->getRoleNames()->all());
        $this->assertNotSame('secreto123', $user->password);
        $this->assertTrue(Hash::check('secreto123', $user->password));
        $this->assertTrue($user->is_active);
    }

    public function test_user_can_not_deactivate_self_or_change_own_role(): void
    {
        $admin = $this->userByEmail('clinica@example.com');

        $this->actingAs($admin)
            ->put("/usuarios/{$admin->id}", [
                'name' => $admin->name,
                'email' => $admin->email,
                'is_active' => '0',
                'role' => 'Vendedor',
            ])
            ->assertSessionHasErrors(['is_active', 'role']);

        $admin->refresh();
        $this->assertTrue($admin->is_active);
        $this->assertSame(['Administrador'], $admin->getRoleNames()->all());
    }

    public function test_password_is_kept_when_left_blank_on_update(): void
    {
        $admin = $this->userByEmail('clinica@example.com');
        $seller = $this->userByEmail('vendedor1@example.com');

        $this->actingAs($admin)
            ->put("/usuarios/{$seller->id}", [
                'name' => 'Vendedor Renombrado',
                'email' => $seller->email, // su propio correo no cuenta como duplicado
                'password' => '',
                'password_confirmation' => '',
                'is_active' => '0',
                'role' => 'Vendedor',
            ])
            ->assertSessionHasNoErrors();

        $seller->refresh();
        $this->assertSame('Vendedor Renombrado', $seller->name);
        $this->assertFalse($seller->is_active);
        $this->assertTrue(Hash::check('clinica123', $seller->password));
    }
}
