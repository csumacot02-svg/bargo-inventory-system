<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MoreBargoProductsSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::query()->get();
        if ($categories->isEmpty()) {
            $this->call(SampleCatalogSeeder::class);
            $categories = Category::query()->get();
        }

        $supplier = Supplier::query()->firstOrCreate(
            ['name' => 'SLSU Campus Supply Depot'],
            [
                'contact_person' => 'Supply Officer',
                'phone' => '09123456789',
                'email' => 'supply@slsu.edu.ph',
                'address' => 'SLSU-Hinunangan Campus',
            ]
        );

        $samples = [
            ['Manila Folder Pack', 'Office Supplies', 'pack', 80, 20, null, false, null, 45.00],
            ['Urea Fertilizer 50kg', 'Organic Fertilizer', 'sack', 16, 5, now()->addMonths(8)->toDateString(), true, 240, 1450.00],
            ['Carabao Grass Cutter Blade', 'Equipment', 'pcs', 10, 3, null, false, null, 380.00],
            ['Sweet Potato (Camote)', 'Agricultural Products', 'kg', 55, 15, now()->addDays(14)->toDateString(), true, 21, 40.00],
            ['White Corn (Shelled)', 'Rice and Grains', 'kg', 70, 20, now()->addMonths(4)->toDateString(), true, 120, 38.00],
            ['Papaya (Ripe)', 'Vegetables and Fruits', 'kg', 25, 8, now()->addDays(4)->toDateString(), true, 6, 55.00],
            ['Goat Mineral Block', 'Livestock-Related Inventory', 'pcs', 18, 6, now()->addMonths(10)->toDateString(), true, 365, 210.00],
            ['Campus Lanyard', 'Merchandise', 'pcs', 120, 30, null, false, null, 45.00],
            ['Bleach Gallon', 'Cleaning Supplies', 'gal', 24, 8, now()->addMonths(18)->toDateString(), true, 540, 95.00],
            ['Twine Roll 1kg', 'Packaging Materials', 'roll', 36, 10, null, false, null, 85.00],
        ];

        $created = 0;

        foreach ($samples as [$name, $categoryName, $unit, $qty, $reorder, $expires, $perishable, $shelf, $price]) {
            if (InventoryItem::query()->where('name', $name)->exists()) {
                continue;
            }

            $category = $categories->firstWhere('name', $categoryName)
                ?? $categories->first();

            InventoryItem::query()->create([
                'item_code' => 'INV-'.strtoupper(Str::random(5)),
                'name' => $name,
                'category_id' => $category->id,
                'supplier_id' => $supplier->id,
                'description' => $name.' — sample BARGO stock item',
                'unit' => $unit,
                'unit_price' => $price,
                'quantity_on_hand' => $qty,
                'reorder_level' => $reorder,
                'condition' => 'good',
                'expiration_date' => $expires,
                'location' => 'BARGO Storeroom',
                'barcode' => 'BC'.random_int(100000, 999999),
                'qr_code' => 'QR-'.strtoupper(Str::random(8)),
                'is_active' => true,
                'is_perishable' => $perishable,
                'shelf_life_days' => $shelf,
            ]);

            $created++;
        }

        $this->command?->info("Added {$created} sample BARGO products. Total items: ".InventoryItem::query()->count());
    }
}
