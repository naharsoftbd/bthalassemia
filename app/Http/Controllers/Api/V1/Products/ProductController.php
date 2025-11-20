<?php

namespace App\Http\Controllers\Api\V1\Products;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Api\V1\Products\StoreProductRequest;
use App\Http\Requests\Api\V1\Products\UpdateProductRequest;
use App\Http\Resources\V1\Products\ProductResource;
use App\Services\Products\ProductService;
use Illuminate\Http\Request;

class ProductController extends BaseController
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;

        $this->middleware('permission:view products|view own products')->only('index', 'show');
        $this->middleware('permission:create products|create own products')->only('store');
        $this->middleware('permission:edit products|edit own products')->only('update');
        $this->middleware('permission:delete products|delete own products')->only('destroy');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'category_id', 'per_page']);

        $products = $this->productService->list($filters);

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request)
    {
        $product = $this->productService->create($request->validated());

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function show($id)
    {
        $product = $this->productService->find($id);

        return (new ProductResource($product->load('variants')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $product = $this->productService->update($id, $request->validated());

        return new ProductResource($product->refresh());
    }

    public function destroy($id)
    {
        $this->productService->delete($id);

        return response()->json(['message' => 'Deleted'], 200);
    }
}
