<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');
    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);
        $response->assertStatus(200);
        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});
test('pedir el enlace responde igual exista o no la cuenta', function () {
    Notification::fake();
    $user = User::factory()->create();

    $existe = $this->post('/forgot-password', ['email' => $user->email]);
    $noExiste = $this->post('/forgot-password', ['email' => 'nadie-'.uniqid().'@example.com']);

    foreach ([$existe, $noExiste] as $response) {
        $response->assertSessionHasNoErrors()->assertSessionHas('status', __('passwords.sent'));
    }

    Notification::assertSentTimes(ResetPasswordNotification::class, 1);
});

test('pedir el enlace tiene un límite de intentos por minuto', function () {
    Notification::fake();

    foreach (range(1, 6) as $intento) {
        $this->post('/forgot-password', ['email' => "intento{$intento}@example.com"])->assertRedirect();
    }

    $this->post('/forgot-password', ['email' => 'otro@example.com'])->assertTooManyRequests();
});
