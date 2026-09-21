<?php

use App\Http\Controllers\ProfileController;
use App\Models\Product;
use App\Models\SalesReport;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = Auth::user();

    if ($user->status !== 'active') {
        return view('pending-approval');
    }

    $reports = SalesReport::with(['store', 'product', 'user'])
        ->whereIn('status', ['active', 'scheduled'])
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
})->middleware('auth')->name('map');

Route::get('/dashboard', fn () => redirect()->route('map'))
    ->middleware('auth')
    ->name('dashboard');

Route::post('/reports', function (Request $request) {
    abort_unless($request->user()->status === 'active', 403);

    $validated = $request->validate([
        'store_name' => ['required', 'string', 'max:255'],
        'address' => ['nullable', 'string', 'max:255'],
        'latitude' => ['required', 'numeric'],
        'longitude' => ['required', 'numeric'],
        'product_name' => ['required', 'string', 'max:255'],
        'quantity_text' => ['required', 'string', 'max:255'],
        'status' => ['required', 'in:active,scheduled,sold_out'],
        'sale_at' => ['nullable', 'required_if:status,scheduled', 'date'],
        'note' => ['nullable', 'string'],
    ], [
        'required' => ':attribute là bắt buộc.',
        'required_if' => ':attribute là bắt buộc khi trạng thái là Sắp bán.',
        'numeric' => ':attribute phải là số.',
        'date' => ':attribute phải là thời gian hợp lệ.',
        'in' => ':attribute không hợp lệ.',
        'max' => ':attribute không được vượt quá :max ký tự.',
    ], [
        'store_name' => 'Tên cửa hàng',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
        'product_name' => 'Sản phẩm',
        'quantity_text' => 'Số lượng',
        'status' => 'Trạng thái',
        'sale_at' => 'Giờ bán',
        'address' => 'Địa chỉ',
        'note' => 'Ghi chú',
    ]);

    $store = Store::create([
        'name' => $validated['store_name'],
        'address' => $validated['address'] ?? null,
        'latitude' => $validated['latitude'],
        'longitude' => $validated['longitude'],
        'created_by' => $request->user()->id,
    ]);

    $product = Product::firstOrCreate(
        ['name' => $validated['product_name']],
        [
            'series' => null,
            'is_active' => true,
        ],
    );

    $saleAt = $validated['status'] === 'scheduled'
        ? $validated['sale_at']
        : now();

    SalesReport::create([
        'store_id' => $store->id,
        'product_id' => $product->id,
        'user_id' => $request->user()->id,
        'quantity' => 0,
        'quantity_text' => $validated['quantity_text'],
        'sale_type' => $validated['status'] === 'scheduled' ? 'scheduled' : 'now',
        'sale_at' => $saleAt,
        'expires_at' => Carbon::parse($saleAt)->addHours(12),
        'note' => $validated['note'] ?? null,
        'status' => $validated['status'],
    ]);

    return redirect()->route('map');
})->middleware('auth')->name('reports.store');

Route::get('/admin/users', function () {
    abort_unless(Auth::user()->isAdmin(), 403);

    return view('admin.users', [
        'pendingUsers' => User::where('status', 'pending')->latest()->get(),
        'activeUsers' => User::where('status', 'active')->latest()->get(),
    ]);
})->middleware('auth')->name('admin.users');

Route::patch('/admin/users/{user}/approve', function (User $user) {
    abort_unless(Auth::user()->isAdmin(), 403);

    $user->update(['status' => 'active']);

    return redirect()->route('admin.users')->with('status', 'Đã duyệt thành viên.');
})->middleware('auth')->name('admin.users.approve');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
