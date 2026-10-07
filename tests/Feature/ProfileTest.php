<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();
    $response = $this
        ->actingAs($user)
        ->get('/profile');
    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();
    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'username' => 'Test User',
            'email' => 'test@example.com',
        ]);
    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');
    $user->refresh();

    $this->assertSame('Test User', $user->username);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();
    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'username' => 'Test User',
            'email' => $user->email,
        ]);
    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');
    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('un usuario no puede eliminar su propia cuenta', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile')->assertOk()->assertDontSee('confirm-user-deletion');
    $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertMethodNotAllowed();

    $this->assertAuthenticatedAs($user);
    $this->assertNotNull($user->fresh());
});