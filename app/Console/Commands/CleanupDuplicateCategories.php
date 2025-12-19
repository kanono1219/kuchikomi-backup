<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'categories:cleanup-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '重複しているカテゴリーを削除します';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('重複カテゴリーをチェックしています...');

        // 重複しているカテゴリーを検出
        $duplicates = DB::table('categories')
            ->select('name', DB::raw('COUNT(*) as count'), DB::raw('MIN(id) as keep_id'))
            ->groupBy('name')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('重複カテゴリーはありません。');
            return Command::SUCCESS;
        }

        $this->warn('重複カテゴリーが見つかりました:');

        foreach ($duplicates as $duplicate) {
            $this->line("- {$duplicate->name} ({$duplicate->count}件)");
        }

        // 重複を削除
        $deletedCount = 0;

        foreach ($duplicates as $duplicate) {
            // 最初のID以外を削除
            $deleted = DB::table('categories')
                ->where('name', $duplicate->name)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();

            $deletedCount += $deleted;

            $this->info("'{$duplicate->name}' の重複 {$deleted}件を削除しました (ID: {$duplicate->keep_id} を保持)");
        }

        $this->info("✅ 合計 {$deletedCount}件の重複カテゴリーを削除しました。");

        // 最終的なカテゴリー一覧を表示
        $this->info("\n現在のカテゴリー一覧:");
        $categories = Category::orderBy('id')->get();

        $this->table(
            ['ID', 'カテゴリー名'],
            $categories->map(function ($cat) {
                return [$cat->id, $cat->name];
            })->toArray()
        );

        return Command::SUCCESS;
    }
}
