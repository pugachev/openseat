<?php

namespace App\Http\Controllers;

use App\Mail\PinNotificationMail;
use App\Models\Pin;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PinController extends Controller
{
    public function index()
    {
        $pins = Pin::query()
            ->latest()
            ->limit(300)
            ->get();

        return response()->json(['data' => $pins]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in([1, 2, 3, 4])],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'comment' => ['nullable', 'string', 'max:500'],
            'store_name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([0, 1, 2])],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'image' => ['nullable', 'image', 'max:5120'], // 5MB
            'shop_id' => ['nullable', 'integer', 'exists:shops,id'],
            'notify_email' => ['nullable', 'email', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $type = (int) $request->input('type');
            if ($type === 2) {
                if (!$request->filled('store_name')) {
                    $validator->errors()->add('store_name', '店名は必須です。');
                }
                if ($request->input('status') === null) {
                    $validator->errors()->add('status', '状況を選択してください。');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $pin = new Pin();
        $pin->type = (int) $data['type'];
        $pin->latitude = (float) $data['latitude'];
        $pin->longitude = (float) $data['longitude'];
        $pin->comment = $data['comment'] ?? null;
        $pin->store_name = $data['store_name'] ?? null;
        $pin->status = $data['status'] ?? null;
        $pin->tags = $data['tags'] ?? [];
        $pin->user_id = auth()->id();
        $pin->shop_id = $data['shop_id'] ?? null;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('pins', 'public');
            $pin->image_path = $path;
        }

        $pin->save();

        if (!empty($data['notify_email'])) {
            try {
                $pinUrl = rtrim(config('app.url'), '/') . '/?pin=' . $pin->id;
                Mail::to($data['notify_email'])->send(new PinNotificationMail($pin, $pinUrl));
            } catch (\Throwable $e) {
                Log::warning('ピン通知メール送信失敗', [
                    'pin_id' => $pin->id,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $pin->fresh(),
        ], 201);
    }

    public function download(Request $request)
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $pins = Pin::whereNotNull('image_path')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->get();

        if ($pins->isEmpty()) {
            return response()->json(['message' => '該当日に画像がありません'], 404);
        }

        $disk    = Storage::disk('public');
        $tmpFile = tempnam(sys_get_temp_dir(), 'pins_');

        $zip = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($pins as $pin) {
            $normalizedPath = ltrim($pin->image_path, '/');
            if ($disk->exists($normalizedPath)) {
                $zipFilename = $pin->created_at->format('Ymd_His') . '_' . basename($normalizedPath);
                $zip->addFile($disk->path($normalizedPath), $zipFilename);
            }
        }

        $zip->close();

        $zipName = 'pins_' . str_replace('-', '', $startDate) . '_' . str_replace('-', '', $endDate) . '.zip';

        return response()->download($tmpFile, $zipName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function image(string $path)
    {
        $normalizedPath = ltrim($path, '/');

        if ($normalizedPath === '' || str_contains($normalizedPath, '..')) {
            abort(404);
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($normalizedPath)) {
            abort(404);
        }

        return response()->file($disk->path($normalizedPath), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
