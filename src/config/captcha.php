<?php

return [
    'secret' => config('services.recaptcha.secret_key', env('RECAPTCHA_SECRET_KEY')),
    'sitekey' => config('services.recaptcha.site_key', env('RECAPTCHA_SITE_KEY')),
    'options' => [
        'timeout' => 30,
    ],
];
