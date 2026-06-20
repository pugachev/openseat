本番環境（Lightsail）へのデプロイ手順をまとめます。

以下のステップで対応してください：

## 1. 変更ファイルの確認

まず `git diff HEAD~1 --name-only` または `git status` を実行して、変更されたファイルの一覧を表示してください。

## 2. FileZilla でのアップロード

変更ファイルの一覧を表示し、ユーザーに以下を伝えてください：
- サーバーパス: `/var/www/openseat/`
- アップロードが必要なファイル一覧

## 3. Lightsail ブラウザコンソールで実行するコマンド

変更内容に応じて、必要なコマンドを選んで提示してください。

### 必ず実行するコマンド（キャッシュクリア）
```bash
cd /var/www/openseat
docker exec openseat-laravel.test-1 php artisan cache:clear
docker exec openseat-laravel.test-1 php artisan config:clear
docker exec openseat-laravel.test-1 php artisan view:clear
docker exec openseat-laravel.test-1 php artisan route:clear
```

### composer.json / composer.lock が変更された場合
```bash
docker exec openseat-laravel.test-1 composer install --no-dev --optimize-autoloader
```

### database/migrations/ 以下にファイルが追加された場合
```bash
docker exec openseat-laravel.test-1 php artisan migrate --force
```

### resources/ 以下の JS/CSS が変更された場合（フロントエンドビルド）
```bash
docker exec openseat-laravel.test-1 npm run build
```

### .env の変更が必要な場合
```bash
# サーバー上の .env を直接編集
nano /var/www/openseat/.env
# 編集後にキャッシュ再生成
docker exec openseat-laravel.test-1 php artisan config:cache
```

### すべてのキャッシュを最適化する場合（本番推奨）
```bash
docker exec openseat-laravel.test-1 php artisan config:cache
docker exec openseat-laravel.test-1 php artisan route:cache
docker exec openseat-laravel.test-1 php artisan view:cache
```

---

変更ファイルを確認した上で、実際に必要なコマンドだけをまとめて提示してください。
