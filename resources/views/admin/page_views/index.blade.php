@extends('layouts.app')

@section('title', 'PVレポート - Open Seat')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <h2 class="text-2xl font-bold text-gray-900">PVレポート</h2>
        <div class="flex gap-3 flex-wrap">
            <a href="{{ route('admin.shops.index') }}"
               class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors font-medium">
                店舗一覧へ戻る
            </a>
            <form action="{{ route('admin.logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                        class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors font-medium">
                    ログアウト
                </button>
            </form>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3 mb-6">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <p class="text-sm text-gray-500">本日の総PV</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($todayTotal) }}</p>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 lg:col-span-2">
            <p class="text-sm text-gray-500 mb-3">直近14日の日別PV</p>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">日付</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">PV</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($last14Days as $day)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-700">{{ \Illuminate\Support\Carbon::parse($day->view_date)->format('Y/m/d') }}</td>
                                <td class="px-4 py-2 text-sm text-gray-900 text-right font-semibold">{{ number_format($day->total_count) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-8 text-center text-sm text-gray-500">まだPVデータがありません</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">本日のページ別PV（上位50件）</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ページ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ルート名</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">PV</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($todayRows as $row)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $row->path }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $row->route_name ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 text-right font-semibold">{{ number_format($row->count) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500">本日のPVはまだありません</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
