<?php
// リライトルールのテストファイル
header('Content-Type: text/plain; charset=utf-8');
echo "=== REWRITE TEST FILE ACCESSED ===\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
echo "PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'not set') . "\n";
echo "REQUEST_FILENAME: " . ($_SERVER['REQUEST_FILENAME'] ?? 'not set') . "\n";
echo "REDIRECT_DEBUG_ROOT_REWRITE: " . ($_SERVER['REDIRECT_DEBUG_ROOT_REWRITE'] ?? 'not set') . "\n";
echo "\n";
echo "If you can see this, the rewrite rules are working.\n";
echo "Try accessing: /public/test_rewrite.php\n";

