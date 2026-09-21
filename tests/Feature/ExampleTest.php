<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SalesReport;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this
            ->actingAs(User::factory()->create())
            ->get('/');

        $response->assertStatus(200);
    }

    public function test_user_can_create_a_sales_report_from_the_map_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/reports', [
            'store_name' => 'Joshin Test',
            'address' => 'Osaka',
            'latitude' => 34.7043,
            'longitude' => 135.4966,
            'product_name' => 'MEGAドリームex',
            'quantity_text' => '5 pack',
            'status' => 'active',
            'note' => 'Moi nguoi toi da 5 pack',
        ]);

        $response->assertRedirect('/');

        $this->assertDatabaseHas(Store::class, [
            'name' => 'Joshin Test',
            'address' => 'Osaka',
        ]);

        $this->assertDatabaseHas(Product::class, [
            'name' => 'MEGAドリームex',
        ]);

        $this->assertDatabaseHas(SalesReport::class, [
            'quantity_text' => '5 pack',
            'status' => 'active',
            'user_id' => $user->id,
        ]);

        $report = SalesReport::first();

        $this->assertNotNull($report->expires_at);
        $this->assertTrue($report->expires_at->greaterThan(now()->addHours(11)));
    }

    public function test_scheduled_report_requires_sale_time(): void
    {
        $response = $this->actingAs(User::factory()->create())->from('/')->post('/reports', [
            'store_name' => 'Joshin Test',
            'latitude' => 34.7043,
            'longitude' => 135.4966,
            'product_name' => 'MEGAドリームex',
            'quantity_text' => '5 pack',
            'status' => 'scheduled',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('sale_at');
    }

    public function test_user_can_create_a_scheduled_sales_report(): void
    {
        $saleAt = now()->addHours(2)->format('Y-m-d H:i:s');

        $response = $this->actingAs(User::factory()->create())->post('/reports', [
            'store_name' => 'Scheduled Store',
            'latitude' => 34.7043,
            'longitude' => 135.4966,
            'product_name' => 'MEGAドリームex',
            'quantity_text' => '5 pack',
            'status' => 'scheduled',
            'sale_at' => $saleAt,
        ]);

        $response->assertRedirect('/');

        $this->assertDatabaseHas(SalesReport::class, [
            'quantity_text' => '5 pack',
            'sale_type' => 'scheduled',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs(User::factory()->create())->get('/');

        $response->assertSee('Scheduled Store');
    }

    public function test_sold_out_and_expired_reports_are_hidden_from_the_map(): void
    {
        $store = Store::create([
            'name' => 'Visible Store',
            'latitude' => 34.7043,
            'longitude' => 135.4966,
        ]);

        $hiddenStore = Store::create([
            'name' => 'Hidden Store',
            'latitude' => 34.7143,
            'longitude' => 135.5066,
        ]);

        $product = Product::create([
            'name' => 'MEGAドリームex',
            'is_active' => true,
        ]);

        SalesReport::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'quantity' => 0,
            'quantity_text' => '5 pack',
            'sale_type' => 'now',
            'sale_at' => now(),
            'expires_at' => now()->addHours(12),
            'status' => 'active',
        ]);

        SalesReport::create([
            'store_id' => $hiddenStore->id,
            'product_id' => $product->id,
            'quantity' => 0,
            'quantity_text' => '1 box',
            'sale_type' => 'now',
            'sale_at' => now(),
            'expires_at' => now()->addHours(12),
            'status' => 'sold_out',
        ]);

        SalesReport::create([
            'store_id' => $hiddenStore->id,
            'product_id' => $product->id,
            'quantity' => 0,
            'quantity_text' => '10 pack',
            'sale_type' => 'now',
            'sale_at' => now()->subHours(13),
            'expires_at' => now()->subHour(),
            'status' => 'active',
        ]);

        $response = $this->actingAs(User::factory()->create())->get('/');

        $response->assertSee('Visible Store');
        $response->assertDontSee('Hidden Store');
    }

    public function test_prune_command_deletes_only_old_hidden_reports(): void
    {
        $store = Store::create([
            'name' => 'Prune Store',
            'latitude' => 34.7043,
            'longitude' => 135.4966,
        ]);

        $product = Product::create([
            'name' => 'MEGAドリームex',
            'is_active' => true,
        ]);

        $freshReport = SalesReport::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'quantity' => 0,
            'quantity_text' => '5 pack',
            'sale_type' => 'now',
            'sale_at' => now(),
            'expires_at' => now()->addHours(12),
            'status' => 'active',
        ]);

        $oldExpiredReport = SalesReport::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'quantity' => 0,
            'quantity_text' => '10 pack',
            'sale_type' => 'now',
            'sale_at' => now()->subDays(10),
            'expires_at' => now()->subDays(8),
            'status' => 'active',
        ]);

        $oldSoldOutReport = SalesReport::withoutTimestamps(fn () => SalesReport::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'quantity' => 0,
            'quantity_text' => '1 box',
            'sale_type' => 'now',
            'sale_at' => now()->subDays(10),
            'expires_at' => now()->addHours(12),
            'status' => 'sold_out',
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(8),
        ]));

        Artisan::call('reports:prune');

        $this->assertDatabaseHas(SalesReport::class, [
            'id' => $freshReport->id,
        ]);

        $this->assertDatabaseMissing(SalesReport::class, [
            'id' => $oldExpiredReport->id,
        ]);

        $this->assertDatabaseMissing(SalesReport::class, [
            'id' => $oldSoldOutReport->id,
        ]);

        $this->assertDatabaseHas(Store::class, [
            'id' => $store->id,
        ]);

        $this->assertDatabaseHas(Product::class, [
            'id' => $product->id,
        ]);
    }

    public function test_pending_user_sees_approval_screen(): void
    {
        $user = User::factory()->create([
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Tài khoản đang chờ duyệt');
    }

    public function test_admin_can_approve_pending_user(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $pendingUser = User::factory()->create([
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.users.approve', $pendingUser));

        $response->assertRedirect(route('admin.users'));
        $this->assertDatabaseHas(User::class, [
            'id' => $pendingUser->id,
            'status' => 'active',
        ]);
    }

    public function test_first_registered_user_becomes_active_admin_and_next_user_is_pending(): void
    {
        $this->post('/register', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas(User::class, [
            'email' => 'admin@example.com',
            'role' => 'admin',
            'status' => 'active',
        ]);

        auth()->logout();

        $this->post('/register', [
            'name' => 'Member User',
            'email' => 'member@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas(User::class, [
            'email' => 'member@example.com',
            'role' => 'member',
            'status' => 'pending',
        ]);
    }

    public function test_make_admin_command_promotes_user(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'role' => 'member',
            'status' => 'pending',
        ]);

        Artisan::call('users:make-admin', [
            'email' => $user->email,
        ]);

        $this->assertDatabaseHas(User::class, [
            'id' => $user->id,
            'role' => 'admin',
            'status' => 'active',
        ]);
    }
}
