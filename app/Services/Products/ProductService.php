<?php

namespace App\Services\Products;

use App\Interfaces\Products\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ProductService
{
    protected $products;

    public function __construct(ProductRepositoryInterface $products)
    {
        $this->products = $products;
    }

    public function list($filters)
    {
        return $this->products->paginated($filters);
    }

    public function find(int $id)
    {
        return $this->products->find($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            return $this->products->create($data);
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            return $this->products->update($id, $data);
        });
    }

    public function delete(int $id)
    {
        return $this->products->delete($id);
    }
}
