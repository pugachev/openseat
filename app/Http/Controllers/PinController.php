<?php

namespace App\Http\Controllers;

use App\Models\Pin;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        $pin = DB::transaction(function () use ($data, $request) {
            $shopId = $data['shop_id'] ?? null;
            $storeName = isset($data['store_name']) ? trim($data['store_name']) : null;

            if ((int) $data['type'] === 2) {
                if ($shopId) {
                    $shop = Shop::find($shopId);
                    if ($shop) {
                        $storeName = $shop->name;
                    }
                } else {
                    $shop = Shop::create([
                        'name' => $storeName,
                        'category' => null,
                        'phone' => '',
                        'address' => '',
                        'latitude' => (float) $data['latitude'],
                        'longitude' => (float) $data['longitude'],
                        'status' => (int) $data['status'],
                        'secret_key' => Str::random(32),
                        'user_id' => auth()->id(),
                    ]);

                    $shopId = $shop->id;
                }
            }

            $pin = new Pin();
            $pin->type = (int) $data['type'];
            $pin->latitude = (float) $data['latitude'];
            $pin->longitude = (float) $data['longitude'];
            $pin->comment = $data['comment'] ?? null;
            $pin->store_name = $storeName;
            $pin->status = $data['status'] ?? null;
            $pin->tags = $data['tags'] ?? [];
            $pin->user_id = auth()->id();
            $pin->shop_id = $shopId;

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('pins', 'public');
                $pin->image_path = $path;
            }

            $pin->save();

            return $pin;
        });

        return response()->json([
            'success' => true,
            'data' => $pin->fresh(),
        ], 201);
    }
}
