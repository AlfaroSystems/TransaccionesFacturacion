<?php

use App\Models\User;

// El registro público está deshabilitado: los usuarios los crea un administrador
// desde la gestión de usuarios.

test('la pantalla de registro no está disponible', function () {
    $this->get('/register')->assertNotFound();
});

test('no se pueden crear cuentas por registro público', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
    expect(User::where('email', 'test@example.com')->exists())->toBeFalse();
});
