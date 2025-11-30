@extends('layouts.app')

@section('title', 'ステータス管理 - Open Seat')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ $shop->name }}</h2>
            <p class="text-gray-600">現在のステータスを選択してください</p>
        </div>
        
        <form action="{{ route('shops.update', $shop->secret_key) }}" method="POST" class="space-y-6">
            @csrf
            
            <!-- 空きボタン -->
            <button type="submit" name="status" value="0" 
                    class="w-full py-8 px-6 rounded-lg text-2xl font-bold transition-all
                    @if($shop->status === 0)
                        bg-green-500 text-white shadow-lg scale-105
                    @else
                        bg-green-100 text-green-800 hover:bg-green-200
                    @endif">
                🟢 空き
            </button>
            
            <!-- 待ちボタン -->
            <button type="submit" name="status" value="1"
                    class="w-full py-8 px-6 rounded-lg text-2xl font-bold transition-all
                    @if($shop->status === 1)
                        bg-yellow-500 text-white shadow-lg scale-105
                    @else
                        bg-yellow-100 text-yellow-800 hover:bg-yellow-200
                    @endif">
                🟡 待ち
            </button>
            
            <!-- 満席ボタン -->
            <button type="submit" name="status" value="2"
                    class="w-full py-8 px-6 rounded-lg text-2xl font-bold transition-all
                    @if($shop->status === 2)
                        bg-red-500 text-white shadow-lg scale-105
                    @else
                        bg-red-100 text-red-800 hover:bg-red-200
                    @endif">
                🔴 満席
            </button>
        </form>
        
        <div class="mt-8 pt-6 border-t border-gray-200">
            <p class="text-sm text-gray-500 text-center">
                店舗情報: {{ $shop->address }}<br>
                電話: {{ $shop->phone }}
            </p>
        </div>
    </div>
</div>
@endsection

