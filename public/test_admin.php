<?php
// デバッグ用テストファイル
header('Content-Type: text/plain; charset=utf-8');
echo "=== TEST FILE ACCESSED ===\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
echo "PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'not set') . "\n";
echo "\n";
echo "If you can see this, the file is accessible.\n";
echo "Try accessing: /public/test_admin.php\n";

