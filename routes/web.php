<?php

use App\Models\SalesReport;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $reports = SalesReport::with(['store', 'product'])
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