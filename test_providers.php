<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$providers = $app->getLoadedProviders();
var_dump(array_key_exists('App\Providers\AppServiceProvider', $providers));
var_dump(array_key_exists('App\Providers\EventServiceProvider', $providers));
