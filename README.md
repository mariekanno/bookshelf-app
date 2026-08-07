# Bookshelf App

Laravelを使用して開発した書籍管理アプリです。

書籍の登録・編集・削除、レビュー投稿、お気に入り、ランキング、読書計画、通知機能、Google Books APIによるISBN検索、REST APIなどを実装しています。

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
git clone <リポジトリURL>
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
