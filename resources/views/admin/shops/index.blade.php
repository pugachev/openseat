@extends('layouts.app')

@section('title', '店舗一覧（管理者） - Open Seat')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div class="flex gap-3 flex-wrap">
            <a href="{{ route('admin.page_views.index') }}"
               class="bg-emerald-600 text-white px-6 py-3 rounded-lg hover:bg-emerald-700 transition-colors font-medium">
                PVレポート
            </a>
            <button type="button" onclick="document.getElementById('bannerModal').classList.remove('hidden')"
                    class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                投稿
            </button>
            <form action="{{ route('admin.logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                        class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors font-medium">
                    ログアウト
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            店舗名
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            電話番号
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            住所
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ステータス
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            登録日
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            操作
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($shops as $shop)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $shop->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">{{ $shop->phone }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-500">{{ $shop->address }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
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
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">{{ $shop->created_at->format('Y/m/d') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.shops.show', $shop->id) }}" 
                                   class="text-blue-600 hover:text-blue-900">
                                    QRコード
                                </a>
                                <a href="{{ route('admin.shops.edit', $shop->id) }}" 
                                   class="text-indigo-600 hover:text-indigo-900">
                                    編集
                                </a>
                                <form action="{{ route('admin.shops.destroy', $shop->id) }}" 
                                      method="POST" 
                                      class="inline"
                                      onsubmit="return confirm('本当に削除しますか？');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="text-red-600 hover:text-red-900">
                                        削除
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
</div>

<!-- バナー投稿モーダル -->
<div id="bannerModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl p-8 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold text-gray-900 mb-6">バナーを編集</h3>

        <form action="{{ route('admin.banner.update') }}" method="POST">
            @csrf

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">メッセージ</label>
                <input type="text" name="banner_text"
                       value="{{ auth()->user()->banner_text ?? '管理人は開拓中！' }}"
                       maxlength="60"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="mb-5 flex gap-6">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">背景色</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="banner_bg_color"
                               value="{{ auth()->user()->banner_bg_color ?? '#dcfce7' }}"
                               class="h-10 w-16 rounded border border-gray-300 cursor-pointer p-0.5">
                        <span class="text-xs text-gray-500">背景</span>
                    </div>
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">文字色</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="banner_text_color"
                               value="{{ auth()->user()->banner_text_color ?? '#166534' }}"
                               class="h-10 w-16 rounded border border-gray-300 cursor-pointer p-0.5">
                        <span class="text-xs text-gray-500">文字</span>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">プレビュー</label>
                <div class="flex items-center gap-2">
                    <span id="bannerPreview"
                          class="text-sm font-medium px-3 py-1 rounded-full"
                          style="background-color: {{ auth()->user()->banner_bg_color ?? '#dcfce7' }}; color: {{ auth()->user()->banner_text_color ?? '#166534' }};">
                        {{ auth()->user()->banner_text ?? '管理人は開拓中！' }}
                    </span>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button"
                        onclick="document.getElementById('bannerModal').classList.add('hidden')"
                        class="px-5 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium">
                    キャンセル
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 text-sm font-medium">
                    保存
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const textInput  = document.querySelector('input[name="banner_text"]');
    const bgInput    = document.querySelector('input[name="banner_bg_color"]');
    const fgInput    = document.querySelector('input[name="banner_text_color"]');
    const preview    = document.getElementById('bannerPreview');

    function updatePreview() {
        preview.textContent = textInput.value || 'プレビュー';
        preview.style.backgroundColor = bgInput.value;
        preview.style.color = fgInput.value;
    }

    textInput.addEventListener('input', updatePreview);
    bgInput.addEventListener('input', updatePreview);
    fgInput.addEventListener('input', updatePreview);
})();
</script>
@endsection

