<?php

use App\Http\Controllers\ProfileController;
use App\Models\Product;
use App\Models\ReportHelpfulVote;
use App\Models\SalesReport;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
        ->withCount('helpfulVotes')
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
                'saleAt' => $report->sale_at ? $report->sale_at->timezone('Asia/Tokyo')->format('Y/m/d H:i') . ' JST' : '',
                'saleAtInput' => $report->sale_at?->timezone('Asia/Tokyo')->format('Y-m-d\TH:i'),
                'updatedAt' => $report->updated_at->timezone('Asia/Tokyo')->format('Y/m/d H:i') . ' JST',
                'updatedAgo' => $report->updated_at->diffForHumans(),
                'reporter' => $report->user?->name ?? 'Demo User',
                'note' => $report->note,
                'status' => $report->status,
                'helpfulCount' => $report->helpful_votes_count,
            ];
        });

    return view('welcome', [
        'reports' => $reports,
    ]);
})->middleware('auth')->name('map');

Route::get('/dashboard', fn () => redirect()->route('map'))
    ->middleware('auth')
    ->name('dashboard');

Route::get('/rank', function () {
    abort_unless(Auth::user()->status === 'active', 403);

    $members = User::query()
        ->where('status', 'active')
        ->withCount('salesReports')
        ->withCount([
            'salesReports as helpful_votes_count' => function (Builder $query) {
                $query->join('report_helpful_votes', 'sales_reports.id', '=', 'report_helpful_votes.sales_report_id');
            },
        ])
        ->orderByDesc('helpful_votes_count')
        ->orderByDesc('sales_reports_count')
        ->orderBy('name')
        ->get();

    return view('rank', [
        'members' => $members,
    ]);
})->middleware('auth')->name('rank');

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

    $latitude = round((float) $validated['latitude'], 7);
    $longitude = round((float) $validated['longitude'], 7);

    $store = Store::firstOrCreate(
        [
            'name' => $validated['store_name'],
            'latitude' => $latitude,
            'longitude' => $longitude,
        ],
        [
            'address' => $validated['address'] ?? null,
            'created_by' => $request->user()->id,
        ],
    );

    $product = Product::firstOrCreate(
        ['name' => $validated['product_name']],
        [
            'series' => null,
            'is_active' => true,
        ],
    );

    $saleAt = $validated['status'] === 'scheduled'
        ? Carbon::parse($validated['sale_at'], 'Asia/Tokyo')->utc()
        : now();

    $expiresAt = $validated['status'] === 'sold_out'
        ? now()
        : $saleAt->copy()->addHours(12);

    SalesReport::create([
        'store_id' => $store->id,
        'product_id' => $product->id,
        'user_id' => $request->user()->id,
        'quantity' => 0,
        'quantity_text' => $validated['quantity_text'],
        'sale_type' => $validated['status'] === 'scheduled' ? 'scheduled' : 'now',
        'sale_at' => $saleAt,
        'expires_at' => $expiresAt,
        'note' => $validated['note'] ?? null,
        'status' => $validated['status'],
    ]);

    return redirect()->route('map');
})->middleware('auth')->name('reports.store');

Route::patch('/reports/{report}', function (Request $request, SalesReport $report) {
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

    $latitude = round((float) $validated['latitude'], 7);
    $longitude = round((float) $validated['longitude'], 7);

    $store = Store::firstOrCreate(
        [
            'name' => $validated['store_name'],
            'latitude' => $latitude,
            'longitude' => $longitude,
        ],
        [
            'address' => $validated['address'] ?? null,
            'created_by' => $request->user()->id,
        ],
    );

    $store->update([
        'address' => $validated['address'] ?? $store->address,
    ]);

    $product = Product::firstOrCreate(
        ['name' => $validated['product_name']],
        [
            'series' => null,
            'is_active' => true,
        ],
    );

    $saleAt = $validated['status'] === 'scheduled'
        ? Carbon::parse($validated['sale_at'], 'Asia/Tokyo')->utc()
        : now();

    $expiresAt = $validated['status'] === 'sold_out'
        ? now()
        : $saleAt->copy()->addHours(12);

    $report->update([
        'store_id' => $store->id,
        'product_id' => $product->id,
        'quantity_text' => $validated['quantity_text'],
        'sale_type' => $validated['status'] === 'scheduled' ? 'scheduled' : 'now',
        'sale_at' => $saleAt,
        'expires_at' => $expiresAt,
        'note' => $validated['note'] ?? null,
        'status' => $validated['status'],
    ]);

    return redirect()->route('map');
})->middleware('auth')->name('reports.update');

Route::post('/reports/{report}/helpful', function (Request $request, SalesReport $report) {
    abort_unless($request->user()->status === 'active', 403);

    if ($report->user_id === $request->user()->id) {
        return redirect()->route('map')->with('status', 'Không thể tự cộng hữu ích cho bài của mình.');
    }

    ReportHelpfulVote::firstOrCreate([
        'sales_report_id' => $report->id,
        'user_id' => $request->user()->id,
    ]);

    return redirect()->route('map')->with('status', 'Đã ghi nhận hữu ích.');
})->middleware('auth')->name('reports.helpful');

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
