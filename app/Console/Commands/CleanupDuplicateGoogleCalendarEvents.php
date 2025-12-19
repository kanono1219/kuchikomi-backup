<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\GoogleCalendarEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupDuplicateGoogleCalendarEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:cleanup-duplicates {--dry-run : 実行せずに確認のみ}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'webアプリのイベントと重複するGoogle Calendarイベントを削除します';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('重複するGoogle Calendarイベントをチェックしています...');

        // webアプリのイベントでGoogleカレンダーに追加されているものを取得
        $webAppGoogleEventIds = Event::whereNotNull('google_calendar_event_id')
            ->pluck('google_calendar_event_id', 'id')
            ->toArray();

        $this->info('Googleカレンダーに追加されたwebアプリのイベント: ' . count($webAppGoogleEventIds) . '件');

        if (empty($webAppGoogleEventIds)) {
            $this->info('重複はありません。');
            return Command::SUCCESS;
        }

        $this->table(
            ['Web App Event ID', 'Google Event ID'],
            collect($webAppGoogleEventIds)->map(function ($googleEventId, $eventId) {
                return [$eventId, $googleEventId];
            })->toArray()
        );

        // Google Calendar Eventsテーブルで重複しているものを検索
        $duplicateEvents = GoogleCalendarEvent::whereIn('google_event_id', $webAppGoogleEventIds)->get();

        $this->warn('重複するGoogle Calendar Events: ' . $duplicateEvents->count() . '件');

        if ($duplicateEvents->count() === 0) {
            $this->info('削除する重複イベントはありません。');
            return Command::SUCCESS;
        }

        // 重複イベントの詳細を表示
        $this->table(
            ['GC Event ID', 'User ID', 'Name', 'Google Event ID', 'Start Date'],
            $duplicateEvents->map(function ($event) {
                return [
                    $event->id,
                    $event->user_id,
                    $event->name,
                    $event->google_event_id,
                    $event->start_date->format('Y-m-d H:i:s'),
                ];
            })->toArray()
        );

        if ($dryRun) {
            $this->info('--dry-run オプションが指定されているため、実際には削除しません。');
            $this->info('削除する場合は、--dry-run オプションを外して再実行してください。');
            return Command::SUCCESS;
        }

        // 確認を求める
        if (!$this->confirm('これらの重複イベントを削除しますか？')) {
            $this->info('キャンセルしました。');
            return Command::SUCCESS;
        }

        // 削除実行
        $deletedCount = 0;
        foreach ($duplicateEvents as $event) {
            try {
                Log::info('Deleting duplicate Google Calendar event', [
                    'gc_event_id' => $event->id,
                    'google_event_id' => $event->google_event_id,
                    'name' => $event->name,
                ]);

                $event->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                $this->error('削除に失敗しました: ' . $event->id . ' - ' . $e->getMessage());
                Log::error('Failed to delete duplicate event', [
                    'gc_event_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("✅ {$deletedCount}件の重複イベントを削除しました。");
        Log::info('Cleanup completed', ['deleted_count' => $deletedCount]);

        return Command::SUCCESS;
    }
}
