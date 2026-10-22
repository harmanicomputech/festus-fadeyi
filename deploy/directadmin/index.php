<?php

/*
| Front controller for DirectAdmin / cPanel hosting.
| This file lives in public_html; the application lives in the "ff85-app"
| folder next to public_html (outside the web root), or up to two levels
| higher when the site is a subdomain folder such as public_html/dr.
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));
define('FF85_PUBLIC_PATH', __DIR__);

$appPath = null;
foreach ([__DIR__.'/../ff85-app', __DIR__.'/../../ff85-app', __DIR__.'/../../../ff85-app'] as $candidate) {
    if (is_file($candidate.'/vendor/autoload.php')) {
        $appPath = realpath($candidate);
        break;
    }
}

if ($appPath === null) {
    http_response_code(500);
    exit('Application folder "ff85-app" was not found next to public_html. See INSTALL-DIRECTADMIN.txt.');
}

if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appPath.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appPath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
