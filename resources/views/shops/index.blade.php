@extends('layouts.app')

@section('title', '店舗一覧 - Open Seat')

@section('content')
<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-900 mb-2">空き状況を確認</h2>
    <p class="text-gray-600">現在の混雑状況を確認できます</p>
</div>

@if($shops->isEmpty())
    <div class="text-center py-12">
        <p class="text-gray-500">登録されている店舗がありません</p>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($shops as $shop)
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <h3 class="text-xl font-semibold text-gray-900">{{ $shop->name }}</h3>
                        <span class="px-3 py-1 rounded-full text-sm font-medium
                            @if($shop->status === 0) bg-green-100 text-green-800
                            @elseif($shop->status === 1) bg-yellow-100 text-yellow-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ $shop->status_label }}
                        </span>
                    </div>
                    
                    <div class="space-y-2 mb-4">
                        <p class="text-gray-600 text-sm">
                            <span class="font-medium">住所:</span> {{ $shop->address }}
                        </p>
                        <p class="text-gray-600 text-sm">
                            <span class="font-medium">電話:</span> {{ $shop->phone }}
                        </p>
                    </div>
                    
                    <a href="tel:{{ $shop->phone }}" 
                       class="inline-flex items-center justify-center w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <span class="mr-2">📞</span>
                        電話する
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection

