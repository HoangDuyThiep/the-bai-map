<?php

use App\Models\Product;
use App\Models\SalesReport;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $reports = SalesReport::with(['store', 'product'])
        ->where('status', 'active')
        ->where(function ($query) {
            $query->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        })
        ->latest()
        ->get()
        ->map(function ($report) {
            return [
                'id' => $report->id,
                'store' => $report->store->name,
                'address' => $report->store->address,
                'lat' => (float) $report->store->latitude,
                'lng' => (float) $report->store->longitude,
                'product' => $report->product->name,
                'quantity' => $report->quantity,
                'quantityText' => $report->quantity_text ?? ($report->quantity . ' box'),
                'saleAt' => $report->sale_at,
                'updatedAgo' => $report->updated_at->diffForHumans(),
                'reporter' => $report->user?->name ?? 'Demo User',
                'note' => $report->note,
                'status' => $report->status,
            ];
        });

    return view('welcome', [
        'reports' => $reports,
    ]);
});

Route::post('/reports', function (Request $request) {
    $validated = $request->validate([
        'store_name' => ['required', 'string', 'max:255'],
        'address' => ['nullable', 'string', 'max:255'],
        'latitude' => ['required', 'numeric'],
        'longitude' => ['required', 'numeric'],
        'product_name' => ['required', 'string', 'max:255'],
        'quantity_text' => ['required', 'string', 'max:255'],
        'status' => ['required', 'in:active,sold_out'],
        'note' => ['nullable', 'string'],
    ], [
        'required' => ':attribute là bắt buộc.',
        'numeric' => ':attribute phải là số.',
        'in' => ':attribute không hợp lệ.',
        'max' => ':attribute không được vượt quá :max ký tự.',
    ], [
        'store_name' => 'Tên cửa hàng',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
        'product_name' => 'Sản phẩm',
        'quantity_text' => 'Số lượng',
        'status' => 'Trạng thái',
        'address' => 'Địa chỉ',
        'note' => 'Ghi chú',
    ]);

    $store = Store::create([
        'name' => $validated['store_name'],
        'address' => $validated['address'] ?? null,
        'latitude' => $validated['latitude'],
        'longitude' => $validated['longitude'],
    ]);

    $product = Product::firstOrCreate(
        ['name' => $validated['product_name']],
        [
            'series' => null,
            'is_active' => true,
        ],
    );

    SalesReport::create([
        'store_id' => $store->id,
        'product_id' => $product->id,
        'quantity' => 0,
        'quantity_text' => $validated['quantity_text'],
        'sale_type' => 'now',
        'sale_at' => now(),
        'expires_at' => now()->addHours(12),
        'note' => $validated['note'] ?? null,
        'status' => $validated['status'],
    ]);

    return redirect('/');
});
