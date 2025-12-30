@extends('layouts.app')

@section('title', '店舗編集 - Open Seat')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">店舗情報を編集</h2>
            <p class="text-gray-600">{{ $shop->name }}</p>
        </div>

        <form action="{{ route('admin.shops.update', $shop->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- 店舗名 -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    店舗名 <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="{{ old('name', $shop->name) }}"
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- 電話番号 -->
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                    電話番号 <span class="text-red-500">*</span>
                </label>
                <input type="tel" 
                       id="phone" 
                       name="phone" 
                       value="{{ old('phone', $shop->phone) }}"
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- 住所 -->
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                    住所 <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="address" 
                       name="address" 
                       value="{{ old('address', $shop->address) }}"
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- 緯度 -->
            <div>
                <label for="latitude" class="block text-sm font-medium text-gray-700 mb-2">
                    緯度（オプション）
                </label>
                <input type="number" 
                       id="latitude" 
                       name="latitude" 
                       value="{{ old('latitude', $shop->latitude) }}"
                       step="0.00000001"
                       min="-90"
                       max="90"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('latitude')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- 経度 -->
            <div>
                <label for="longitude" class="block text-sm font-medium text-gray-700 mb-2">
                    経度（オプション）
                </label>
                <input type="number" 
                       id="longitude" 
                       name="longitude" 
                       value="{{ old('longitude', $shop->longitude) }}"
                       step="0.00000001"
                       min="-180"
                       max="180"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('longitude')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- ステータス -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    ステータス
                </label>
                <select id="status" 
                        name="status"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="0" {{ old('status', $shop->status) == 0 ? 'selected' : '' }}>🟢 空き</option>
                    <option value="1" {{ old('status', $shop->status) == 1 ? 'selected' : '' }}>🟡 待ち</option>
                    <option value="2" {{ old('status', $shop->status) == 2 ? 'selected' : '' }}>🔴 満席</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- ボタン -->
            <div class="flex gap-4 pt-4">
                <button type="submit" 
                        class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    更新する
                </button>
                <a href="{{ route('admin.shops.index') }}" 
                   class="flex-1 bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors font-medium text-center">
                    キャンセル
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

