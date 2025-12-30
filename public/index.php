<?php
// デバッグ用：このファイルが実行されているか確認（最優先で実行）
$logFile = __DIR__ . '/../storage/logs/debug.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

$debugInfo = date('Y-m-d H:i:s') . " - index.php executed\n";
$debugInfo .= "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
$debugInfo .= "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
$debugInfo .= "PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'not set') . "\n";
$debugInfo .= "REDIRECT_DEBUG_REWRITE: " . ($_SERVER['REDIRECT_DEBUG_REWRITE'] ?? 'not set') . "\n";
$debugInfo .= "REDIRECT_DEBUG_ROOT_REWRITE: " . ($_SERVER['REDIRECT_DEBUG_ROOT_REWRITE'] ?? 'not set') . "\n";
$debugInfo .= "REQUEST_FILENAME: " . ($_SERVER['REQUEST_FILENAME'] ?? 'not set') . "\n";
$debugInfo .= "---\n";

// 複数の場所にログを出力（パーミッションエラーを避けるため）
@file_put_contents($logFile, $debugInfo, FILE_APPEND);
@file_put_contents(__DIR__ . '/debug_index.log', $debugInfo, FILE_APPEND);
@file_put_contents(__DIR__ . '/../debug_root.log', $debugInfo, FILE_APPEND);
@error_log($debugInfo);

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
    
    // デバッグ用（一時的に有効化）
    file_put_contents(__DIR__ . '/../storage/logs/debug.log', 
        date('Y-m-d H:i:s') . " - Original REQUEST_URI: " . $originalUri . "\n",
        FILE_APPEND
    );
    
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
    
    // デバッグ用
    file_put_contents(__DIR__ . '/../storage/logs/debug.log', 
        date('Y-m-d H:i:s') . " - Modified REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n",
        FILE_APPEND
    );
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// 修正された$_SERVER変数からリクエストを作成
$request = Request::createFromGlobals();
$app->handleRequest($request);
