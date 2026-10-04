<?php

test('home route redirects guest to login', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});