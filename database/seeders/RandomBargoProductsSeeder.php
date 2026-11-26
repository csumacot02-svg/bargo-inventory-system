<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RandomBargoProductsSeeder extends Seeder
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
            ['Coco Seedlings (Tray)', 'Agricultural Products', 'tray', 35, 10, null, false, null, 120.00],
            ['Feed Pellets 25kg', 'Livestock-Related Inventory', 'sack', 22, 8, now()->addMonths(4)->toDateString(), true, 120, 980.00],
            ['Native Chicken Eggs', 'Livestock-Related Inventory', 'tray', 15, 5, now()->addDays(12)->toDateString(), true, 21, 220.00],
            ['Banana (Lakatan)', 'Vegetables and Fruits', 'kg', 48, 15, now()->addDays(6)->toDateString(), true, 7, 65.00],
            ['Corn Grits', 'Rice and Grains', 'kg', 60, 20, now()->addMonths(5)->toDateString(), true, 150, 45.00],
            ['Stapler Heavy Duty', 'Office Supplies', 'pcs', 14, 4, null, false, null, 185.00],
            ['Wheelbarrow Steel', 'Equipment', 'pcs', 4, 1, null, false, null, 2800.00],
            ['BARGO Tote Bag', 'Merchandise', 'pcs', 75, 20, null, false, null, 150.00],
            ['Floor Mop Refill', 'Cleaning Supplies', 'pcs', 28, 8, null, false, null, 95.00],
            ['Plastic Crate Large', 'Packaging Materials', 'pcs', 40, 12, null, false, null, 320.00],
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

        $this->command?->info("Added {$created} random BARGO products. Total items: ".InventoryItem::query()->count());
    }
}
