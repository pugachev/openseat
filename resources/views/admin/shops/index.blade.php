@extends('layouts.app')

@section('title', '店舗一覧（管理者） - Open Seat')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-900">店舗一覧</h2>
        <a href="{{ route('admin.shops.create') }}" 
           class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
            + 新規登録
        </a>
    </div>

    @if($shops->isEmpty())
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <p class="text-gray-600 mb-4">登録されている店舗がありません</p>
            <a href="{{ route('admin.shops.create') }}" 
               class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                最初の店舗を登録する
            </a>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
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
    @endif
</div>
@endsection

