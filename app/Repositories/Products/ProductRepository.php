<?php

namespace App\Repositories\Products;

use App\Interfaces\Products\ProductRepositoryInterface;
use App\Models\Product;

class ProductRepository implements ProductRepositoryInterface
{
    public function all()
    {
        return Product::with('variants')->get();
    }

    public function paginated($filters)
    {
        $perPage = $filters['per_page'] ?? 10;

        $products = Product::query();

        if (! empty($filters['search'])) {
            $products->where('name', 'like', '%'.$filters['search'].'%');
            $products->orWhere('slug', 'like', '%'.$filters['search'].'%');
            $products->orWhere('description', 'like', '%'.$filters['search'].'%');
        }

        $products = $products->with('variants');

        return $products->latest()->paginate($perPage)->withQueryString();

    }

    public function find(int $id)
    {
        return Product::with('variants')->findOrFail($id);
    }

    public function create(array $data)
    {
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
}
