<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        foreach (['Admin', 'Staff', 'Customer'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@deprudent.test'],
            [
                'name' => 'De Prudent Admin',
                'password' => Hash::make('password123'),
            ]
        );
        $admin->assignRole('Admin');

        // Categories
        $categories = collect(['Electronics', 'Fashion', 'Books', 'Home & Living'])
            ->map(fn ($name) => Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => "{$name} products"]
            ));

        // Sample products
        if (Product::count() === 0) {
            foreach ($categories as $category) {
                for ($i = 1; $i <= 3; $i++) {
                    Product::create([
                        'name' => "{$category->name} Item {$i}",
                        'slug' => Str::slug("{$category->name} Item {$i}") . '-' . Str::random(4),
                        'description' => "Sample {$category->name} product #{$i}",
                        'price' => rand(1500, 25000),
                        'sku' => strtoupper(Str::random(8)),
                        'stock_quantity' => rand(0, 40),
                        'category_id' => $category->id,
                        'is_active' => true,
                    ]);
                }
            }
        }

        // Testimonials
        if (Testimonial::count() === 0) {
            Testimonial::insert([
                [
                    'client_name' => 'Amaka O.',
                    'client_position' => 'Verified Buyer',
                    'content' => 'Fast delivery and great product quality.',
                    'is_active' => true,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'client_name' => 'Tunde A.',
                    'client_position' => 'Verified Buyer',
                    'content' => 'Smooth checkout experience with Paystack.',
                    'is_active' => true,
                    'sort_order' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
