<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetProductCategoriesRequest;
use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Http\Resources\PaginatedResource;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(GetProductCategoriesRequest $request)
    {
        $categories  = ProductCategory::search($request->input('search'))
            ->latest()->paginate($request->input('limit', 10));

        return ApiResponse::success(new PaginatedResource($categories, ProductCategoryResource::class));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductCategoryRequest $request)
    {
        $category = ProductCategory::create($request->validated());

        return ApiResponse::success(
            new ProductCategoryResource($category),
            'product Category created Successfuly',
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = ProductCategory::find($id);

        if (!$category) {
            return ApiResponse::error(
                'Product Category not found',
                Response::HTTP_NOT_FOUND
            );
        }

        return ApiResponse::success(
            new ProductCategoryResource($category),
            'Product Category Details'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductCategoryRequest $request, string $id)
    {
        $category = ProductCategory::find($id);

        if (!$category) {
            return  ApiResponse::error(
                'Product category not found'
            );
        }

        $category->update($request->validated());

        return ApiResponse::success(
            new ProductCategoryResource($category),
            'Product category updated successfuly'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = ProductCategory::find($id);

        if (!$category) {
            return   ApiResponse::error(
                'Product category not found'
            );
        }

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();
        return ApiResponse::success(null, 'product category deleted successfuly');
    }

    public function options(GetProductCategoriesRequest $request)
    {
        $categories = ProductCategory::select('id', 'name')
            ->search($request->input('search'))
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            ProductCategoryResource::collection($categories),
            'Product category list'
        );
    }
}
