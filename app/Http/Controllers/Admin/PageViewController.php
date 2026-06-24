<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use Illuminate\View\View;

class PageViewController extends Controller
{
    /**
     * PVレポート一覧
     */
    public function index(): View
    {
        $today = now()->toDateString();

        $todayRows = PageView::query()
            ->whereDate('view_date', $today)
            ->orderByDesc('count')
            ->limit(50)
            ->get();

        $todayTotal = (int) $todayRows->sum('count');

        $last14Days = PageView::query()
            ->selectRaw('view_date, SUM(count) as total_count')
            ->whereDate('view_date', '>=', now()->subDays(13)->toDateString())
            ->groupBy('view_date')
            ->orderBy('view_date')
            ->get();

        return view('admin.page_views.index', [
            'todayRows' => $todayRows,
            'todayTotal' => $todayTotal,
            'last14Days' => $last14Days,
        ]);
    }
}
