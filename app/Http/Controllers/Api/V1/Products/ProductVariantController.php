<?php

namespace App\Http\Controllers\Api\V1\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Products\StoreVariantRequest;
use App\Http\Requests\V1\Products\UpdateVariantRequest;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ProductVariant::with('product');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        return ProductVariantResource::collection($query->paginate(15));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVariantRequest $request)
    {
        $data = $request->validated();
        $variant = ProductVariant::create($data);

        return new ProductVariantResource($variant);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductVariant $variant)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVariantRequest $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
