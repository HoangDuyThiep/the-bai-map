<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\SalesReport;
use App\Models\Store;
use Illuminate\Database\Seeder;

class MapDemoSeeder extends Seeder
{
    public function run(): void
    {
        $megaDream = Product::create([
            'name' => 'MEGAドリームex',
            'series' => 'Pokemon Card',
            'is_active' => true,
        ]);

        $rocket = Product::create([
            'name' => 'ロケット団の栄光',
            'series' => 'Pokemon Card',
            'is_active' => true,
        ]);

        $blackBolt = Product::create([
            'name' => 'ブラックボルト',
            'series' => 'Pokemon Card',
            'is_active' => true,
        ]);

        $joshin = Store::create([
            'name' => 'Joshin Hirakata',
            'address' => 'Hirakata, Osaka',
            'latitude' => 34.8147000,
            'longitude' => 135.6500000,
            'note' => null,
        ]);

        $yodobashi = Store::create([
            'name' => 'Yodobashi Umeda',
            'address' => 'Umeda, Osaka',
            'latitude' => 34.7043000,
            'longitude' => 135.4966000,
            'note' => null,
        ]);

        $pokemonCenter = Store::create([
            'name' => 'Pokemon Center Osaka',
            'address' => 'Osaka Station City',
            'latitude' => 34.7024000,
            'longitude' => 135.4959000,
            'note' => null,
        ]);

        SalesReport::create([
            'store_id' => $joshin->id,
            'product_id' => $megaDream->id,
            'quantity' => 20,
            'quantity_text' => '20 box',
            'sale_type' => 'now',
            'sale_at' => now(),
            'expires_at' => now()->addHours(12),
            'note' => 'Mỗi người tối đa 1 BOX',
            'status' => 'active',
        ]);

        SalesReport::create([
            'store_id' => $yodobashi->id,
            'product_id' => $rocket->id,
            'quantity' => 10,
            'quantity_text' => '10 pack',
            'sale_type' => 'now',
            'sale_at' => now(),
            'expires_at' => now()->addHours(12),
            'note' => 'Vừa có người báo đã hết hàng',
            'status' => 'sold_out',
        ]);

        SalesReport::create([
            'store_id' => $pokemonCenter->id,
            'product_id' => $blackBolt->id,
            'quantity' => 5,
            'quantity_text' => '5 pack',
            'sale_type' => 'now',
            'sale_at' => now()->subHours(13),
            'expires_at' => now()->subHour(),
            'note' => 'Thông tin cũ đã tự quá hạn',
            'status' => 'active',
        ]);
    }
}
