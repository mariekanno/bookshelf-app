# Bookshelf書籍レビューアプリ

Laravelを使用して開発した、書籍レビュー・読書管理Webアプリケーションです。

書籍・ジャンル・レビューの管理に加え、お気に入り、ランキング、読書計画、通知、レポートなどの機能を実装しています。

バックエンドでは、LaravelによるCRUD処理だけでなく、認証・認可、REST API、Laravel SanctumによるAPI認証、Google Books APIとの外部API連携、通知・バッチ処理、Feature Testなどを実装しました。

---

## 作成者

菅野 まりえ

---

## 使用技術

- PHP 8.2
- Laravel 10.x
- MySQL 8.0
- Nginx
- Docker / Docker Compose / Laravel Sail
- Laravel Fortify（認証）
- Laravel Sanctum（API認証）
- Google Books API
- PHPUnit
- phpMyAdmin

---

## 設計・実装で工夫した点

### 1. 認証・認可の責務分離

Laravel Fortifyを利用した認証に加え、書籍・レビュー・読書計画などの操作権限をPolicyで管理しました。

認証と認可を分け、Controllerに権限判定のロジックを集中させない設計を意識しました。

### 2. FormRequestによるバリデーション

登録・更新時のバリデーションはFormRequestに分離しました。

Controllerの責務を減らし、入力値の検証ルールを管理しやすい構成にしています。

### 3. REST APIとSanctum認証

書籍情報を取得するAPIに加え、Laravel Sanctumを利用した認証が必要なAPIを実装しました。

Web画面だけでなく、外部クライアントから利用することを想定したAPI設計について学習・実装しています。

### 4. 読書計画・通知・バッチ処理

読書計画には「進行中」「読了」「期限切れ」のステータスを設けています。

スケジュール処理によって期限切れステータスへの更新を行い、読書期日の3日前・当日・3日後には対象ユーザーへ通知する機能を実装しました。

### 5. 外部API連携

Google Books APIと連携し、ISBNを利用して書籍情報を検索できる機能を実装しました。

外部APIを利用するテストではHTTP Fakeを使用し、外部サービスの状態に依存しにくいテストを作成しました。

### 6. Eloquent ORM・リレーション

書籍・ジャンル・レビュー・お気に入りなどの関連をEloquent ORMで定義しています。

多対多のリレーションや複合ユニーク制約を使用し、データの整合性を考慮したデータベース設計を行いました。

### 7. テスト

Feature Testを中心に、認証、認可、CRUD、API、通知、バッチ処理などのテストを実装しました。

最終確認時には全テストがPASSし、テストカバレッジ83%を確認しています。

---

## ER図

```mermaid
erDiagram
users ||--o{ books : creates
users ||--o{ reviews : writes
users ||--o{ favorites : registers
users ||--o{ likes : adds
users ||--o{ reading_plans : creates

books ||--o{ reviews : has
books ||--o{ favorites : has
books ||--o{ reading_plans : has
books ||--o{ book_genre : belongs_to

genres ||--o{ book_genre : belongs_to

reviews ||--o{ likes : has

users {
bigint id PK
varchar name
varchar email UK
timestamp email_verified_at
varchar password
varchar remember_token
timestamp created_at
timestamp updated_at
}

books {
bigint id PK
varchar title
varchar author
varchar isbn UK
date published_date
text description
varchar image_url
bigint created_by FK
timestamp created_at
timestamp updated_at
}

genres {
bigint id PK
varchar name UK
timestamp created_at
timestamp updated_at
}

book_genre {
bigint id PK
bigint book_id FK
bigint genre_id FK
timestamp created_at
timestamp updated_at
}

reviews {
bigint id PK
bigint user_id FK
bigint book_id FK
tinyint rating
text comment
timestamp created_at
timestamp updated_at
}

favorites {
bigint id PK
bigint user_id FK
bigint book_id FK
timestamp created_at
timestamp updated_at
}

likes {
bigint id PK
bigint user_id FK
bigint review_id FK
timestamp created_at
timestamp updated_at
}

reading_plans {
bigint id PK
bigint user_id FK
bigint book_id FK
date target_date
timestamp completed_at
varchar status
timestamp created_at
timestamp updated_at
}

notifications {
uuid id PK
varchar type
varchar notifiable_type
bigint notifiable_id
text data
timestamp read_at
timestamp created_at
timestamp updated_at
}
```

