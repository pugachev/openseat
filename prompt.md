# 開発タスク：理容室・美容室向け空き状況共有アプリ「Open Seat」の実装

あなたは熟練したLaravel開発者です。
現在のワークスペースに、以下の要件に従ってアプリケーションを実装してください。

## 1. アプリ概要
アプリ名: **Open Seat**
予約システムを持たない理容室・美容室が、リアルタイムで「現在の混雑状況（空席状況）」を発信し、ユーザーがそれを確認できるWebサービス。

## 2. 開発環境・前提条件
- **OS: MacOS** (Apple Silicon または Intel)
- **環境:** Docker (Laravel Sail) を使用
- フレームワーク: Laravel (現在の環境を使用)
- **データベース: MySQL** (Dockerコンテナ内で動作)
- CSS: Tailwind CSS
- デザイン: 完全レスポンシブ対応（モバイルファースト）
- 認証: 店舗側のログイン機能は「なし」。`secret_key` をURLに含むことで認証とする。

## 3. 実装ステップ（この順序で実行してください）

### Step 0: 環境設定 (.env)
Docker環境(Sail)に合わせて `.env` を修正してください。
1. `DB_CONNECTION=mysql`
2. `DB_HOST=mysql` (**重要:** 127.0.0.1ではなく、Dockerサービス名を指定)
3. `DB_PORT=3306`
4. `DB_DATABASE=open_seat` (※まだ作成されていない場合は作成手順も提示すること)
5. `DB_USERNAME=sail`
6. `DB_PASSWORD=password`

**補足:** データベースはマイグレーション実行時に自動作成されます。

**初回セットアップ時の注意（重要）:**
- `MYSQL_DATABASE=${DB_DATABASE}` は **MySQLコンテナ初回起動時のみ** 反映されます。
- 既存Dockerボリュームがある場合、`.env` の `DB_DATABASE` を変更しても既存DB名は自動変更されません。
- `DB_DATABASE=open_seat` なのにコンテナ内に `openseat` しかない、という不一致が起こり得ます。

**不一致が起きた場合の対処:**
1. 開発環境でデータを消してよい場合: `./vendor/bin/sail down -v` → `./vendor/bin/sail up -d` → `./vendor/bin/sail artisan migrate --seed`
2. 既存データを保持したい場合: `open_seat` を手動作成し、`.env` のユーザーに権限付与した上で `migrate --seed` を実行

**注記:** MySQLのDB自体は「マイグレーションで自動作成」ではなく、通常はコンテナ初期化または手動作成で用意します。

### Step 0.5: フロントエンドアセットのセットアップ
Tailwind CSSを使用するため、npmパッケージのインストールとビルドが必要です。

1. **npmパッケージのインストール**
   ```bash
   ./vendor/bin/sail npm install
   ```

2. **Viteのビルド（開発環境の場合）**
   ```bash
   ./vendor/bin/sail npm run build
   ```
   または開発サーバー起動（ホットリロード有効）:
   ```bash
   ./vendor/bin/sail npm run dev
   ```

**注意:** 本番環境（Xserver等）では、ローカルでビルドした `public/build/` ディレクトリをアップロードしてください。

### Step 0.6: 画像アップロード用のストレージリンク作成
画像ファイルを `storage/app/public` に保存し、Web公開ディレクトリから参照できるようにシンボリックリンクを作成してください。

```bash
./vendor/bin/sail artisan storage:link
```

**補足:** 上記により `public/storage` -> `storage/app/public` のリンクが作成されます。

### Step 1: マイグレーションとモデルの作成
以下のスキーマで `Shop` モデルとマイグレーションファイルを作成し、マイグレートを実行してください。
- `id`: PK
- `name`: string (店名)
- `phone`: string (電話番号)
- `address`: string (住所)
- `latitude`: decimal(10, 8), nullable (緯度)
- `longitude`: decimal(11, 8), nullable (経度)
- `status`: integer (0=空き, 1=待ち, 2=満席, default=0)
- `secret_key`: string, unique, index (店舗識別用)
- `timestamps`

### Step 2: シーダーの作成
動作確認用に、ダミー店舗を3件登録する `ShopSeeder` を作成してください。
- 店名例: "理容タナカ", "カットサロン・エース", "バーバーひかり"
- `secret_key` はランダムな文字列を入れてください。
- 緯度経度は日本の適当な場所（例: 神戸周辺）を入れてください。

### Step 3: ルーティングとコントローラー
`routes/web.php` と `ShopController` を実装してください。
1. `GET /` : ユーザー用一覧画面 (`index` メソッド)
2. `GET /shop/control/{secret_key}` : 店舗用操作画面 (`edit` メソッド)
3. `POST /shop/control/{secret_key}/update` : ステータス更新処理 (`update` メソッド)

### Step 4: ビューの作成 (Blade & Tailwind Responsive)
レスポンシブデザインを意識して実装してください。

1. **共通レイアウト (`layouts/app.blade.php`)**
   - ヘッダーに「Open Seat」のロゴ（テキスト）を配置。
   - コンテンツ幅は `max-w-7xl` で中央寄せ (`mx-auto`)。

2. **ユーザー画面 (`shops/index.blade.php`)**
   - 店舗一覧を表示。
   - **レスポンシブグリッド:**
     - スマホ: 1列 (`grid-cols-1`)
     - タブレット: 2列 (`md:grid-cols-2`)
     - PC: 3列 (`lg:grid-cols-3`)
   - 各カードにはステータスバッジと「📞 電話する」ボタンを配置。

3. **店舗用画面 (`shops/edit.blade.php`)**
   - **UI要件:** 高齢者がPCで開いた場合でも混乱しないよう配慮する。
   - コンテナ: 画面中央に配置し、最大幅を制限する (`max-w-md mx-auto`)。
   - コンテンツ: 縦積みの3つの巨大なボタン（🟢 🟡 🔴）。
   - 操作フィードバック: ステータス変更が完了したことを視覚的にわかりやすく伝える。

## 4. エラーハンドリング
- `secret_key` が間違っている場合は 404 を返してください。

---
**指示:**
まずは上記の要件を理解し、**「実装プラン」**を提示してください。
特に `.env` の設定値がDocker環境に適しているか確認してください。
承認後、ファイル作成を行ってください。
