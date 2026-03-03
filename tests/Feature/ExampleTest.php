<?php

test('the application redirects to admin', function () {
    $response = $this->get('/');

    $response->assertRedirect('/admin');
});