### 主な制約

- `book_genre`：`book_id` と `genre_id` の複合ユニーク制約
- `favorites`：`user_id` と `book_id` の複合ユニーク制約
- `likes`：`user_id` と `review_id` の複合ユニーク制約
- `reading_plans.status`：`in_progress`・`completed`・`overdue`
- `reading_plans.completed_at`：読了前はNULL
- `notifications`：Laravel標準のポリモーフィック通知テーブル


---

# 開発環境URL

http://localhost

---

# 動作環境

- Docker
- Docker Compose

※WindowsではWSL2の利用を推奨しています。

---

# 環境構築手順

## 1. リポジトリをクローン

```bash
git clone https://github.com/mariekanno/bookshelf-app.git
```

## 2. .envを作成

```bash
cp .env.example .env
```

以下を設定してください。

```text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

GOOGLE_BOOKS_API_KEY=あなたのAPIキー
```

---

## 3. Composerパッケージのインストール

```bash
docker run --rm \
-u "$(id -u):$(id -g)" \
-v "$(pwd):/var/www/html" \
-w /var/www/html \
laravelsail/php82-composer:latest \
composer install --ignore-platform-reqs
```

---

## 4. Sail起動

```bash
./vendor/bin/sail up -d
```

---

## 5. アプリケーションキー生成

```bash
sail artisan key:generate
```

---

## 6. マイグレーション・シーディング

```bash
sail artisan migrate:fresh --seed
```

---

## 7. Node Modulesインストール

```bash
sail npm install
```

---

## 8. フロントビルド

```bash
sail npm run build
```

---

## 9. アプリ起動

http://localhost

---

# テスト実行

通常

```bash
sail artisan test
```

カバレッジ付き

```bash
sail artisan test --coverage
```
### テスト結果

- 全テスト：PASS
- テストカバレッジ：83%

主に以下の項目をFeature Testで検証しています。

- 認証
- 認可
- 書籍CRUD
- REST API
- 読書計画
- 通知
- バッチ処理
- 外部API連携

---

# 主な機能

### 基本機能

- ユーザー登録
- ログイン
- ログアウト
- 書籍CRUD
- ジャンルCRUD
- レビューCRUD
- お気に入り
- レビューいいね
- ランキング表示

### 応用機能

- 読書計画CRUD
- 読了機能
- 通知一覧
- 日時バッチによる通知
- マイ読書レポート
- Google Books API連携
- Sanctum認証API

---

## 画面イメージ

### 書籍一覧
<img width="959" height="410" alt="書籍一覧画面" src="https://github.com/user-attachments/assets/f49d9ae7-3d7a-40d3-9b72-9d0c7cd610f8" />

### 書籍詳細
<img width="947" height="412" alt="書籍詳細画面" src="https://github.com/user-attachments/assets/99ce28a0-7a43-44ca-9f00-0b87238b7b40" />


### 読書計画一覧
<img width="950" height="407" alt="読書計画一覧画面" src="https://github.com/user-attachments/assets/87db61c3-46ae-46d1-9c0a-a3f7275bea43" />

### マイ読書レポート
<img width="947" height="411" alt="マイ読書レポート画面" src="https://github.com/user-attachments/assets/529df3d3-4932-403f-96eb-5bf04c3d9490" />


### 通知一覧
<img width="957" height="410" alt="通知一覧画面" src="https://github.com/user-attachments/assets/89e5fa39-18ef-4826-a080-449aa78e9d6b" />

---

## APIエンドポイント

### 認証

|　Method　|　URI　|　概要　|
|---|---|---|
| POST | /api/v1/login | ログイン・トークン発行 |
| POST | /api/v1/logout | ログアウト・トークン削除 |

### 書籍

|　Method　|　URI　|　概要　| 認証 |
|---|---|---|---|
| GET | /api/v1/books | 書籍一覧取得 | 不要 |
| GET | /api/v1/books/{book} | 書籍詳細取得 | 不要 |
| POST | /api/v1/books | 書籍登録 | Sanctum |
| PUT | /api/v1/books/{book} | 書籍更新 | Sanctum |
| DELETE | /api/v1/books/{book} | 書籍削除 | Sanctum |
