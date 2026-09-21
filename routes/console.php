<?php

use App\Models\SalesReport;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reports:prune {--days=7 : Number of days to keep hidden reports}', function () {
    $days = max(1, (int) $this->option('days'));
    $cutoff = now()->subDays($days)->toDateTimeString();

    $deleted = SalesReport::query()
        ->where(function ($query) use ($cutoff) {
            $query->where('expires_at', '<', $cutoff)
                ->orWhere('status', 'sold_out');
        })
        ->delete();

    $this->info("Deleted {$deleted} old sales reports.");
})->purpose('Delete old hidden sales reports while keeping stores and products');
