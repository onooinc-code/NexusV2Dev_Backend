<?php
$j = json_decode(file_get_contents('composer.json'), true);
$j['require']['laravel/framework'] = '^13.0';
$j['require']['php'] = '^8.3';
$j['config']['audit'] = ['block-insecure' => false];
foreach(['laravel/horizon', 'laravel/reverb', 'laravel/sanctum', 'laravel/tinker', 'opcodesio/log-viewer', 'pusher/pusher-php-server'] as $p) {
    $j['require'][$p] = '*';
}
foreach(['barryvdh/laravel-debugbar', 'laravel/boost', 'laravel/pail', 'laravel/pint', 'laravel/sail', 'laravel/telescope', 'nunomaduro/collision', 'phpunit/phpunit'] as $p) {
    $j['require-dev'][$p] = '*';
}
file_put_contents('composer.json', json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
