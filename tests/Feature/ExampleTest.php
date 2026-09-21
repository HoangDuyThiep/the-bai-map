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
            'sale_type' => 'now',
            'sale_at' => '2026-09-21 10:00:00',
            'expires_at' => '2026-09-21 13:00:00',
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
    }
}
