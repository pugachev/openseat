<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Open Seat')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('map.svg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900">いまどこ？</h1>
                @php
                    $bannerUser = auth()->user() ?? \App\Models\User::first();
                    $bannerText = $bannerUser?->banner_text ?? '管理人は開拓中！';
                    $bannerBg   = $bannerUser?->banner_bg_color ?? '#dcfce7';
                    $bannerFg   = $bannerUser?->banner_text_color ?? '#166534';
                @endphp
                @if($bannerText)
                    <span class="text-sm font-medium px-3 py-1 rounded-full"
                          style="background-color: {{ $bannerBg }}; color: {{ $bannerFg }};">
                        {{ $bannerText }}
                    </span>
                @endif
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
