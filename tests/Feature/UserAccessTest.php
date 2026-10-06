<?php

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

// =============================================================================
// Helpers
// =============================================================================

function rolAdmin(): Role
{
    return Role::firstOrCreate(['name' => 'admin']);
}

function crearRol(array $permisos = []): Role
{
    $role = Role::create(['name' => 'rol-prueba-'.Str::lower(Str::random(8))]);

    foreach ($permisos as $permiso) {
        Permission::firstOrCreate(['id' => $permiso], ['name' => $permiso]);

        // Los Gates se registran al arrancar la app; si el permiso es nuevo, se define aquí
        if (! Gate::has($permiso)) {
            Gate::define($permiso, fn (User $user) => $user->hasPermission($permiso));
        }
    }

    $role->permissions()->sync($permisos);

    return $role;
}

function crearUsuarioConRol(Role $role, array $atributos = []): User
{
    $user = User::factory()->create($atributos);
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    return $user->load('roles.permissions');
}

function crearSucursal(): Branch
{
    $company = Company::create(['name' => 'Empresa '.Str::random(6)]);

    return Branch::create(['company_id' => $company->id, 'name' => 'Sucursal '.Str::random(6)]);
}

// Usuario no admin con permisos de gestión de usuarios, asignado a una sucursal propia
function gestorDeUsuarios(): User
{
    return crearUsuarioConRol(crearRol([
        'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
    ]), ['id_branch' => crearSucursal()->id]);
}

function datosUsuario(User $user, array $cambios = []): array
{
    return array_merge([
        'name'      => $user->name,
        'email'     => $user->email,
        'id_branch' => $user->id_branch,
        'status'    => $user->status,
        'roles'     => $user->roles->pluck('id')->all(),
    ], $cambios);
}

// =============================================================================
// Punto 1: usuarios inactivos
// =============================================================================

