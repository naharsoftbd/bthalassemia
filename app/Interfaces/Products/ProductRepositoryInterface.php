<?php

namespace App\Interfaces\Products;

interface ProductRepositoryInterface
{
    public function all();

    public function paginated($filters);

    public function find(int $id);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id);

    public function syncVariants(int $productId, array $variants);
}
