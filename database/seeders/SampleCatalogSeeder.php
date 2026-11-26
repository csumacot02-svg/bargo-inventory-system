<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SampleCatalogSeeder extends Seeder
{
    /**
     * Add 10 sample categories and 10 sample products (idempotent).
     */
    public function run(): void
    {
        $categoryNames = [
            'Office Supplies',
            'Agricultural Products',
            'Livestock-Related Inventory',
            'Rice and Grains',
            'Vegetables and Fruits',
            'Organic Fertilizer',
            'Merchandise',
            'Equipment',
            'Cleaning Supplies',
            'Packaging Materials',
        ];

        $categories = collect($categoryNames)->map(fn (string $name) => Category::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            [
                'name' => $name,
                'description' => $name.' managed by BARGO',
            ]
        ));

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
            ['Bond Paper A4', 'Office Supplies', 'ream', 40, 10, null, false, null, 250.00],
            ['Ballpen Box (Blue)', 'Office Supplies', 'box', 25, 8, null, false, null, 60.00],
            ['Organic Fertilizer Sack', 'Organic Fertilizer', 'sack', 18, 5, now()->addDays(90)->toDateString(), true, 180, 350.00],
            ['Fresh Vegetables Mix', 'Vegetables and Fruits', 'kg', 30, 10, now()->addDays(5)->toDateString(), true, 7, 80.00],
            ['Local Milled Rice', 'Rice and Grains', 'sack', 12, 4, now()->addMonths(6)->toDateString(), true, 180, 2200.00],
            ['Printer Ink Black', 'Office Supplies', 'pcs', 8, 5, null, false, null, 450.00],
            ['Garden Hand Tools Set', 'Equipment', 'set', 6, 2, null, false, null, 850.00],
            ['Campus Merchandise Shirt', 'Merchandise', 'pcs', 50, 15, null, false, null, 350.00],
            ['Disinfectant Solution', 'Cleaning Supplies', 'gal', 20, 6, now()->addYear()->toDateString(), true, 365, 280.00],
            ['Corrugated Box Medium', 'Packaging Materials', 'pcs', 100, 25, null, false, null, 25.00],
        ];

        $created = 0;

        foreach ($samples as [$name, $categoryName, $unit, $qty, $reorder, $expires, $perishable, $shelf, $price]) {
            $exists = InventoryItem::query()->where('name', $name)->exists();
            if ($exists) {
                continue;
            }

            $category = $categories->firstWhere('name', $categoryName);

            InventoryItem::query()->create([
                'item_code' => 'INV-'.strtoupper(Str::random(5)),
                'name' => $name,
                'category_id' => $category->id,
                'supplier_id' => $supplier->id,
                'description' => $name.' for BARGO inventory monitoring',
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

        $this->command?->info("Sample catalog ready: {$categories->count()} categories, {$created} new products added.");
    }
}
