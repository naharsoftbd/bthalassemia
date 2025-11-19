<?php

namespace App\Repositories\Products;

use App\Interfaces\Products\ProductRepositoryInterface;
use App\Models\Product;

class ProductRepository implements ProductRepositoryInterface
{
    protected $vendor_id;
    public function __construct()
    {
        if (auth()?->user()?->hasRole('vendor')) {
            $this->vendor_id = auth()->user()->vendor->id;
        }
    }

    public function all()
    {
        return Product::with('variants')->get();
    }

    public function paginated($filters)
    {
        $perPage = $filters['per_page'] ?? 10;

        $products = Product::query();

        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            
            $products->where(function ($query) use ($searchTerm) {
                // Full-text search on product fields
                if (config('database.default') === 'mysql') {
                    $query->whereRaw(
                        "MATCH(products.name, products.description, products.short_description) AGAINST(? IN BOOLEAN MODE)",
                        [$this->prepareSearchTerm($searchTerm)]
                    );
                } else {
                    $query->whereFullText(['name', 'description', 'short_description'], $searchTerm);
                }

                // Also search in variants
                $query->orWhereHas('variants', function ($variantQuery) use ($searchTerm) {
                    if (config('database.default') === 'mysql') {
                        $variantQuery->whereRaw(
                            "MATCH(sku, name) AGAINST(? IN BOOLEAN MODE)",
                            [$this->prepareSearchTerm($searchTerm)]
                        );
                    } else {
                        $variantQuery->whereFullText(['sku', 'name'], $searchTerm);
                    }
                });

                // Fallback LIKE search for tags
                $query->orWhere('tags', 'like', "%\"{$searchTerm}\"%");
            });
        }

        // Other filters
        if (!empty($filters['category_id'])) {
            $products->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['vendor_id'])) {
            $products->where('vendor_id', $filters['vendor_id']);
        }

        if (isset($filters['is_active'])) {
            $products->where('is_active', (bool)$filters['is_active']);
        }

        if ($this->vendor_id) {
            // Vendors can only see their own products
            $products = $products->forVendor($this->vendor_id);
        } else {
            // Admins can see all products
            $products =  $products->with('vendor');
        }

        $products = $products->with(['variants', 'vendor', 'vendor.user']);

        return $products->latest()->paginate($perPage)->withQueryString();

    }

    public function find(int $id)
    {
        return Product::with('variants')->findOrFail($id);
    }

    public function create(array $data)
    {
        if($this->vendor_id){
            $data += ['vendor_id' => $this->vendor_id];
        }
        

        $product = Product::create($data);

        if (isset($data['variants'])) {
            $this->syncVariants($product->id, $data['variants']);
        }

        return $product->load('variants');
    }

    public function update(int $id, array $data)
    {
        $product = Product::findOrFail($id);

        $product->update($data);

        if (isset($data['variants'])) {
            $this->syncVariants($product->id, $data['variants']);
        }

        return $product->load('variants');
    }

    public function delete(int $id)
    {
        $product = Product::findOrFail($id);

        return $product->delete();
    }

    public function syncVariants(int $productId, array $variants)
    {
        $product = Product::findOrFail($productId);

        // clear removed variants
        $existingIds = collect($variants)
            ->filter(fn ($v) => isset($v['id']))
            ->pluck('id')
            ->toArray();

        $product->variants()
            ->whereNotIn('id', $existingIds)
            ->delete();

        foreach ($variants as $variant) {
            if (isset($variant['id'])) {
                // update
                $product->variants()->where('id', $variant['id'])->update($variant);
            } else {
                // create
                $product->variants()->create($variant);
            }
        }

        return $product->variants;
    }

    /**
     * Prepare search term for full-text search
     */
    protected function prepareSearchTerm(string $searchTerm): string
    {
        // Remove special characters and prepare for boolean mode
        $cleaned = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $searchTerm);
        
        // For MySQL boolean mode, add + before each word
        if (config('database.default') === 'mysql') {
            $words = array_filter(explode(' ', $cleaned));
            $words = array_map(function($word) {
                return '+' . $word . '*';
            }, $words);
            
            return implode(' ', $words);
        }
        
        return $cleaned;
    }
}
