<?php

namespace App\Http\Controllers;

use App\Models\RssFeed;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class RssFeedController extends Controller
{
    /**
     * RSS配信一覧を表示
     */
    public function index()
    {
        $rssFeeds = RssFeed::with('category')->orderBy('created_at', 'desc')->get();

        return view('admin.rss-feeds.index', compact('rssFeeds'));
    }

    /**
     * RSS配信作成フォームを表示
     */
    public function create()
    {
        $categories = Category::all();

        return view('admin.rss-feeds.create', compact('categories'));
    }

    /**
     * RSS配信を作成
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'category_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
            'fetch_interval' => 'integer|min:60',
        ]);

        RssFeed::create($validated);

        return redirect()->route('admin.rss-feeds.index')
            ->with('success', 'RSS配信を登録しました。');
    }

    /**
     * RSS配信編集フォームを表示
     */
    public function edit(RssFeed $rssFeed)
    {
        $categories = Category::all();

        return view('admin.rss-feeds.edit', compact('rssFeed', 'categories'));
    }

    /**
     * RSS配信を更新
     */
    public function update(Request $request, RssFeed $rssFeed)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'category_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
            'fetch_interval' => 'integer|min:60',
        ]);

        $rssFeed->update($validated);

        return redirect()->route('admin.rss-feeds.index')
            ->with('success', 'RSS配信を更新しました。');
    }

    /**
     * RSS配信を削除
     */
    public function destroy(RssFeed $rssFeed)
    {
        $rssFeed->delete();

        return redirect()->route('admin.rss-feeds.index')
            ->with('success', 'RSS配信を削除しました。');
    }

    /**
     * 手動でRSSを取得
     */
    public function fetch(RssFeed $rssFeed)
    {
        try {
            $result = $this->fetchAndParseRss($rssFeed);

            if ($result['success']) {
                return back()->with('success', $result['message']);
            } else {
                return back()->with('error', $result['message']);
            }
        } catch (Exception $e) {
            Log::error('RSS fetch failed', [
                'rss_feed_id' => $rssFeed->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'RSS取得に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * RSSを取得してパースし、イベントとして登録
     */
    private function fetchAndParseRss(RssFeed $rssFeed): array
    {
        try {
            // RSS URLの内容を取得
            $rssContent = @file_get_contents($rssFeed->url);

            if ($rssContent === false) {
                return [
                    'success' => false,
                    'message' => 'RSSフィードの取得に失敗しました。',
                    'imported_count' => 0
                ];
            }

            // XMLをパース
            $xml = @simplexml_load_string($rssContent);

            if ($xml === false) {
                return [
                    'success' => false,
                    'message' => 'RSSフィードのパースに失敗しました。',
                    'imported_count' => 0
                ];
            }

            $importedCount = 0;

            // RSSアイテムを処理
            foreach ($xml->channel->item as $item) {
                $title = (string) $item->title;
                $description = (string) $item->description;
                $link = (string) $item->link;
                $pubDate = isset($item->pubDate) ? Carbon::parse((string) $item->pubDate) : now();

                // 既存のイベントチェック（タイトルとリンクで重複確認）
                $existingEvent = Event::where('name', $title)
                    ->where('official_site', $link)
                    ->first();

                if (!$existingEvent) {
                    Event::create([
                        'name' => $title,
                        'overview' => $description,
                        'official_site' => $link,
                        'category_id' => $rssFeed->category_id,
                        'user_id' => auth()->id(),
                        'start_date' => $pubDate,
                        'end_date' => $pubDate->copy()->addHours(2), // デフォルト2時間後
                        'location' => '未設定',
                        'address' => '',
                        'venue_type' => 'indoor', // デフォルト室内
                    ]);

                    $importedCount++;
                }
            }

            // 最終取得時刻を更新
            $rssFeed->update(['last_fetched_at' => now()]);

            return [
                'success' => true,
                'message' => "{$importedCount}件のイベントをインポートしました。",
                'imported_count' => $importedCount
            ];
        } catch (Exception $e) {
            Log::error('RSS parse error', [
                'rss_feed_id' => $rssFeed->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'RSS処理中にエラーが発生しました: ' . $e->getMessage(),
                'imported_count' => 0
            ];
        }
    }
}
