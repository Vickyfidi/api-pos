<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetProductsRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\PaginatedResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(GetProductsRequest $request)
    {
        $products = Product::with('category')
            ->search($request->input('search'))
            ->latest()->paginate($request->input('limit', 10));

        return ApiResponse::success(new PaginatedResource($products, ProductResource::class));
    }

    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());

        return ApiResponse::success(
            new ProductResource($product->load('category')),
            'Product created successfully',
            Response::HTTP_CREATED
        );
    }

    public function show(string $id)
    {
        $product = Product::with('category')->find($id);

        if (!$product) {
            return ApiResponse::error(
                'Product not found',
                Response::HTTP_NOT_FOUND
            );
        }

        return ApiResponse::success(
            new ProductResource($product),
            'Product Details'
        );
    }

    public function update(UpdateProductRequest $request, string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return ApiResponse::error('Product not found');
        }

        $product->update($request->validated());

        return ApiResponse::success(
            new ProductResource($product->load('category')),
            'Product updated successfully'
        );
    }

    public function destroy(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return ApiResponse::error('Product not found');
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();
        return ApiResponse::success(null, 'Product deleted successfully');
    }

    public function options(GetProductsRequest $request)
    {
        $products = Product::select('id', 'name')
            ->search($request->input('search'))
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            ProductResource::collection($products),
            'Product list'
        );
    }
}
