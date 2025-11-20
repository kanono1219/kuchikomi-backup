<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    public function rules()
    {
        return [
            'event.name' => 'required|string|max:255',
            'event.overview' => 'required|string',
            'event.category_id' => 'required|exists:categories,id',
            'event.location' => 'required|string|max:255',
            'event.address' => 'required|string|max:255',
            'event.latitude' => 'required|numeric',
            'event.longitude' => 'required|numeric',
            'event.start_date' => 'required|date',
            'event.end_date' => 'required|date|after_or_equal:event.start_date',
            'event.venue_type' => 'required|in:indoor,outdoor',  // 追加
            'image' => 'nullable|image|max:2048',
            'event.external_url' => 'nullable|url',
        ];
    }

    public function messages()
    {
        return [
            'event.name.required' => 'イベント名は必須です。',
            'event.overview.required' => 'イベントの概要は必須です。',
            'event.category_id.required' => 'カテゴリーの選択は必須です。',
            'event.location.required' => '開催場所は必須です。',
            'event.start_date.required' => '開始日時は必須です。',
            'event.end_date.required' => '終了日時は必須です。',
            'event.end_date.after_or_equal' => '終了日時は開始日時以降である必要があります。',
            'event.venue_type.required' => '会場タイプは必須です。',  // 追加
            'event.venue_type.in' => '会場タイプは室内または屋外のいずれかを選択してください。',  // 追加
            'event.external_url.url' => '有効なURLを入力してください。',
            'image.image' => '画像ファイルをアップロードしてください。',
            'image.max' => '画像ファイルは2MB以下である必要があります。',
        ];
    }
}