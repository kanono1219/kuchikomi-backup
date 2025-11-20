<!DOCTYPE HTML>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>イベント作成</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&libraries=places" async defer></script>
</head>
<x-app-layout>
    <body class="bg-gray-100">
        <div class="container mx-auto px-4 py-8">
            <h1 class="text-3xl font-bold mb-6 text-gray-800">イベント作成</h1>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">エラーが発生しました！</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="/events" method="POST" enctype="multipart/form-data" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                @csrf
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                        イベント名
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" type="text" name="event[name]" placeholder="イベント名" value="{{ old('event.name') }}">
                    @error('event.name')
                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="overview">
                        内容
                    </label>
                    <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="overview" name="event[overview]" placeholder="イベントの内容を書いてください" rows="4">{{ old('event.overview') }}</textarea>
                    @error('event.overview')
                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="category">
                        イベントカテゴリー
                    </label>
                    <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="category" name="event[category_id]">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('event.category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('event.category_id')
                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="venue_type">
                    会場タイプ 
                </label>
                <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="venue_type" name="event[venue_type]">
                    <option value="indoor" {{ old('event.venue_type') == 'indoor' ? 'selected' : '' }}>室内</option>
                    <option value="outdoor" {{ old('event.venue_type') == 'outdoor' ? 'selected' : '' }}>屋外</option>
                </select>
                @error('event.venue_type')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="location">
                        開催場所
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="autocomplete" type="text" name="event[location]" placeholder="開催場所を検索または地図上でクリック" value="{{ old('event.location') }}">
                    @error('event.location')
                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="address">
                        住所
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="address" type="text" name="event[address]" placeholder="住所" value="{{ old('event.address') }}">
                    @error('event.address')
                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- 地図を表示する div 要素 -->
                <div id="map" style="height: 400px; width: 100%; margin-bottom: 20px;"></div>

                <input type="hidden" id="latitude" name="event[latitude]" value="{{ old('event.latitude') }}">
                <input type="hidden" id="longitude" name="event[longitude]" value="{{ old('event.longitude') }}">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="start_date">
                        開始日時
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="start_date" type="datetime-local" name="event[start_date]" value="{{ old('event.start_date') }}">
                    @error('event.start_date')
                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="end_date">
                        終了日時
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="end_date" type="datetime-local" name="event[end_date]" value="{{ old('event.end_date') }}">
                    @error('event.end_date')
                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="image">
                        イベント画像
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="image" type="file" name="image">
                    @error('image')
                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="external_url" class="block text-gray-700 text-sm font-bold mb-2">外部リンク（任意）</label>
                    <input type="url" name="event[external_url]" id="external_url" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="{{ old('event.external_url') }}">
                    @error('event.external_url')
                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-center justify-start space-x-4">
                    <a href="/" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        戻る
                    </a>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        作成
                    </button>
                </div>
            </form>
        </div>

        <script>
            let map;
let marker;
let autocomplete;

function initMap() {
    map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: 26.2124, lng: 127.6809 }, // 沖縄の中心付近
        zoom: 10
    });

    autocomplete = new google.maps.places.Autocomplete(
        document.getElementById('autocomplete'),
        { types: ['establishment', 'geocode'] }
    );

    autocomplete.bindTo('bounds', map);

    autocomplete.addListener('place_changed', function() {
        fillInAddress(function(locationName, address, lat, lng) {
            document.getElementById('autocomplete').value = locationName;
            document.getElementById('address').value = address;
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
        });
    });

    map.addListener('click', function (e) {
        placeMarkerAndPanTo(e.latLng, map, function(locationName, address, lat, lng) {
            document.getElementById('autocomplete').value = locationName;
            document.getElementById('address').value = address;
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
        });
    });
}

function fillInAddress(callback) {
    const place = autocomplete.getPlace();
    if (!place.geometry) {
        return;
    }

    if (place.geometry.viewport) {
        map.fitBounds(place.geometry.viewport);
    } else {
        map.setCenter(place.geometry.location);
        map.setZoom(17);
    }

    let locationName = place.name || '';
    let address = place.formatted_address || '';

    callback(locationName, address, place.geometry.location.lat(), place.geometry.location.lng());
    placeMarkerAndPanTo(place.geometry.location, map);
}

function placeMarkerAndPanTo(latLng, map, callback) {
    if (marker) {
        marker.setMap(null);
    }
    marker = new google.maps.Marker({
        position: latLng,
        map: map
    });
    map.panTo(latLng);

    const geocoder = new google.maps.Geocoder();
    geocoder.geocode({ location: latLng }, function(results, status) {
        let locationName = "";
        let address = "";

        if (status === "OK" && results[0]) {
            address = results[0].formatted_address;

            for (let result of results) {
                if (result.types.includes('establishment') || result.types.includes('point_of_interest')) {
                    locationName = result.name;
                    break;
                }
            }

            if (!locationName) {
                locationName = `地点: ${latLng.lat()}, ${latLng.lng()}`;
            }
        } else {
            locationName = `地点: ${latLng.lat()}, ${latLng.lng()}`;
            address = "住所不明";
        }

        if (callback) {
            callback(locationName, address, latLng.lat(), latLng.lng());
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    initMap();
});
        </script>
    </body>
</x-app-layout>
</html>