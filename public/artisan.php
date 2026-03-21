shell_exec('php artisan config:clear');
shell_exec('php artisan config:cache');
shell_exec('php artisan view:clear');
shell_exec('php artisan cache:clear');
echo "artisanコマンド実行完了";