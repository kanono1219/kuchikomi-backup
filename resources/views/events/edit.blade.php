<!DOCTYPE HTML>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>イベント編集</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<x-app-layout>
    <body class="bg-gray-100">
        <div class="container mx-auto px-4 py-8">
            <h1 class="text-3xl font-bold mb-6 text-gray-800">イベント編集</h1>
            <form action="/events/{{ $event->id }}" method="POST" enctype="multipart/form-data" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                        イベント名
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" type="text" name="event[name]" placeholder="イベント名" value="{{ $event->name }}">
                    <p class="text-red-500 text-xs italic">{{ $errors->first('event.name') }}</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="overview">
                        内容
                    </label>
                    <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="overview" name="event[overview]" placeholder="イベントの内容を書いてください" rows="4">{{ $event->overview }}</textarea>
                    <p class="text-red-500 text-xs italic">{{ $errors->first('event.overview') }}</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="category">
                        イベントカテゴリー
                    </label>
                    <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="category" name="event[category_id]">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $event->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="venue_type">
                        会場タイプ 
                    </label>
                    <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="venue_type" name="event[venue_type]">
                        <option value="indoor" {{ $event->venue_type == 'indoor' ? 'selected' : '' }}>室内</option>
                        <option value="outdoor" {{ $event->venue_type == 'outdoor' ? 'selected' : '' }}>屋外</option>
                    </select>
                    <p class="text-red-500 text-xs italic">{{ $errors->first('event.venue_type') }}</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="location">
                        開催場所
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="autocomplete" type="text" name="event[location]" placeholder="開催場所" value="{{ $event->location }}">
                    <p class="text-red-500 text-xs italic">{{ $errors->first('event.location') }}</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="address">
                        住所
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="address" type="text" name="event[address]" placeholder="住所" value="{{ $event->address }}">
                    <p class="text-red-500 text-xs italic">{{ $errors->first('event.address') }}</p>
                </div>
                <input type="hidden" id="latitude" name="event[latitude]" value="{{ $event->latitude }}">
                <input type="hidden" id="longitude" name="event[longitude]" value="{{ $event->longitude }}">
                
                <!-- 地図を表示する div 要素 -->
                <div id="map" class="w-full h-64 mb-4 rounded-lg shadow-md"></div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="start_date">
                        開始日時
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="start_date" type="datetime-local" name="event[start_date]" value="{{ $event->start_date ? date('Y-m-d\TH:i', strtotime($event->start_date)) : '' }}">
                    <p class="text-red-500 text-xs italic">{{ $errors->first('event.start_date') }}</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="end_date">
                        終了日時
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="end_date" type="datetime-local" name="event[end_date]" value="{{ $event->end_date ? date('Y-m-d\TH:i', strtotime($event->end_date)) : '' }}">
                    <p class="text-red-500 text-xs italic">{{ $errors->first('event.end_date') }}</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="image">
                        イベント画像
                    </label>
                    @if($event->image_url)
                        <img src="{{ $event->image_url }}" alt="イベント画像" class="mb-2 max-w-xs">
                    @endif
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="image" type="file" name="image">
                    <p class="text-red-500 text-xs italic">{{ $errors->first('image') }}</p>
                </div>
                <div class="mb-4">
                    <label for="external_url" class="block text-gray-700 text-sm font-bold mb-2">外部リンク（任意）</label>
                    <input type="url" name="event[external_url]" id="external_url" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="{{ old('event.external_url', $event->external_url ?? '') }}">
                </div>
                <div class="flex items-center justify-start space-x-4">
                    <a href="/" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        戻る
                    </a>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        更新
                    </button>
                </div>
            </form>
        </div>

        <script>
            let map;
            let marker;
            let autocomplete;

            function initMap() {
                const eventLocation = {
                    lat: {{ $event->latitude ?? 26.2124 }},
                    lng: {{ $event->longitude ?? 127.6809 }}
                };

                map = new google.maps.Map(document.getElementById('map'), {
                    center: eventLocation,
                    zoom: 15
                });

                marker = new google.maps.Marker({
                    position: eventLocation,
                    map: map,
                    draggable: true
                });

                autocomplete = new google.maps.places.Autocomplete(
                    document.getElementById('autocomplete'),
                    {types: ['establishment']}
                );

                autocomplete.bindTo('bounds', map);

                autocomplete.addListener('place_changed', fillInAddress);

                marker.addListener('dragend', function() {
                    const position = marker.getPosition();
                    updateLatLng(position.lat(), position.lng());
                    geocodePosition(position);
                });

                google.maps.event.addListener(map, 'click', function(event) {
                    placeMarker(event.latLng);
                });
            }

            function fillInAddress() {
                const place = autocomplete.getPlace();
                if (!place.geometry) {
                    return;
                }

                map.setCenter(place.geometry.location);
                marker.setPosition(place.geometry.location);

                updateLatLng(place.geometry.location.lat(), place.geometry.location.lng());

                document.getElementById('autocomplete').value = place.name;
                document.getElementById('address').value = place.formatted_address;
            }

            function placeMarker(location) {
                marker.setPosition(location);
                map.setCenter(location);
                updateLatLng(location.lat(), location.lng());
                geocodePosition(location);
            }

            function updateLatLng(lat, lng) {
                document.getElementById('latitude').value = lat;
                document.getElementById('longitude').value = lng;
            }

            function geocodePosition(pos) {
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({
                    latLng: pos
                }, function(responses) {
                    if (responses && responses.length > 0) {
                        document.getElementById('address').value = responses[0].formatted_address;
                    } else {
                        document.getElementById('address').value = 'Cannot determine address at this location.';
                    }
                });
            }

            function loadMapScript() {
                const script = document.createElement('script');
                script.src = 'https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&libraries=places&callback=initMap';
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            }

            document.addEventListener('DOMContentLoaded', loadMapScript);
        </script>
    </body>
</x-app-layout>
</html>