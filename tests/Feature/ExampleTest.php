<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SalesReport;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_user_can_create_a_sales_report_from_the_map_form(): void
    {
        $response = $this->post('/reports', [
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
        ]);

        $report = SalesReport::first();

        $this->assertNotNull($report->expires_at);
        $this->assertTrue($report->expires_at->greaterThan(now()->addHours(11)));
    }

    public function test_scheduled_report_requires_sale_time(): void
    {
        $response = $this->from('/')->post('/reports', [
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

        $response = $this->post('/reports', [
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

        $response = $this->get('/');

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

        $response = $this->get('/');

        $response->assertSee('Visible Store');
        $response->assertDontSee('Hidden Store');
    }
}
