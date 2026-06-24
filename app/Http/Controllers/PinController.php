<?php

namespace App\Http\Controllers;

use App\Mail\PinNotificationMail;
use App\Models\Pin;
use Carbon\Carbon;
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
            'image' => ['nullable', 'image', 'max:20480'], // 20MB
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'max:20480'],
            'image_source' => ['nullable', Rule::in(['camera', 'gallery'])],
            'shop_id' => ['nullable', 'integer', 'exists:shops,id'],
            'notify_email' => ['nullable', 'email', 'max:255'],
        ], [
            'image.max' => '画像サイズは20MB以下にしてください。',
            'images.*.max' => '画像サイズは1枚あたり20MB以下にしてください。',
            'image.image' => '画像ファイルを選択してください。',
            'images.*.image' => '画像ファイルを選択してください。',
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
        $imageSource = $data['image_source'] ?? 'camera';
        $files = [];

        if ($request->hasFile('images')) {
            $files = $request->file('images');
        } elseif ($request->hasFile('image')) {
            $files = [$request->file('image')];
        }

        if (empty($files)) {
            $files = [null];
        }

        $pins = [];

        foreach ($files as $file) {
            $exif = ($file && $imageSource === 'gallery')
                ? $this->extractImageExif($file->getRealPath())
                : ['latitude' => null, 'longitude' => null, 'taken_at' => null];

            $pin = new Pin();
            $pin->type = (int) $data['type'];
            $pin->latitude = (float) ($exif['latitude'] ?? $data['latitude']);
            $pin->longitude = (float) ($exif['longitude'] ?? $data['longitude']);
            $pin->taken_at = $exif['taken_at'] ?? null;
            $pin->comment = $data['comment'] ?? null;
            $pin->store_name = $data['store_name'] ?? null;
            $pin->status = $data['status'] ?? null;
            $pin->tags = $data['tags'] ?? [];
            $pin->user_id = auth()->id();
            $pin->shop_id = $data['shop_id'] ?? null;

            if ($file) {
                $path = $file->store('pins', 'public');
                $pin->image_path = $path;
            }

            $pin->save();
            $pins[] = $pin->fresh();
        }

        if (!empty($data['notify_email'])) {
            try {
                $pinUrl = rtrim(config('app.url'), '/') . '/?pin=' . $pins[0]->id;
                Mail::to($data['notify_email'])->send(new PinNotificationMail($pins[0], $pinUrl));
            } catch (\Throwable $e) {
                Log::warning('ピン通知メール送信失敗', [
                    'pin_id' => $pins[0]->id,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => count($pins) === 1 ? $pins[0] : $pins,
            'pins' => $pins,
        ], 201);
    }

    private function extractImageExif(string $path): array
    {
        if (!function_exists('exif_read_data')) {
            return ['latitude' => null, 'longitude' => null, 'taken_at' => null];
        }

        try {
            $exif = @exif_read_data($path, 'IFD0,EXIF,GPS', true);
        } catch (\Throwable $e) {
            return ['latitude' => null, 'longitude' => null, 'taken_at' => null];
        }

        if (!is_array($exif)) {
            return ['latitude' => null, 'longitude' => null, 'taken_at' => null];
        }

        $gps = $exif['GPS'] ?? [];
        $latitude = null;
        $longitude = null;

        if (!empty($gps['GPSLatitude']) && !empty($gps['GPSLongitude'])) {
            $latitude = $this->gpsCoordinateToDecimal(
                $gps['GPSLatitude'],
                $gps['GPSLatitudeRef'] ?? 'N'
            );
            $longitude = $this->gpsCoordinateToDecimal(
                $gps['GPSLongitude'],
                $gps['GPSLongitudeRef'] ?? 'E'
            );
        }

        $dateValue = $exif['EXIF']['DateTimeOriginal']
            ?? $exif['EXIF']['DateTimeDigitized']
            ?? $exif['IFD0']['DateTime']
            ?? null;

        $takenAt = null;
        if (is_string($dateValue) && $dateValue !== '') {
            try {
                $takenAt = Carbon::createFromFormat('Y:m:d H:i:s', $dateValue, 'Asia/Tokyo')->utc();
            } catch (\Throwable $e) {
                $takenAt = null;
            }
        }

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'taken_at' => $takenAt,
        ];
    }

    private function gpsCoordinateToDecimal(array $coordinate, string $ref): ?float
    {
        if (count($coordinate) < 3) {
            return null;
        }

        $degrees = $this->gpsPartToFloat($coordinate[0]);
        $minutes = $this->gpsPartToFloat($coordinate[1]);
        $seconds = $this->gpsPartToFloat($coordinate[2]);

        if ($degrees === null || $minutes === null || $seconds === null) {
            return null;
        }

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        return in_array(strtoupper($ref), ['S', 'W'], true) ? -$decimal : $decimal;
    }

    private function gpsPartToFloat(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value) || $value === '') {
            return null;
        }

        if (!str_contains($value, '/')) {
            return is_numeric($value) ? (float) $value : null;
        }

        [$numerator, $denominator] = array_pad(explode('/', $value, 2), 2, null);

        if (!is_numeric($numerator) || !is_numeric($denominator) || (float) $denominator == 0.0) {
            return null;
        }

        return (float) $numerator / (float) $denominator;
    }

    public function download(Request $request)
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        // UIで入力される日付はJST基準のため、UTCに変換して範囲検索する
        // (DBのcreated_atはUTC保存のため、whereDate()をそのまま使うと日付がズレる)
        $startUtc = \Carbon\Carbon::createFromFormat('Y-m-d', $startDate, 'Asia/Tokyo')
            ->startOfDay()->utc();
        $endUtc   = \Carbon\Carbon::createFromFormat('Y-m-d', $endDate, 'Asia/Tokyo')
            ->endOfDay()->utc();

        $pins = Pin::whereNotNull('image_path')
            ->whereBetween('created_at', [$startUtc, $endUtc])
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
                $jstTime = $pin->created_at->setTimezone('Asia/Tokyo');
                $zipFilename = $jstTime->format('Ymd_His') . '_' . basename($normalizedPath);
                $zip->addFile($disk->path($normalizedPath), $zipFilename);
            }
        }

        $zip->close();

        $zipName = 'pins_' . str_replace('-', '', $startDate) . '_' . str_replace('-', '', $endDate) . '.zip';

        return response()->download($tmpFile, $zipName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function like(int $id)
    {
        $pin = Pin::findOrFail($id);
        $pin->increment('like_count');

        return response()->json(['like_count' => $pin->like_count]);
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
