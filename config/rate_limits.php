<?php

return [
    'login' => [
        'max_attempts' => 5,
        'decay_seconds' => 60,
    ],
    'register' => [
        'max_attempts' => 3,
        'decay_seconds' => 3600,
    ],
    'password_reset' => [
        'max_attempts' => 3,
        'decay_seconds' => 3600,
    ],
    'ticket_create' => [
        'max_attempts' => 10,
        'decay_seconds' => 3600,
    ],
];
