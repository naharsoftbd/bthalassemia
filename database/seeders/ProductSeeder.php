<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        DB::table('product_variants')->delete();
        DB::table('products')->delete();

        $vendors = Vendor::all();

        if ($vendors->isEmpty()) {
            $this->command->warn('No vendors found. Please run VendorSeeder first.');

            return;
        }

        $products = $this->getProductData();

        foreach ($products as $productData) {
            // Assign random vendor
            $vendor = $vendors->random();

            // Create product
            $product = Product::create([
                'vendor_id' => $vendor->id,
                'name' => $productData['name'],
                'slug' => Str::slug($productData['name']).'-'.uniqid(),
                'description' => $productData['description'],
                'short_description' => $productData['short_description'],
                'base_price' => $productData['base_price'],
                'is_active' => true,
                'is_approved' => true,
                'status' => 'published',
                'tags' => json_encode($productData['tags']),
                'meta_title' => $productData['meta_title'],
                'meta_description' => $productData['meta_description'],
            ]);

            // Create variants
            foreach ($productData['variants'] as $variantData) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $variantData['sku'],
                    'name' => $variantData['name'],
                    'price' => $variantData['price'],
                    'compare_at_price' => $variantData['compare_at_price'],
                    'stock' => $variantData['stock'],
                    'low_stock_threshold' => $variantData['low_stock_threshold'],
                    'attributes' => $variantData['attributes'],
                    'barcode' => $variantData['barcode'],
                    'weight' => $variantData['weight'],
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Successfully seeded '.count($products).' products with variants.');
    }

    protected function getProductData(): array
    {
        return [
            // Electronics Category
            [
                'name' => 'Apple iPhone 15 Pro',
                'description' => 'The most advanced iPhone with titanium design, A17 Pro chip, and professional camera system. Features Action button, USB-C, and all-day battery life.',
                'short_description' => 'Titanium design, A17 Pro chip, Pro camera system',
                'base_price' => 999.00,
                'tags' => ['smartphone', 'apple', 'ios', '5g', 'camera'],
                'meta_title' => 'Apple iPhone 15 Pro - Buy Now',
                'meta_description' => 'Get the new iPhone 15 Pro with titanium design, A17 Pro chip, and professional camera system. Free shipping available.',
                'variants' => [
                    [
                        'sku' => 'IP15P-128-NAT',
                        'name' => 'Natural Titanium, 128GB',
                        'price' => 999.00,
                        'compare_at_price' => 1099.00,
                        'stock' => 45,
                        'low_stock_threshold' => 10,
                        'attributes' => [
                            'color' => 'Natural Titanium',
                            'storage' => '128GB',
                            'connectivity' => '5G',
                            'chip' => 'A17 Pro',
                        ],
                        'barcode' => '194253957301',
                        'weight' => 0.187,
                    ],
                    [
                        'sku' => 'IP15P-256-BLK',
                        'name' => 'Black Titanium, 256GB',
                        'price' => 1099.00,
                        'compare_at_price' => 1199.00,
                        'stock' => 32,
                        'low_stock_threshold' => 10,
                        'attributes' => [
                            'color' => 'Black Titanium',
                            'storage' => '256GB',
                            'connectivity' => '5G',
                            'chip' => 'A17 Pro',
                        ],
                        'barcode' => '194253957318',
                        'weight' => 0.187,
                    ],
                ],
            ],

            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'description' => 'Ultimate Android smartphone with S Pen, Snapdragon 8 Gen 3, and advanced AI features. Featuring a 200MP camera and titanium frame.',
                'short_description' => 'S Pen, 200MP camera, Snapdragon 8 Gen 3',
                'base_price' => 1199.00,
                'tags' => ['smartphone', 'samsung', 'android', '5g', 'camera'],
                'meta_title' => 'Samsung Galaxy S24 Ultra - AI Phone',
                'meta_description' => 'Samsung Galaxy S24 Ultra with S Pen, 200MP camera, and AI features. Best Android smartphone available.',
                'variants' => [
                    [
                        'sku' => 'SGS24U-256-TIT',
                        'name' => 'Titanium Gray, 256GB',
                        'price' => 1199.00,
                        'compare_at_price' => 1299.00,
                        'stock' => 28,
                        'low_stock_threshold' => 8,
                        'attributes' => [
                            'color' => 'Titanium Gray',
                            'storage' => '256GB',
                            'connectivity' => '5G',
                            'chip' => 'Snapdragon 8 Gen 3',
                        ],
                        'barcode' => '887276654321',
                        'weight' => 0.232,
                    ],
                ],
            ],

            [
                'name' => 'Sony WH-1000XM5 Wireless Headphones',
                'description' => 'Industry-leading noise cancellation with 30-hour battery life. Featuring HD Voice Pickup and multipoint connection.',
                'short_description' => 'Noise canceling, 30h battery, HD voice',
                'base_price' => 399.00,
                'tags' => ['headphones', 'wireless', 'noise-canceling', 'sony'],
                'meta_title' => 'Sony WH-1000XM5 Wireless Noise Canceling Headphones',
                'meta_description' => 'Sony WH-1000XM5 wireless headphones with industry-leading noise cancellation and 30-hour battery life.',
                'variants' => [
                    [
                        'sku' => 'SONY-XM5-BLK',
                        'name' => 'Black',
                        'price' => 399.00,
                        'compare_at_price' => 449.00,
                        'stock' => 75,
                        'low_stock_threshold' => 15,
                        'attributes' => [
                            'color' => 'Black',
                            'connectivity' => 'Bluetooth 5.2',
                            'battery_life' => '30 hours',
                            'noise_canceling' => 'Yes',
                        ],
                        'barcode' => '027242924311',
                        'weight' => 0.250,
                    ],
                    [
                        'sku' => 'SONY-XM5-SIL',
                        'name' => 'Silver',
                        'price' => 399.00,
                        'compare_at_price' => 449.00,
                        'stock' => 52,
                        'low_stock_threshold' => 15,
                        'attributes' => [
                            'color' => 'Silver',
                            'connectivity' => 'Bluetooth 5.2',
                            'battery_life' => '30 hours',
                            'noise_canceling' => 'Yes',
                        ],
                        'barcode' => '027242924328',
                        'weight' => 0.250,
                    ],
                ],
            ],

            // Fashion Category
            [
                'name' => 'Nike Air Jordan 1 Retro High',
                'description' => 'Classic basketball sneakers with premium leather construction and iconic Air Jordan design. Perfect for both sports and casual wear.',
                'short_description' => 'Classic design, premium leather, iconic style',
                'base_price' => 180.00,
                'tags' => ['sneakers', 'nike', 'jordan', 'basketball', 'shoes'],
                'meta_title' => 'Nike Air Jordan 1 Retro High - Official',
                'meta_description' => 'Authentic Nike Air Jordan 1 Retro High sneakers. Classic basketball design with premium materials.',
                'variants' => [
                    [
                        'sku' => 'AJ1-BLK-RED-9',
                        'name' => 'Black/Red, Size 9',
                        'price' => 180.00,
                        'compare_at_price' => 200.00,
                        'stock' => 15,
                        'low_stock_threshold' => 5,
                        'attributes' => [
                            'color' => 'Black/Red',
                            'size' => '9',
                            'material' => 'Leather',
                            'style' => 'High Top',
                        ],
                        'barcode' => '888834567890',
                        'weight' => 0.800,
                    ],
                    [
                        'sku' => 'AJ1-BLK-RED-10',
                        'name' => 'Black/Red, Size 10',
                        'price' => 180.00,
                        'compare_at_price' => 200.00,
                        'stock' => 12,
                        'low_stock_threshold' => 5,
                        'attributes' => [
                            'color' => 'Black/Red',
                            'size' => '10',
                            'material' => 'Leather',
                            'style' => 'High Top',
                        ],
                        'barcode' => '888834567907',
                        'weight' => 0.820,
                    ],
                    [
                        'sku' => 'AJ1-WHT-BLK-9',
                        'name' => 'White/Black, Size 9',
                        'price' => 175.00,
                        'compare_at_price' => 195.00,
                        'stock' => 8,
                        'low_stock_threshold' => 5,
                        'attributes' => [
                            'color' => 'White/Black',
                            'size' => '9',
                            'material' => 'Leather',
                            'style' => 'High Top',
                        ],
                        'barcode' => '888834567914',
                        'weight' => 0.800,
                    ],
                ],
            ],

            [
                'name' => 'Levi\'s 511 Slim Jeans',
                'description' => 'Classic slim-fit jeans made from premium denim. Comfortable stretch with modern fit and durable construction.',
                'short_description' => 'Slim fit, premium denim, comfortable stretch',
                'base_price' => 89.00,
                'tags' => ['jeans', 'levis', 'denim', 'slim-fit', 'pants'],
                'meta_title' => 'Levi\'s 511 Slim Fit Jeans - Premium Denim',
                'meta_description' => 'Levi\'s 511 slim fit jeans made from premium denim with comfortable stretch. Available in multiple sizes.',
                'variants' => [
                    [
                        'sku' => 'LEV-511-BLU-32-32',
                        'name' => 'Dark Blue, 32x32',
                        'price' => 89.00,
                        'compare_at_price' => 98.00,
                        'stock' => 25,
                        'low_stock_threshold' => 8,
                        'attributes' => [
                            'color' => 'Dark Blue',
                            'waist' => '32',
                            'length' => '32',
                            'fit' => 'Slim',
                            'material' => '98% Cotton, 2% Elastane',
                        ],
                        'barcode' => '188153011111',
                        'weight' => 0.450,
                    ],
                    [
                        'sku' => 'LEV-511-BLK-34-32',
                        'name' => 'Black, 34x32',
                        'price' => 89.00,
                        'compare_at_price' => 98.00,
                        'stock' => 18,
                        'low_stock_threshold' => 8,
                        'attributes' => [
                            'color' => 'Black',
                            'waist' => '34',
                            'length' => '32',
                            'fit' => 'Slim',
                            'material' => '98% Cotton, 2% Elastane',
                        ],
                        'barcode' => '188153011128',
                        'weight' => 0.450,
                    ],
                ],
            ],

            // Home & Kitchen
            [
                'name' => 'Instant Pot Duo Plus 9-in-1',
                'description' => '9-in-1 multi-use programmable pressure cooker. Functions as pressure cooker, slow cooker, rice cooker, yogurt maker, and more.',
                'short_description' => '9-in-1 pressure cooker, programmable, versatile',
                'base_price' => 129.00,
                'tags' => ['kitchen', 'pressure-cooker', 'instant-pot', 'cooking'],
                'meta_title' => 'Instant Pot Duo Plus 9-in-1 Pressure Cooker',
                'meta_description' => 'Instant Pot Duo Plus 9-in-1 programmable pressure cooker. Multiple cooking functions in one appliance.',
                'variants' => [
                    [
                        'sku' => 'INST-DUO-6QT',
                        'name' => '6 Quart',
                        'price' => 129.00,
                        'compare_at_price' => 149.00,
                        'stock' => 35,
                        'low_stock_threshold' => 10,
                        'attributes' => [
                            'capacity' => '6 Quart',
                            'color' => 'Stainless Steel',
                            'functions' => '9-in-1',
                            'power' => '1000W',
                        ],
                        'barcode' => '681131400101',
                        'weight' => 5.200,
                    ],
                    [
                        'sku' => 'INST-DUO-8QT',
                        'name' => '8 Quart',
                        'price' => 149.00,
                        'compare_at_price' => 169.00,
                        'stock' => 22,
                        'low_stock_threshold' => 10,
                        'attributes' => [
                            'capacity' => '8 Quart',
                            'color' => 'Stainless Steel',
                            'functions' => '9-in-1',
                            'power' => '1200W',
                        ],
                        'barcode' => '681131400118',
                        'weight' => 6.100,
                    ],
                ],
            ],

            [
                'name' => 'Dyson V11 Torque Drive Cordless Vacuum',
                'description' => 'Powerful cordless vacuum with intelligent suction and 60-minute run time. LCD screen shows performance and maintenance alerts.',
                'short_description' => 'Cordless, intelligent suction, 60min runtime',
                'base_price' => 599.00,
                'tags' => ['vacuum', 'cordless', 'dyson', 'cleaning'],
                'meta_title' => 'Dyson V11 Torque Drive Cordless Vacuum Cleaner',
                'meta_description' => 'Dyson V11 Torque Drive cordless vacuum with intelligent suction and 60-minute run time. Advanced cleaning technology.',
                'variants' => [
                    [
                        'sku' => 'DYSON-V11-BLU',
                        'name' => 'Blue',
                        'price' => 599.00,
                        'compare_at_price' => 699.00,
                        'stock' => 18,
                        'low_stock_threshold' => 5,
                        'attributes' => [
                            'color' => 'Blue/Nickel',
                            'battery_life' => '60 minutes',
                            'suction_power' => '185 AW',
                            'dustbin_capacity' => '0.76L',
                        ],
                        'barcode' => '871294301234',
                        'weight' => 3.050,
                    ],
                ],
            ],

            // Beauty & Personal Care
            [
                'name' => 'Dyson Supersonic Hair Dryer',
                'description' => 'Professional hair dryer with intelligent heat control to prevent extreme heat damage. Fast drying with smooth, shiny results.',
                'short_description' => 'Intelligent heat control, fast drying, smooth results',
                'base_price' => 429.00,
                'tags' => ['hair-dryer', 'dyson', 'beauty', 'styling'],
                'meta_title' => 'Dyson Supersonic Hair Dryer - Professional',
                'meta_description' => 'Dyson Supersonic hair dryer with intelligent heat control. Prevents heat damage while providing fast drying.',
                'variants' => [
                    [
                        'sku' => 'DYSON-HD-FUCHSIA',
                        'name' => 'Fuchsia',
                        'price' => 429.00,
                        'compare_at_price' => 499.00,
                        'stock' => 24,
                        'low_stock_threshold' => 6,
                        'attributes' => [
                            'color' => 'Fuchsia',
                            'power' => '1600W',
                            'speed_settings' => '3',
                            'heat_settings' => '4',
                        ],
                        'barcode' => '871294302345',
                        'weight' => 1.100,
                    ],
                    [
                        'sku' => 'DYSON-HD-SILVER',
                        'name' => 'Silver',
                        'price' => 429.00,
                        'compare_at_price' => 499.00,
                        'stock' => 19,
                        'low_stock_threshold' => 6,
                        'attributes' => [
                            'color' => 'Silver',
                            'power' => '1600W',
                            'speed_settings' => '3',
                            'heat_settings' => '4',
                        ],
                        'barcode' => '871294302352',
                        'weight' => 1.100,
                    ],
                ],
            ],

            // Sports & Outdoors
            [
                'name' => 'Yeti Tundra 65 Cooler',
                'description' => 'Roto-molded construction with 2-inch PermaFrost Insulation. Bear-resistant design with T-Rex Latches and NeverFail Hinge System.',
                'short_description' => 'Bear-resistant, 65qt capacity, rotomolded',
                'base_price' => 375.00,
                'tags' => ['cooler', 'yeti', 'outdoor', 'camping'],
                'meta_title' => 'Yeti Tundra 65 Cooler - Bear Resistant',
                'meta_description' => 'Yeti Tundra 65 cooler with bear-resistant design and 65-quart capacity. Perfect for outdoor adventures.',
                'variants' => [
                    [
                        'sku' => 'YETI-T65-WHT',
                        'name' => 'White',
                        'price' => 375.00,
                        'compare_at_price' => 425.00,
                        'stock' => 12,
                        'low_stock_threshold' => 4,
                        'attributes' => [
                            'color' => 'White',
                            'capacity' => '65 Quarts',
                            'ice_retention' => '5+ days',
                            'bear_resistant' => 'Yes',
                        ],
                        'barcode' => '818279012345',
                        'weight' => 23.100,
                    ],
                    [
                        'sku' => 'YETI-T65-TAN',
                        'name' => 'Tan',
                        'price' => 375.00,
                        'compare_at_price' => 425.00,
                        'stock' => 8,
                        'low_stock_threshold' => 4,
                        'attributes' => [
                            'color' => 'Tan',
                            'capacity' => '65 Quarts',
                            'ice_retention' => '5+ days',
                            'bear_resistant' => 'Yes',
                        ],
                        'barcode' => '818279012352',
                        'weight' => 23.100,
                    ],
                ],
            ],

            // Books & Media
            [
                'name' => 'The Psychology of Money',
                'description' => 'Timeless lessons on wealth, greed, and happiness. Exploring how people think about money and make financial decisions.',
                'short_description' => 'Financial wisdom, wealth building, money psychology',
                'base_price' => 18.00,
                'tags' => ['book', 'finance', 'psychology', 'money'],
                'meta_title' => 'The Psychology of Money - Morgan Housel',
                'meta_description' => 'The Psychology of Money by Morgan Housel. Timeless lessons on wealth, greed, and financial decision making.',
                'variants' => [
                    [
                        'sku' => 'BOOK-PSYMONEY-HC',
                        'name' => 'Hardcover',
                        'price' => 18.00,
                        'compare_at_price' => 22.00,
                        'stock' => 85,
                        'low_stock_threshold' => 20,
                        'attributes' => [
                            'format' => 'Hardcover',
                            'pages' => '256',
                            'author' => 'Morgan Housel',
                            'language' => 'English',
                        ],
                        'barcode' => '9780857197689',
                        'weight' => 0.450,
                    ],
                ],
            ],
        ];
    }
}
