<?php

it('serves the homepage', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
