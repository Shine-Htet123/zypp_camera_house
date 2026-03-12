<?php

return [
    'from_email' => 'no-reply@example.com',
    'from_name' => 'ZYPP Camera House',
    'smtp' => [
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'smtp-user@example.com',
        'password' => 'smtp-password',
        'encryption' => 'tls', // tls or ssl
        'auth' => true,
    ],
];
