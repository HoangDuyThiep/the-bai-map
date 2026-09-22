<?php

use App\Http\Controllers\ProfileController;
use App\Models\BlockedMemberEmail;
use App\Models\Product;
use App\Models\ReportHelpfulVote;
use App\Models\SalesReport;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

Route::get('/places/search', function (Request $request) {
    abort_unless($request->user()->status === 'active', 403);

    $validated = $request->validate([
        'q' => ['required', 'string', 'min:2', 'max:120'],
    ]);

    $query = trim($validated['q']);
    $cacheKey = 'place-search:' . md5(mb_strtolower($query));

    $places = Cache::remember($cacheKey, now()->addDay(), function () use ($query) {
        $response = Http::withHeaders([
            'User-Agent' => 'TheBaiMap/1.0 (' . config('app.url') . ')',
            'Accept-Language' => 'ja,vi,en',
        ])
            ->timeout(8)
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'jsonv2',
                'limit' => 5,
                'countrycodes' => 'jp',
                'addressdetails' => 1,
            ]);

        if ($response->failed()) {
            abort(502, 'Không thể tìm địa điểm lúc này.');
        }

        return collect($response->json())
            ->map(function (array $place) {
                return [
                    'id' => $place['place_id'] ?? null,
                    'name' => ($place['name'] ?? null) ?: ($place['display_name'] ?? 'Địa điểm'),
                    'address' => $place['display_name'] ?? '',
                    'lat' => (float) ($place['lat'] ?? 0),
                    'lng' => (float) ($place['lon'] ?? 0),
                    'type' => $place['type'] ?? null,
                ];
            })
            ->filter(fn (array $place) => $place['lat'] && $place['lng'])
            ->values()
            ->all();
    });

    return response()->json([
        'places' => $places,
    ]);
})->middleware('auth')->name('places.search');

Route::post('/reports', function (Request $request) {
    abort_unless($request->user()->status === 'active', 403);

    $validated = $request->validate([
        'store_name' => ['required', 'string', 'max:255'],
        'address' => ['nullable', 'string', 'max:255'],
        'latitude' => ['required', 'numeric'],
        'longitude' => ['required', 'numeric'],
        'client_token' => ['nullable', 'string', 'max:80'],
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
    $clientToken = $validated['client_token'] ?? null;

    if ($clientToken) {
        $existingReport = SalesReport::where('user_id', $request->user()->id)
            ->where('client_token', $clientToken)
            ->first();

        if ($existingReport) {
            return redirect()->route('map')->with('status', 'Thông tin này đã được đăng.');
        }
    }

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
        'client_token' => $clientToken,
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
        'blockedEmails' => BlockedMemberEmail::latest()->get(),
    ]);
})->middleware('auth')->name('admin.users');

Route::patch('/admin/users/{user}/approve', function (User $user) {
    abort_unless(Auth::user()->isAdmin(), 403);

    $user->update(['status' => 'active']);
    BlockedMemberEmail::where('email', mb_strtolower($user->email))->delete();

    return redirect()->route('admin.users')->with('status', 'Đã duyệt thành viên.');
})->middleware('auth')->name('admin.users.approve');

Route::delete('/admin/users/{user}', function (User $user) {
    abort_unless(Auth::user()->isAdmin(), 403);
    abort_if($user->isAdmin(), 403, 'Không thể xóa tài khoản admin.');
    abort_if($user->is(Auth::user()), 403, 'Không thể tự xóa tài khoản của mình.');

    BlockedMemberEmail::updateOrCreate(
        ['email' => mb_strtolower($user->email)],
        ['blocked_by' => Auth::id()],
    );

    $user->delete();

    return redirect()->route('admin.users')->with('status', 'Đã xóa thành viên. Nếu email này đăng ký lại, tài khoản sẽ cần duyệt.');
})->middleware('auth')->name('admin.users.destroy');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
