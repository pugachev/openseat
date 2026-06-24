<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Open Seat

理容室・美容室向け空き状況共有アプリです。Laravel Sail (Docker) で動作します。

## Windows (RyzenAI / WSL2 + Docker) セットアップ

### 1. BIOS設定 — AMD仮想化の有効化

RyzenプロセッサーではBIOSでSVM Mode (AMD-V) を有効にする必要があります。

1. PC起動時に `Del` または `F2` を押してBIOS/UEFIへ入る
2. **Advanced** または **CPU Configuration** メニューを開く
3. **SVM Mode** (または **AMD-V**) を `Enabled` に変更
4. 保存して再起動

### 2. WSL2のインストール

PowerShell を**管理者権限**で起動して実行:

```powershell
wsl --install
wsl --set-default-version 2
```

インストール後、PC を再起動してから Ubuntu を初回起動し、Linuxユーザー名とパスワードを設定する。

WSL2が有効か確認:

```powershell
wsl -l -v
# STATE が "Running"、VERSION が "2" であることを確認
```

### 3. Docker Desktopのインストールと設定

1. Docker Desktop for Windows をインストール
2. インストール後、**Settings > General** で `Use the WSL 2 based engine` が ON になっていることを確認
3. **Settings > Resources > WSL Integration** で使用するディストリビューション（Ubuntu）を有効化
4. Apply & Restart

### 4. WSL2 Ubuntu ターミナルを開く

以降の作業はすべて **WSL2 の Ubuntu ターミナル内**で行う。

> **重要**: リポジトリは必ず WSL2 ファイルシステム内 (`~/`) にクローンすること。
> `/mnt/c/` 以下（Windows ドライブ）に置くとファイルI/Oが極端に遅くなり、Sail が正常に動作しません。

### 5. 必要パッケージのインストール (WSL2 Ubuntu内)

**方法A: WSL2にPHP + Composerをインストールする**

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y git curl php8.4 php8.4-cli php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip unzip
```

Composerのインストール:

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

**方法B: Docker経由でComposerを実行する（PHP不要）**

WSL2にPHPをインストールせず、DockerコンテナでComposerだけを実行する方法です。
必要なのは `git` のみ:

```bash
sudo apt update && sudo apt install -y git
```

### 6. リポジトリのクローンとセットアップ

```bash
cd ~
git clone <リポジトリURL> openseat
cd openseat
```

Windowsとのgit行末コード問題を防ぐため、クローン前に設定しておく:

```bash
git config --global core.autocrlf false
```

### 7. 環境ファイルの設定

```bash
cp .env.example .env
```

`.env` を編集してDB接続をMySQLに変更 (`.env.example` のデフォルトは SQLite):

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=open_seat
DB_USERNAME=sail
DB_PASSWORD=password
```

さらに WSL2 のユーザーIDを確認して追記:

```bash
id -u   # 通常 1000
id -g   # 通常 1000
```

`.env` に追記:

```dotenv
WWWUSER=1000
WWWGROUP=1000
```

### 8. Sailの初回起動

**方法Aの場合（WSL2にComposerをインストール済み）:**

```bash
composer install
./vendor/bin/sail up -d
```

**方法Bの場合（DockerでComposerを実行）:**

以下の1コマンドで `vendor/` を生成してから Sail を起動する:

```bash
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" \
  -w /var/www/html \
  laravelsail/php84-composer:latest \
  composer install --ignore-platform-reqs
./vendor/bin/sail up -d
```

起動確認:

```bash
./vendor/bin/sail ps
```

### 9. 初期化コマンド

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan storage:link
```

フロントエンド:

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

### 10. アクセス確認

| サービス | URL |
|---|---|
| アプリ | http://localhost |
| phpMyAdmin | http://localhost:8080 |
| Vite (開発) | http://localhost:5173 |

### Windows特有のトラブルシューティング

**ポート80が競合する場合**

IIS や他サービスがポート80を使用している場合は `.env` でポートを変更:

```dotenv
APP_PORT=8000
```

アクセスは `http://localhost:8000` になる。

**`sail` コマンドを短縮したい場合**

WSL2の `~/.bashrc` または `~/.zshrc` に追記:

```bash
alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'
```

以降は `sail up -d` のように省略して実行できる。

**Docker Desktop が起動しない / WSL2 接続エラー**

```powershell
# PowerShell (管理者) で実行
wsl --shutdown
wsl
```

その後 Docker Desktop を再起動する。

---

## Quick Start (初回セットアップ)

1. 環境ファイルを作成

```bash
cp .env.example .env
```

2. `.env` のDB設定を確認

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=open_seat
DB_USERNAME=mtake
DB_PASSWORD=password
```

3. 依存関係をインストール

```bash
composer install
```

4. Sailを起動

```bash
./vendor/bin/sail up -d
```

5. アプリキー生成

```bash
./vendor/bin/sail artisan key:generate
```

6. マイグレーションと初期データ投入

```bash
./vendor/bin/sail artisan migrate --seed
```

7. 画像公開用のストレージリンク作成

```bash
./vendor/bin/sail artisan storage:link
```

## フロントエンド

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

## よくあるハマりどころ: DB名不一致

- `MYSQL_DATABASE=${DB_DATABASE}` はMySQLコンテナ初回起動時のみ反映されます。
- 既存ボリュームがある状態で `.env` の `DB_DATABASE` を変更しても、コンテナ内の既存DB名は自動で変わりません。

対処方法:

1. 開発データを消してよい場合（推奨）

```bash
./vendor/bin/sail down -v
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
```

2. 既存データを保持したい場合
- `.env` の `DB_DATABASE` と同名のDBを作成
- `.env` の `DB_USERNAME` に権限付与
- その後 `migrate --seed` を実行

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


## Xserver
# .env　config/ routes/を更新した際は発行が必要
php8.2 artisan optimize:clear
php8.2 artisan optimize


# PVチェック
https://openseat.ikefukuro40.tech/admin/login
[admin/page-views](https://openseat.ikefukuro40.tech/admin/page-views)