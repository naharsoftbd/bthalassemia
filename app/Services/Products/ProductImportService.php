<?php

namespace App\Services\Products;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductImportService
{
    protected $importResults = [
        'successful' => 0,
        'failed' => 0,
        'errors' => [],
        'warnings' => [],
    ];

    public function importProducts(UploadedFile $file, ?int $vendorId = null, bool $updateExisting = false): array
    {
        $this->importResults = [
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
            'warnings' => [],
            'processed' => 0,
        ];

        try {
            $rows = $this->readCSV($file);

            if (empty($rows)) {
                $this->importResults['errors'][] = 'CSV file is empty or could not be read.';

                return $this->importResults;
            }

            // Validate headers
            $headers = array_keys($rows[0]);
            $headerValidation = $this->validateHeaders($headers);
            if (! $headerValidation['valid']) {
                $this->importResults['errors'] = array_merge($this->importResults['errors'], $headerValidation['errors']);

                return $this->importResults;
            }

            // Process each row
            foreach ($rows as $index => $row) {
                $this->processRow($row, $index + 2, $vendorId, $updateExisting);
            }

            return $this->importResults;

        } catch (\Exception $e) {
            Log::error('Product import failed: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->importResults['errors'][] = 'Import failed: '.$e->getMessage();

            return $this->importResults;
        }
    }

    protected function readCSV(UploadedFile $file): array
    {
        $rows = [];
        $handle = fopen($file->getPathname(), 'r');

        if (! $handle) {
            return [];
        }

        // Read and clean headers
        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);

            return [];
        }

        $headers = array_map(function ($header) {
            return trim(strtolower($header));
        }, $headers);

        while (($data = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (empty(array_filter($data, function ($value) {
                return $value !== null && $value !== '';
            }))) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $value = isset($data[$index]) ? $this->cleanValue(trim($data[$index])) : '';
                $row[$header] = $value;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    protected function cleanValue($value)
    {
        if ($value === '') {
            return null;
        }

        // Remove quotes if they wrap the entire value
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
        }

        return $value;
    }

    protected function validateHeaders(array $headers): array
    {
        $requiredHeaders = ['name', 'sku', 'price', 'stock'];
        $missingHeaders = array_diff($requiredHeaders, $headers);

        if (! empty($missingHeaders)) {
            return [
                'valid' => false,
                'errors' => ['Missing required headers: '.implode(', ', $missingHeaders)],
            ];
        }

        return ['valid' => true, 'errors' => []];
    }

    protected function processRow(array $row, int $lineNumber, ?int $vendorId, bool $updateExisting): void
    {
        $this->importResults['processed']++;

        try {
            // Validate row data
            $validation = $this->validateRow($row, $lineNumber);
            if (! $validation['valid']) {
                $this->importResults['failed']++;
                $this->importResults['errors'][] = "Line {$lineNumber}: ".implode(', ', $validation['errors']);

                return;
            }

            // Prepare data
            $productData = $this->prepareProductData($row, $vendorId);
            $variantData = $this->prepareVariantData($row);

            DB::transaction(function () use ($productData, $variantData, $updateExisting, $lineNumber, $row) {
                // Find or create product
                $product = $this->findOrCreateProduct($productData, $updateExisting, $row['name']);

                // Create or update variant
                $this->createOrUpdateVariant($product, $variantData, $updateExisting, $lineNumber, $row['sku']);

                $this->importResults['successful']++;
            });

        } catch (\Exception $e) {
            $this->importResults['failed']++;
            $this->importResults['errors'][] = "Line {$lineNumber}: ".$e->getMessage();
            Log::error("Import error on line {$lineNumber}: ".$e->getMessage());
        }
    }

    protected function validateRow(array $row, int $lineNumber): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'base_price' => 'nullable|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'variant_name' => 'nullable|string|max:255',
            'attributes' => 'nullable|string',
            'weight' => 'nullable|numeric|min:0',
            'barcode' => 'nullable|string|max:255',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'is_active' => 'nullable|string|in:true,false,1,0,yes,no',
            'tags' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ];

