<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Xserver環境対応: REQUEST_URIから/public/を削除（Request::capture()の前に実行）
if (isset($_SERVER['REQUEST_URI'])) {
    $originalUri = $_SERVER['REQUEST_URI'];
    
    // /public/で始まる場合は削除
    if (strpos($originalUri, '/public/') === 0) {
        $_SERVER['REQUEST_URI'] = substr($originalUri, 7); // '/public' の7文字を削除
    }
    // /publicで終わる場合（スラッシュなし）も対応
    elseif ($originalUri === '/public') {
        $_SERVER['REQUEST_URI'] = '/';
    }
    
    // PATH_INFOも修正（存在する場合）
    if (isset($_SERVER['PATH_INFO']) && strpos($_SERVER['PATH_INFO'], '/public/') === 0) {
        $_SERVER['PATH_INFO'] = substr($_SERVER['PATH_INFO'], 7);
    }
    
    // SCRIPT_NAMEも修正（存在する場合）
    if (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/public/') === 0) {
        $_SERVER['SCRIPT_NAME'] = substr($_SERVER['SCRIPT_NAME'], 7);
    }
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$request = Request::capture();

$response = $app->handleRequest($request);

if ($response) {
    $response->send();
    $app->terminate($request, $response);
}
