<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RssImporterController extends Controller
{
    public function index()
    {
        $suggestedFeeds = $this->getSuggestedFeeds();
        return view('rss-importer.index', compact('suggestedFeeds'));
    }

    public function preview(Request $request)
    {
        $request->validate([
            'feed_url' => 'required|url',
        ]);

        try {
            $response = Http::timeout(10)->get($request->feed_url);
            $body = (string)$response->getBody();

            // XMLパーサーのエラーをキャッチ
            libxml_use_internal_errors(true);
            libxml_clear_errors();
            
            $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOENT);
            
            if ($xml === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                throw new \Exception('XMLパースエラー: フォーマットが正しくありません');
            }

            $events = [];
            $rootName = $xml->getName();
            
            if ($rootName === 'rss') {
                $events = $this->parseRSS($xml);
            } elseif ($rootName === 'feed') {
                $events = $this->parseAtom($xml);
            } else {
                throw new \Exception('サポートされていないフォーマットです');
            }

            if (empty($events)) {
                return back()->with('error', 'このフィードからイベント情報を取得できません');
            }

            $feedUrl = $request->feed_url;
            return view('rss-importer.preview', compact('events', 'feedUrl'));

        } catch (\Exception $e) {
            return back()->with('error', 'フィード取得エラー: ' . $e->getMessage());
        }
    }

    private function parseRSS($xml)
    {
        $events = [];
        $categories = Category::pluck('id', 'name');
        $defaultCategoryId = $categories->get('RSS配信', $categories->get('その他', 1));

        try {
            if (isset($xml->channel->item)) {
                foreach ($xml->channel->item as $item) {
                    $title = trim((string)$item->title);
                    $description = trim((string)$item->description);
                    $link = trim((string)$item->link);

                    if (empty($title)) {
                        continue;
                    }

                    // 場所情報の抽出
                    $location = 'オンライン';
                    $address = '';
                    $latitude = 26.2124;
                    $longitude = 127.6809;

                    // ev:location タグから場所を取得
                    if (isset($item->children('ev', true)->location)) {
                        $location = trim((string)$item->children('ev', true)->location);
                    }

                    // geo:lat, geo:long から位置情報を取得
                    if (isset($item->children('geo', true)->lat)) {
                        $latitude = (float)$item->children('geo', true)->lat;
                    }
                    if (isset($item->children('geo', true)->long)) {
                        $longitude = (float)$item->children('geo', true)->long;
                    }

                    // description から場所情報を抽出
                    $plainDescription = strip_tags($description);
                    if (preg_match('/(?:場所|会場|開催地)[:：]\s*([^\n]+)/u', $plainDescription, $matches)) {
                        $extractedLocation = trim($matches[1]);
                        if (!empty($extractedLocation)) {
                            $location = mb_substr($extractedLocation, 0, 255);
                        }
                    }

                    // 日時情報の抽出
                    $startDate = Carbon::now();
                    $endDate = null;

                    // ev:startdate, ev:enddate から日時を取得
                    if (isset($item->children('ev', true)->startdate)) {
                        try {
                            $startDate = Carbon::parse((string)$item->children('ev', true)->startdate);
                        } catch (\Exception $e) {
                            \Log::warning('Failed to parse ev:startdate: ' . $e->getMessage());
                        }
                    }

                    if (isset($item->children('ev', true)->enddate)) {
                        try {
                            $endDate = Carbon::parse((string)$item->children('ev', true)->enddate);
                        } catch (\Exception $e) {
                            \Log::warning('Failed to parse ev:enddate: ' . $e->getMessage());
                        }
                    }

                    // pubDateから日時を取得
                    if (!empty($item->pubDate) && !isset($item->children('ev', true)->startdate)) {
                        try {
                            $startDate = Carbon::createFromFormat('D, d M Y H:i:s O', (string)$item->pubDate);
                        } catch (\Exception $e) {
                            try {
                                $startDate = Carbon::parse((string)$item->pubDate);
                            } catch (\Exception $e2) {
                                $startDate = Carbon::now();
                            }
                        }
                    }

                    // description から日時を抽出
                    if (preg_match('/(\d{4})年(\d{1,2})月(\d{1,2})日/', $plainDescription, $dateMatches)) {
                        try {
                            $extractedDate = Carbon::create($dateMatches[1], $dateMatches[2], $dateMatches[3]);
                            if (preg_match('/(\d{1,2}):(\d{2})/', $plainDescription, $timeMatches)) {
                                $extractedDate->setTime($timeMatches[1], $timeMatches[2]);
                            }
                            $startDate = $extractedDate;
                        } catch (\Exception $e) {
                            // エラーは無視
                        }
                    }

                    // 終了日時が設定されていない場合
                    if (!$endDate) {
                        $endDate = $startDate->copy()->addHours(2);
                    }

                    // venue_typeの判定
                    $venueType = 'indoor';
                    if (stripos($location, '屋外') !== false || stripos($location, '公園') !== false) {
                        $venueType = 'outdoor';
                    }

                    $events[] = [
                        'name' => mb_substr($title, 0, 255),
                        'overview' => mb_substr($description, 0, 1000),
                        'external_url' => $link,
                        'start_date' => $startDate->format('Y-m-d H:i:s'),
                        'end_date' => $endDate->format('Y-m-d H:i:s'),
                        'category_id' => $defaultCategoryId,
                        'location' => $location,
                        'address' => $address,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'venue_type' => $venueType,
                    ];

                    if (count($events) >= 20) {
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('RSS Parse Error: ' . $e->getMessage());
        }

        return $events;
    }

    private function parseAtom($xml)
    {
        $events = [];
        $categories = Category::pluck('id', 'name');
        $defaultCategoryId = $categories->get('RSS配信', $categories->get('その他', 1));

        try {
            if (isset($xml->entry)) {
                foreach ($xml->entry as $entry) {
                    $title = trim((string)$entry->title);
                    $content = isset($entry->content) ? trim((string)$entry->content) : trim((string)$entry->summary);
                    $link = '';

                    if (empty($title)) {
                        continue;
                    }

                    // リンク取得
                    if (isset($entry->link)) {
                        foreach ($entry->link as $l) {
                            $href = (string)$l->attributes()->href;
                            if (!empty($href)) {
                                $link = $href;
                                break;
                            }
                        }
                    }

                    // 場所情報の抽出
                    $location = 'オンライン';
                    $address = '';
                    $latitude = 26.2124;
                    $longitude = 127.6809;

                    $plainContent = strip_tags($content);
                    if (preg_match('/(?:場所|会場|開催地)[:：]\s*([^\n]+)/u', $plainContent, $matches)) {
                        $extractedLocation = trim($matches[1]);
                        if (!empty($extractedLocation)) {
                            $location = mb_substr($extractedLocation, 0, 255);
                        }
                    }

                    // 日時情報の抽出
                    $startDate = Carbon::now();
                    $endDate = null;

                    if (!empty($entry->published)) {
                        try {
                            $startDate = Carbon::parse((string)$entry->published);
                        } catch (\Exception $e) {
                            $startDate = Carbon::now();
                        }
                    }

                    // contentから日時を抽出
                    if (preg_match('/(\d{4})年(\d{1,2})月(\d{1,2})日/', $plainContent, $dateMatches)) {
                        try {
                            $extractedDate = Carbon::create($dateMatches[1], $dateMatches[2], $dateMatches[3]);
                            if (preg_match('/(\d{1,2}):(\d{2})/', $plainContent, $timeMatches)) {
                                $extractedDate->setTime($timeMatches[1], $timeMatches[2]);
                            }
                            $startDate = $extractedDate;
                        } catch (\Exception $e) {
                            // エラーは無視
                        }
                    }

                    if (!$endDate) {
                        $endDate = $startDate->copy()->addHours(2);
                    }

                    // venue_typeの判定
                    $venueType = 'indoor';
                    if (stripos($location, '屋外') !== false || stripos($location, '公園') !== false) {
                        $venueType = 'outdoor';
                    }

                    $events[] = [
                        'name' => mb_substr($title, 0, 255),
                        'overview' => mb_substr($plainContent, 0, 1000),
                        'external_url' => $link,
                        'start_date' => $startDate->format('Y-m-d H:i:s'),
                        'end_date' => $endDate->format('Y-m-d H:i:s'),
                        'category_id' => $defaultCategoryId,
                        'location' => $location,
                        'address' => $address,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'venue_type' => $venueType,
                    ];

                    if (count($events) >= 20) {
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Atom Parse Error: ' . $e->getMessage());
        }

        return $events;
    }

    public function import(Request $request)
    {
        $request->validate([
            'events' => 'required|array',
        ]);

        $imported = 0;
        $errors = [];

        foreach ($request->events as $eventJson) {
            try {
                $eventData = json_decode($eventJson, true);

                if (!$eventData) {
                    continue;
                }

                $exists = Event::where('external_url', $eventData['external_url'])
                    ->orWhere(function($query) use ($eventData) {
                        $query->where('name', $eventData['name'])
                              ->whereDate('start_date', substr($eventData['start_date'], 0, 10));
                    })
                    ->exists();

                if ($exists) {
                    $errors[] = $eventData['name'] . ': 既に登録されています';
                    continue;
                }

                Event::create([
                    'name' => $eventData['name'],
                    'overview' => $eventData['overview'],
                    'category_id' => $eventData['category_id'],
                    'location' => $eventData['location'],
                    'start_date' => $eventData['start_date'],
                    'end_date' => $eventData['end_date'],
                    'external_url' => $eventData['external_url'],
                    'venue_type' => $eventData['venue_type'],
                    'latitude' => 26.2124,
                    'longitude' => 127.6809,
                    'user_id' => Auth::id(),
                ]);

                $imported++;

            } catch (\Exception $e) {
                \Log::error('Import Error: ' . $e->getMessage());
                $errors[] = 'エラーが発生しました';
            }
        }

        $message = $imported . '件のイベントをインポートしました';
        if (!empty($errors)) {
            $message .= ' (' . count($errors) . '件エラー)';
        }

        return redirect()->route('index')
            ->with('success', $message);
    }

    private function getSuggestedFeeds()
    {
        return [
            [
                'name' => 'BBC News World',
                'url' => 'https://feeds.bbci.co.uk/news/world/rss.xml',
                'description' => 'テスト用フィード（BBC News）'
            ],
            [
                'name' => 'New York Times World',
                'url' => 'https://rss.nytimes.com/services/xml/rss/nyt/World.xml',
                'description' => 'テスト用フィード（New York Times）'
            ],
            [
                'name' => 'NPR News',
                'url' => 'https://feeds.npr.org/1001/rss.xml',
                'description' => 'テスト用フィード（NPR）'
            ],
        ];
    }
}