        $validator = Validator::make($row, $rules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->all(),
            ];
        }

        // Additional validation for attributes
        if (! empty($row['attributes'])) {
            $attributesValidation = $this->validateAttributes($row['attributes'], $lineNumber);
            if (! $attributesValidation['valid']) {
                return $attributesValidation;
            }
        }

        return ['valid' => true, 'errors' => []];
    }

    protected function validateAttributes(string $attributes, int $lineNumber): array
    {
        try {
            // Fix common JSON formatting issues
            $attributes = $this->fixJsonFormat($attributes);

            $decoded = json_decode($attributes, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'valid' => false,
                    'errors' => ['Invalid JSON format in attributes: '.json_last_error_msg()],
                ];
            }

            if (! is_array($decoded)) {
                return [
                    'valid' => false,
                    'errors' => ['Attributes must be a valid JSON object'],
                ];
            }

            return ['valid' => true, 'errors' => []];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => ['Failed to parse attributes: '.$e->getMessage()],
            ];
        }
    }

    protected function fixJsonFormat(string $json): string
    {
        // Replace single quotes with double quotes for proper JSON
        $json = str_replace("'", '"', $json);

        // Fix common Excel/CSV JSON issues
        $json = preg_replace('/""/', '"', $json); // Fix double quotes

        return $json;
    }

    protected function prepareProductData(array $row, ?int $vendorId): array
    {
        // Generate unique slug
        $slug = Str::slug($row['name']);
        $uniqueSlug = $slug.'-'.uniqid();

        return [
            'vendor_id' => $vendorId,
            'name' => $row['name'],
            'slug' => $uniqueSlug,
            'description' => $row['description'] ?? null,
            'short_description' => $row['short_description'] ?? null,
            'base_price' => $row['base_price'] ?? null,
            'is_active' => $this->parseBoolean($row['is_active'] ?? true),
            'tags' => ! empty($row['tags']) ? json_encode(explode(',', $row['tags'])) : null,
            'meta_title' => $row['meta_title'] ?? null,
            'meta_description' => $row['meta_description'] ?? null,
        ];
    }

    protected function prepareVariantData(array $row): array
    {
        // Parse attributes JSON
        $attributes = null;
        if (! empty($row['attributes'])) {
            try {
                $attributes = $this->fixJsonFormat($row['attributes']);
                $decoded = json_decode($attributes, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $attributes = json_encode($decoded);
                }
            } catch (\Exception $e) {
                // If JSON parsing fails, store as null
                $attributes = null;
            }
        }

        return [
            'sku' => $row['sku'],
            'name' => $row['variant_name'] ?? 'Default',
            'price' => (float) $row['price'],
            'compare_at_price' => ! empty($row['compare_at_price']) ? (float) $row['compare_at_price'] : null,
            'stock' => (int) $row['stock'],
            'low_stock_threshold' => ! empty($row['low_stock_threshold']) ? (int) $row['low_stock_threshold'] : 5,
            'attributes' => $attributes,
            'barcode' => $row['barcode'] ?? null,
            'weight' => ! empty($row['weight']) ? (float) $row['weight'] : null,
            'is_active' => $this->parseBoolean($row['is_active'] ?? true),
        ];
    }

    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        $value = strtolower((string) $value);

        return in_array($value, ['true', '1', 'yes', 'y']);
    }

    protected function findOrCreateProduct(array $productData, bool $updateExisting, string $productName): Product
    {
        if ($updateExisting) {
            $product = Product::where('name', $productName)
                ->where('vendor_id', $productData['vendor_id'])
                ->first();

            if ($product) {
                $product->update($productData);

                return $product;
            }
        }

        // Create new product
        return Product::create($productData);
    }

    protected function createOrUpdateVariant(Product $product, array $variantData, bool $updateExisting, int $lineNumber, string $sku): void
    {
        if ($updateExisting) {
            $variant = ProductVariant::where('sku', $sku)->first();

            if ($variant) {
                // Check if variant belongs to the same product
                if ($variant->product_id !== $product->id) {
                    $this->importResults['warnings'][] = "Line {$lineNumber}: SKU '{$sku}' exists for a different product. Creating new variant.";
                } else {
                    $variant->update($variantData);

                    return;
                }
            }
        }

        // Create new variant
        try {
            $product->variants()->create($variantData);
        } catch (\Exception $e) {
            throw new \Exception('Failed to create variant: '.$e->getMessage());
        }
    }

    /**
     * Get CSV template for download
     */
    public function getTemplate(): array
    {
        return [
            [
                'name' => 'Wireless Headphones',
                'sku' => 'HP-001',
                'price' => '99.99',
                'stock' => '100',
                'description' => 'Premium wireless headphones with noise cancellation',
                'short_description' => 'Noise-cancelling wireless headphones',
                'base_price' => '119.99',
                'compare_at_price' => '149.99',
                'variant_name' => 'Black',
                'attributes' => '{"color": "black", "connectivity": "wireless"}',
                'weight' => '0.5',
                'barcode' => '1234567890123',
                'low_stock_threshold' => '5',
                'is_active' => '1',
                'tags' => 'electronics,audio',
                'meta_title' => 'Wireless Headphones',
                'meta_description' => 'Premium wireless headphones with noise cancellation technology',
            ],
            [
                'name' => 'Smart Watch',
                'sku' => 'SW-001',
                'price' => '199.99',
                'stock' => '50',
                'description' => 'Advanced smartwatch with health tracking features',
                'short_description' => 'Health tracking smartwatch',
                'base_price' => '229.99',
                'compare_at_price' => '299.99',
                'variant_name' => 'Blue, Large',
                'attributes' => '{"color": "blue", "size": "large"}',
                'weight' => '0.3',
                'barcode' => '9876543210987',
                'low_stock_threshold' => '10',
                'is_active' => '1',
                'tags' => 'electronics,wearables',
                'meta_title' => 'Smart Watch',
                'meta_description' => 'Advanced smartwatch with comprehensive health tracking',
            ],
        ];
    }
}
