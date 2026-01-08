# 沖縄イベント情報Webシステム - ER図

## エンティティ関連図（Entity Relationship Diagram）

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           データベース構造図                                  │
└─────────────────────────────────────────────────────────────────────────────┘

                              ┌──────────────┐
                              │    users     │
                              ├──────────────┤
                              │ id (PK)      │
                              │ name         │
                              │ email        │
                              │ password     │
                              │ google_cal...│
                              │ created_at   │
                              │ updated_at   │
                              └──────┬───────┘
                                     │
         ┌───────────────┬───────────┼───────────┬───────────────┬─────────┐
         │               │           │           │               │         │
         ▼               ▼           ▼           ▼               ▼         ▼
┌─────────────┐  ┌──────────┐  ┌────────┐  ┌─────────┐  ┌──────────┐  ┌─────────┐
│password_    │  │personal_ │  │events  │  │reviews  │  │event_    │  │buddy_   │
│resets       │  │access_   │  │        │  │         │  │favorites │  │posts    │
├─────────────┤  │tokens    │  ├────────┤  ├─────────┤  ├──────────┤  ├─────────┤
│ email       │  ├──────────┤  │id (PK) │  │id (PK)  │  │id (PK)   │  │id (PK)  │
│ token       │  │id (PK)   │  │user_id │  │event_id │  │user_id   │  │event_id │
│ created_at  │  │user_id   │  │category│  │user_id  │  │event_id  │  │user_id  │
└─────────────┘  │name      │  │_id     │  │rating   │  │created_at│  │title    │
                 │token     │  │name    │  │comment  │  │updated_at│  │message  │
                 │abilities │  │overview│  │created_at│ └──────────┘  │max_     │
                 │created_at│  │location│  │updated_at│                │ partici │
                 │updated_at│  │address │  │deleted_at│                │ pants   │
                 └──────────┘  │start_  │  └────┬────┘                │status   │
                               │ date   │       │                      │created_at│
                 ┌──────────┐  │end_date│       │                      │updated_at│
                 │categories│  │image_  │       │                      │deleted_at│
                 ├──────────┤  │ url    │       │                      └────┬────┘
                 │id (PK)   │  │external│       │                           │
                 │name      │  │_url    │       │                           │
                 │created_at│  │latitude│       │                           │
                 │updated_at│  │longitude│      │                           │
                 └────┬─────┘  │venue_  │       │                           │
                      │        │ type   │       │                           │
                      │        │created_│       │                           │
                      │        │ at     │       │                           │
                      │        │updated_│       │                           │
                      │        │ at     │       │                           │
                      │        │deleted_│       │                           │
                      │        │ at     │       │                           │
                      │        └───┬────┘       │                           │
                      │            │            │                           │
                      └────────────┘            │                           │
                                                │                           │
                                    ┌───────────┴───────────┐               │
                                    │                       │               │
                                    ▼                       ▼               │
                            ┌──────────────┐      ┌─────────────┐          │
                            │review_       │      │likes        │          │
                            │comments      │      ├─────────────┤          │
                            ├──────────────┤      │id (PK)      │          │
                            │id (PK)       │      │review_id    │          │
                            │review_id     │      │user_id      │          │
                            │user_id       │      │created_at   │          │
                            │comment       │      │updated_at   │          │
                            │created_at    │      │deleted_at   │          │
                            │updated_at    │      └─────────────┘          │
                            │deleted_at    │                                │
                            └──────────────┘                                │
                                                                            │
                                    ┌───────────────────────────────────────┘
                                    │
                                    ▼
                            ┌─────────────────┐
                            │buddy_post_      │
                            │participants     │
                            ├─────────────────┤
                            │id (PK)          │
                            │buddy_post_id    │
                            │user_id          │
                            │status           │
                            │created_at       │
                            │updated_at       │
                            └─────────────────┘
                                    │
                                    │ (1:1)
                                    ▼
                            ┌─────────────────┐
                            │chat_rooms       │
                            ├─────────────────┤
                            │id (PK)          │
                            │buddy_post_id    │
                            │created_at       │
                            │updated_at       │
                            │deleted_at       │
                            └────────┬────────┘
                                     │
                                     │ (1:N)
                                     ▼
                            ┌─────────────────┐
                            │chat_messages    │
                            ├─────────────────┤
                            │id (PK)          │
                            │chat_room_id     │
                            │user_id          │
                            │message          │
                            │is_read          │
                            │created_at       │
                            │updated_at       │
                            │deleted_at       │
                            └─────────────────┘

                            ┌─────────────────┐
                            │failed_jobs      │
                            ├─────────────────┤
                            │id (PK)          │
                            │uuid             │
                            │connection       │
                            │queue            │
                            │payload          │
                            │exception        │
                            │failed_at        │
                            └─────────────────┘
