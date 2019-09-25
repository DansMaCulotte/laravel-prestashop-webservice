<?php

return [
    'url' => env('PRESTASHOP_URL', null),
    'token' => env('PRESTASHOP_TOKEN', null),
    'debug' => env('PRESTASHOP_DEBUG', env('APP_DEBUG', false))
];
