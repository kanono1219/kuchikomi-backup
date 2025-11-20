<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .event-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3490dc;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
    <h2>{{ $user->name }} 様</h2>
    
    <p>お気に入り登録されているイベント「{{ $event->name }}」の開催が{{ $user->notification_days_before }}日後に迫っています。</p>
    
    <div class="event-info">
        <span class="event-category">{{ $event->category->name }}</span>
        
        @if($event->average_rating > 0)
            <div class="rating">
                <span>評価: {{ number_format($event->average_rating, 1) }}</span>
                @if($event->reviews_count > 0)
                    <span>({{ $event->reviews_count }}件のレビュー)</span>
                @endif
            </div>
        @endif
        
        <h3>イベント詳細</h3>
        <p><strong>開催日時：</strong><br>
            {{ $event->start_date->format('Y年m月d日 H:i') }} ～<br>
            {{ $event->end_date->format('Y年m月d日 H:i') }}
        </p>
        
        @if($event->location || $event->address)
            <div class="event-location">
                @if($event->location)
                    <p><strong>会場：</strong> {{ $event->location }}</p>
                @endif
                @if($event->address)
                    <p><strong>住所：</strong> {{ $event->address }}</p>
                @endif
                @if($event->latitude && $event->longitude)
                    <p><a href="https://www.google.com/maps?q={{ $event->latitude }},{{ $event->longitude }}" 
                          target="_blank" class="map-link">
                        地図を表示
                    </a></p>
                @endif
            </div>
        @endif
        
        <p><strong>概要：</strong><br>
            {{ $event->overview }}
        </p>

        @if($event->external_url)
            <p><strong>関連URL：</strong><br>
                <a href="{{ $event->external_url }}">{{ $event->external_url }}</a>
            </p>
        @endif
    </div>

    <a href="{{ route('events.show', $event) }}" class="button">
        イベントの詳細を確認する
    </a>
    
    <div class="footer">
        <p>
            ※ このメールは{{ $user->notification_days_before }}日前の通知設定に基づいて送信されています。<br>
            通知設定は<a href="{{ route('profile.edit') }}">マイページ</a>から変更できます。
        </p>
    </div>
</div>