test('un usuario inactivo no puede iniciar sesión', function () {
    $user = User::factory()->inactive()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('un usuario activo puede iniciar sesión', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $this->assertAuthenticatedAs($user);
});

test('una sesión abierta se cierra cuando el usuario es desactivado', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile')->assertOk();

    $user->update(['status' => 'inactive']);

    $this->get('/profile')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

// =============================================================================
// Punto 2: escalamiento de privilegios
// =============================================================================

test('un usuario que no es admin no puede asignar el rol admin a otro usuario', function () {
    $gestor = gestorDeUsuarios();
    $objetivo = crearUsuarioConRol(crearRol(), ['id_branch' => $gestor->id_branch]);

    $this->actingAs($gestor)
        ->put(route('users.update', $objetivo), datosUsuario($objetivo, ['roles' => [rolAdmin()->id]]))
        ->assertSessionHasErrors('roles');

    expect($objetivo->fresh()->isAdmin())->toBeFalse();
});

test('un usuario que no es admin no puede crear un usuario con rol admin', function () {
    $gestor = gestorDeUsuarios();

    $this->actingAs($gestor)
        ->post(route('users.store'), [
            'name'                  => 'Intruso',
            'email'                 => 'intruso-'.Str::random(6).'@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'status'                => 'active',
            'id_branch'             => $gestor->id_branch,
            'roles'                 => [rolAdmin()->id],
        ])
        ->assertSessionHasErrors('roles');

    $this->assertDatabaseMissing('users', ['name' => 'Intruso']);
});

test('un usuario que no es admin no puede modificar a un administrador', function () {
    $gestor = gestorDeUsuarios();
    $admin = crearUsuarioConRol(rolAdmin(), ['id_branch' => $gestor->id_branch]);
    $passwordOriginal = $admin->password;

    $this->actingAs($gestor)
        ->put(route('users.update', $admin), datosUsuario($admin, [
            'password'              => 'tomada12345',
            'password_confirmation' => 'tomada12345',
        ]))
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('error');

    expect($admin->fresh()->password)->toBe($passwordOriginal);

    $this->actingAs($gestor)
        ->delete(route('users.destroy', $admin))
        ->assertSessionHas('error');

    expect($admin->fresh()->isActive())->toBeTrue();
});

test('un usuario no puede cambiar sus propios roles', function () {
    $gestor = gestorDeUsuarios();
    $rolOriginal = $gestor->roles->first()->id;
    $otroRol = crearRol(['bitacora.ver']);

    $this->actingAs($gestor)
        ->put(route('users.update', $gestor), datosUsuario($gestor, ['roles' => [$otroRol->id]]))
        ->assertSessionHasErrors('roles');

    expect($gestor->fresh()->roles->pluck('id')->all())->toBe([$rolOriginal]);
});

test('un administrador no puede quitarse su propio rol ni desactivarse', function () {
    $admin = crearUsuarioConRol(rolAdmin());

    $this->actingAs($admin)
        ->put(route('users.update', $admin), datosUsuario($admin, ['roles' => [crearRol()->id]]))
        ->assertSessionHasErrors('roles');

    $this->actingAs($admin)
        ->put(route('users.update', $admin), datosUsuario($admin, ['status' => 'inactive']))
        ->assertSessionHasErrors('status');

    $admin->refresh();
    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->isActive())->toBeTrue();
});

test('un usuario puede editar sus propios datos sin enviar roles ni estado', function () {
    $gestor = gestorDeUsuarios();
    $roles = $gestor->roles->pluck('id')->all();

    $this->actingAs($gestor)
        ->put(route('users.update', $gestor), [
            'name'      => 'Nombre Actualizado',
            'email'     => $gestor->email,
            'id_branch' => $gestor->id_branch,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.index'));

    $gestor->refresh();
    expect($gestor->name)->toBe('Nombre Actualizado')
        ->and($gestor->roles->pluck('id')->all())->toBe($roles)
        ->and($gestor->isActive())->toBeTrue();
});

test('la lista de usuarios oculta el rol admin y protege a los administradores ante un no admin', function () {
    $gestor = gestorDeUsuarios();
    crearUsuarioConRol(rolAdmin(), ['id_branch' => $gestor->id_branch]);

    $response = $this->actingAs($gestor)->get(route('users.index'))->assertOk();

    expect($response->viewData('assignableRoles')->pluck('name'))->not->toContain('admin');
    $response->assertSee('Protegido');
});

test('un administrador puede asignar el rol admin', function () {
    $admin = crearUsuarioConRol(rolAdmin());
    $objetivo = crearUsuarioConRol(crearRol());

    $this->actingAs($admin)
        ->put(route('users.update', $objetivo), datosUsuario($objetivo, ['roles' => [rolAdmin()->id]]))
        ->assertSessionHasNoErrors();

    expect($objetivo->fresh()->isAdmin())->toBeTrue();
});

// =============================================================================
// Punto 3: conservación de la bitácora
// =============================================================================

test('eliminar desde la interfaz desactiva al usuario en lugar de borrarlo', function () {
    $gestor = gestorDeUsuarios();
    $objetivo = crearUsuarioConRol(crearRol(), ['id_branch' => $gestor->id_branch]);

    $this->actingAs($gestor)
        ->delete(route('users.destroy', $objetivo))
        ->assertSessionHas('success');

    expect($objetivo->fresh())->not->toBeNull()
        ->and($objetivo->fresh()->isActive())->toBeFalse();

    // Una segunda llamada lo reactiva
    $this->actingAs($gestor)->delete(route('users.destroy', $objetivo));

    expect($objetivo->fresh()->isActive())->toBeTrue();
});

test('un usuario no puede desactivarse a sí mismo', function () {
    $gestor = gestorDeUsuarios();

    $this->actingAs($gestor)
        ->delete(route('users.destroy', $gestor))
        ->assertSessionHas('error');

    expect($gestor->fresh()->isActive())->toBeTrue();
});

test('borrar un usuario conserva sus registros en la bitácora', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    crearRol(); // genera registros en la bitácora a nombre del usuario
    $logId = AuditLog::where('user_id', $user->id)->latest('id')->value('id');

    expect($logId)->not->toBeNull();

    // El borrado lo ejecuta otro usuario
    $this->actingAs(crearUsuarioConRol(rolAdmin()));
    $user->delete();

    $log = AuditLog::find($logId);
    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBeNull();
});