```

## テーブル詳細説明

### 1. ユーザー関連テーブル

#### users（ユーザー）
- **主キー**: id
- **説明**: システムの中心テーブル。すべてのユーザー情報を管理
- **特記事項**: Googleカレンダー連携用のトークン情報も保存

#### password_resets（パスワードリセット）
- **主キー**: email
- **説明**: パスワードリセット用のトークンを一時保存

#### personal_access_tokens（アクセストークン）
- **主キー**: id
- **外部キー**: user_id → users.id
- **説明**: Laravel Sanctum用のAPIトークン管理

### 2. イベント関連テーブル

#### categories（カテゴリー）
- **主キー**: id
- **説明**: イベントカテゴリーのマスターテーブル
- **リレーション**: 1 : N → events

#### events（イベント）
- **主キー**: id
- **外部キー**:
  - user_id → users.id（作成者）
  - category_id → categories.id（カテゴリー）
- **説明**: イベント情報の中核テーブル
- **特記事項**:
  - external_url: RSSインポート元のURL保存
  - venue_type: 屋内/屋外の開催タイプ
  - latitude/longitude: 地図表示用の座標
  - ソフトデリート対応（deleted_at）
- **リレーション**:
  - 1 : N → reviews
  - 1 : N → event_favorites
  - 1 : N → buddy_posts

### 3. レビュー関連テーブル

#### reviews（レビュー）
- **主キー**: id
- **外部キー**:
  - event_id → events.id
  - user_id → users.id
- **説明**: イベントに対する評価とコメント
- **特記事項**:
  - rating: 1〜5の整数値（星評価）
  - withAvg('reviews', 'rating')で平均評価を算出
- **リレーション**:
  - 1 : N → review_comments
  - 1 : N → likes

#### review_comments（レビューコメント）
- **主キー**: id
- **外部キー**:
  - review_id → reviews.id
  - user_id → users.id
- **説明**: レビューに対するコメント

#### likes（いいね）
- **主キー**: id
- **外部キー**:
  - review_id → reviews.id
  - user_id → users.id
- **説明**: レビューへのいいね機能

### 4. お気に入り関連テーブル

#### event_favorites（お気に入りイベント）
- **主キー**: id
- **外部キー**:
  - user_id → users.id
  - event_id → events.id
- **説明**: ユーザーがお気に入り登録したイベント

### 5. イベント参加関連テーブル（バディ機能）

#### buddy_posts（バディ募集）
- **主キー**: id
- **外部キー**:
  - event_id → events.id
  - user_id → users.id（募集者）
- **説明**: イベントへの参加者募集投稿
- **特記事項**:
  - max_participants: 最大参加人数
  - status: open/closed（募集状況）
  - ソフトデリート対応
- **リレーション**:
  - 1 : N → buddy_post_participants
  - 1 : 1 → chat_rooms

#### buddy_post_participants（参加者）
- **主キー**: id
- **外部キー**:
  - buddy_post_id → buddy_posts.id
  - user_id → users.id（参加希望者）
- **説明**: バディ募集への参加申請管理
- **特記事項**: status: pending/approved/rejected

### 6. チャット関連テーブル

#### chat_rooms（チャットルーム）
- **主キー**: id
- **外部キー**: buddy_post_id → buddy_posts.id
- **説明**: バディ投稿ごとのチャットルーム
- **リレーション**: 1 : N → chat_messages

#### chat_messages（チャットメッセージ）
- **主キー**: id
- **外部キー**:
  - chat_room_id → chat_rooms.id
  - user_id → users.id（送信者）
- **説明**: チャットメッセージの保存
- **特記事項**: is_read: 既読フラグ

### 7. システム管理テーブル

#### failed_jobs（失敗ジョブ）
- **主キー**: id
- **説明**: バックグラウンド処理で失敗したジョブを記録

## 主要なリレーションシップ

### usersテーブルを中心とした関連
```
users (1) ←→ (N) events
users (1) ←→ (N) reviews
users (1) ←→ (N) review_comments
users (1) ←→ (N) likes
users (1) ←→ (N) event_favorites
users (1) ←→ (N) buddy_posts
users (1) ←→ (N) buddy_post_participants
users (1) ←→ (N) chat_messages
users (1) ←→ (N) personal_access_tokens
```

### eventsテーブルを中心とした関連
```
categories (1) ←→ (N) events
events (1) ←→ (N) reviews
events (1) ←→ (N) event_favorites
events (1) ←→ (N) buddy_posts
```

### レビュー関連の階層構造
```
events (1) ←→ (N) reviews (1) ←→ (N) review_comments
                          (1) ←→ (N) likes
```

### バディ機能の階層構造
```
events (1) ←→ (N) buddy_posts (1) ←→ (N) buddy_post_participants
                              (1) ←→ (1) chat_rooms (1) ←→ (N) chat_messages
```

## データベース設計の特徴

### 1. パフォーマンス最適化
- **Eager Loading**: `with()`, `withAvg()`, `withCount()`でN+1問題を回避
- **インデックス**: 外部キーに自動的にインデックスが設定
- **集計の効率化**: データベース側で平均値・件数を計算

### 2. データ整合性
- **外部キー制約**: すべてのリレーションに制約を設定
- **カスケード削除**: 親データ削除時に関連データも自動削除
- **ソフトデリート**: 主要テーブルで論理削除を実装

### 3. 拡張性
- **中間テーブル**: 多対多の関係を柔軟に管理
- **マスターテーブル**: カテゴリーなどの共通データを一元管理
- **トークン管理**: 外部API連携に対応

### 4. セキュリティ
- **トークンの暗号化**: Googleカレンダートークンを暗号化保存
- **論理削除**: 削除データの復元が可能
- **パスワードハッシュ**: bcryptによる暗号化

## 使用例

### レビュー平均評価の取得
```php
$events = Event::with('category')
    ->withAvg('reviews', 'rating')  // 平均評価
    ->withCount('reviews')          // レビュー数
    ->paginate(12);
```

### RSSインポート時の重複チェック
```php
$exists = Event::where('external_url', $eventData['external_url'])
    ->orWhere(function($query) use ($eventData) {
        $query->where('name', $eventData['name'])
              ->whereDate('start_date', substr($eventData['start_date'], 0, 10));
    })
    ->exists();
```

### バディ参加者とチャットメッセージの取得
```php
$buddyPost = BuddyPost::with([
    'participants',
    'chatRoom.messages.user'
])->find($id);
```
