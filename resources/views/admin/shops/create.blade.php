@extends('layouts.app')

@section('title', '店舗新規登録 - Open Seat')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">店舗新規登録</h2>
            <p class="text-gray-600">店舗情報を入力してください</p>
        </div>

        <form action="{{ route('admin.shops.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- 店舗名 -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    店舗名 <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="{{ old('name') }}"
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
                       value="{{ old('phone') }}"
                       required
                       placeholder="例: 078-123-4567"
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
                       value="{{ old('address') }}"
                       required
                       placeholder="例: 兵庫県神戸市中央区三宮町1-1-1"
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
                       value="{{ old('latitude') }}"
                       step="0.00000001"
                       min="-90"
                       max="90"
                       placeholder="例: 34.6901"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-xs text-gray-500">地図表示に使用されます。未入力でも登録可能です。</p>
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
                       value="{{ old('longitude') }}"
                       step="0.00000001"
                       min="-180"
                       max="180"
                       placeholder="例: 135.1956"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-xs text-gray-500">地図表示に使用されます。未入力でも登録可能です。</p>
                @error('longitude')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- 初期ステータス -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    初期ステータス
                </label>
                <select id="status" 
                        name="status"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="0" {{ old('status', 0) == 0 ? 'selected' : '' }}>🟢 空き</option>
                    <option value="1" {{ old('status') == 1 ? 'selected' : '' }}>🟡 待ち</option>
                    <option value="2" {{ old('status') == 2 ? 'selected' : '' }}>🔴 満席</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- ボタン -->
            <div class="flex gap-4 pt-4">
                <button type="submit" 
                        class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    登録する
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

