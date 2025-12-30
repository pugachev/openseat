@extends('layouts.app')

@section('title', '店舗登録完了 - Open Seat')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">店舗登録が完了しました</h2>
            <p class="text-gray-600">{{ $shop->name }}</p>
        </div>

        <div class="grid md:grid-cols-2 gap-8 mb-8">
            <!-- QRコード表示 -->
            <div class="bg-gray-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 text-center">QRコード</h3>
                <div class="flex justify-center mb-4">
                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        {!! $qrCode !!}
                    </div>
                </div>
                <p class="text-sm text-gray-600 text-center">
                    このQRコードを印刷して店舗に設置してください<br>
                    スマートフォンで読み取るとステータス更新画面が開きます
                </p>
            </div>

            <!-- URL表示 -->
            <div class="bg-gray-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">アクセスURL</h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">ステータス更新用URL</label>
                    <div class="flex gap-2">
                        <input type="text" 
                               id="controlUrl" 
                               value="{{ $controlUrl }}" 
                               readonly
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm">
                        <button onclick="copyUrl()" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                            コピー
                        </button>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ $controlUrl }}" 
                       target="_blank"
                       class="block w-full text-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                        プレビューを開く
                    </a>
                </div>
            </div>
        </div>

        <!-- 店舗情報 -->
        <div class="border-t border-gray-200 pt-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">登録された店舗情報</h3>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">店舗名</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $shop->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">電話番号</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $shop->phone }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">住所</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $shop->address }}</dd>
                </div>
                @if($shop->latitude && $shop->longitude)
                <div>
                    <dt class="text-sm font-medium text-gray-500">緯度</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $shop->latitude }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">経度</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $shop->longitude }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-sm font-medium text-gray-500">現在のステータス</dt>
                    <dd class="mt-1">
                        @if($shop->status === 0)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                🟢 空き
                            </span>
                        @elseif($shop->status === 1)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                🟡 待ち
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                🔴 満席
                            </span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <!-- アクションボタン -->
        <div class="flex gap-4 pt-6 border-t border-gray-200">
            <a href="{{ route('admin.shops.index') }}" 
               class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium text-center">
                店舗一覧に戻る
            </a>
            <a href="{{ route('admin.shops.edit', $shop->id) }}" 
               class="flex-1 bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors font-medium text-center">
                店舗情報を編集
            </a>
            <button onclick="window.print()" 
                    class="flex-1 bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors font-medium">
                印刷する
            </button>
        </div>
    </div>
</div>

<script>
function copyUrl() {
    const urlInput = document.getElementById('controlUrl');
    urlInput.select();
    urlInput.setSelectionRange(0, 99999); // モバイル対応
    
    try {
        document.execCommand('copy');
        alert('URLをクリップボードにコピーしました');
    } catch (err) {
        // モダンブラウザの場合は Clipboard API を使用
        if (navigator.clipboard) {
            navigator.clipboard.writeText(urlInput.value).then(() => {
                alert('URLをクリップボードにコピーしました');
            });
        } else {
            alert('コピーに失敗しました。手動でコピーしてください。');
        }
    }
}
</script>

<style>
@media print {
    body {
        background: white;
    }
    .bg-white {
        box-shadow: none;
    }
    button, a {
        display: none;
    }
    .bg-gray-50 {
        background: white !important;
        border: 1px solid #e5e7eb;
    }
}
</style>
@endsection

