<?php

it('returns a successful redirect to login', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});

