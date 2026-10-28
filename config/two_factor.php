<?php

return [
    // Optional dedicated transport; never permit log/array transports for real verification codes.
    'email_mailer' => env('TWO_FACTOR_MAILER'),
];
