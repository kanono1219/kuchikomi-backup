#!/bin/bash

echo "========================================="
echo "Google Calendar 診断スクリプト"
echo "========================================="
echo ""

# 1. マイグレーション状態確認
echo "1. マイグレーション状態を確認中..."
php artisan migrate:status | grep google_calendar

echo ""

# 2. データベースのレコード数確認
echo "2. データベースのレコード数を確認中..."
php artisan tinker --execute="
echo 'Google Calendar Events: ' . \App\Models\GoogleCalendarEvent::count();
echo PHP_EOL;
echo 'Users with Google Calendar connected: ' . \App\Models\User::where('google_calendar_connected', true)->count();
"

echo ""

# 3. 最新のログを確認
echo "3. 最新のログを確認中（最後の20行）..."
tail -n 20 storage/logs/laravel.log | grep -i "google"

echo ""
echo "========================================="
echo "診断完了"
echo "========================================="
