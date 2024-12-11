<?php

return [
    'secret' => config('params.recaptcha_secret_site_key', env('RECAPTCHA_SECRET_KEY')),
    'sitekey' => config('params.recaptcha_site_key', env('RECAPTCHA_SITE_KEY')),
    'options' => [
        'timeout' => 30,
    ],
];
