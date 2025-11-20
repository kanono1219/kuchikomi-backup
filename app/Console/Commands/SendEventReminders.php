<?php

// app/Console/Commands/SendEventReminders.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use App\Mail\EventReminderMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';
    protected $description = 'お気に入りイベントの通知メールを送信';

    public function handle()
    {
        $this->info('イベント通知処理を開始します...');

        // イベントを取得（レビュー情報も含める）
        $upcomingEvents = Event::whereHas('favorites', function ($query) {
            $query->where('event_notifications_enabled', true);
        })
        ->where('start_date', '>', now())
        ->with([
            'favorites' => function ($query) {
                $query->where('event_notifications_enabled', true);
            },
            'category',
            'reviews'
        ])
        ->withAvgRating()
        ->withReviewsCount()
        ->get();

        $sentCount = 0;
        $errorCount = 0;

        foreach ($upcomingEvents as $event) {
            foreach ($event->favorites as $user) {
                $notificationDate = Carbon::parse($event->start_date)
                    ->subDays($user->notification_days_before)
                    ->startOfDay();
                
                if (Carbon::now()->startOfDay()->equalTo($notificationDate)) {
                    try {
                        Mail::to($user->email)->send(new EventReminderMail($event, $user));
                        $sentCount++;
                        
                        $this->info(sprintf(
                            "送信成功: %s -> イベント: %s (開催: %s, カテゴリ: %s)",
                            $user->email,
                            $event->name,
                            $event->start_date->format('Y-m-d H:i'),
                            $event->category->name
                        ));
                        
                        Log::info('イベント通知送信成功', [
                            'event_id' => $event->id,
                            'event_name' => $event->name,
                            'category' => $event->category->name,
                            'user_id' => $user->id,
                            'user_email' => $user->email,
                            'notification_days' => $user->notification_days_before,
                            'average_rating' => $event->average_rating,
                            'reviews_count' => $event->reviews_count
                        ]);
                    } catch (\Exception $e) {
                        $errorCount++;
                        
                        $this->error(sprintf(
                            "送信失敗: %s -> イベント: %s\nエラー: %s",
                            $user->email,
                            $event->name,
                            $e->getMessage()
                        ));
                        
                        Log::error('イベント通知送信失敗', [
                            'event_id' => $event->id,
                            'event_name' => $event->name,
                            'user_id' => $user->id,
                            'user_email' => $user->email,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        $summary = sprintf(
            "処理完了: 成功=%d件, 失敗=%d件, 合計=%d件",
            $sentCount,
            $errorCount,
            $sentCount + $errorCount
        );
        
        $this->info($summary);
        Log::info($summary);
    }
}