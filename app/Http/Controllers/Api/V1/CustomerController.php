<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetCustomersRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\PaginatedResource;
use App\Models\Customer;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    public function index(GetCustomersRequest $request)
    {
        $customers = Customer::search($request->input('search'))
            ->latest()->paginate($request->input('limit', 10));

        return ApiResponse::success(new PaginatedResource($customers, CustomerResource::class));
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer = Customer::create($request->validated());

        return ApiResponse::success(
            new CustomerResource($customer),
            'Customer created successfully',
            Response::HTTP_CREATED
        );
    }

    public function show(string $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return ApiResponse::error(
                'Customer not found',
                Response::HTTP_NOT_FOUND
            );
        }

        return ApiResponse::success(
            new CustomerResource($customer),
            'Customer Details'
        );
    }

    public function update(UpdateCustomerRequest $request, string $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return ApiResponse::error('Customer not found');
        }

        $customer->update($request->validated());

        return ApiResponse::success(
            new CustomerResource($customer),
            'Customer updated successfully'
        );
    }

    public function destroy(string $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return ApiResponse::error('Customer not found');
        }

        $customer->delete();
        return ApiResponse::success(null, 'Customer deleted successfully');
    }

    public function options(GetCustomersRequest $request)
    {
        $customers = Customer::select('id', 'name')
            ->search($request->input('search'))
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            CustomerResource::collection($customers),
            'Customer list'
        );
    }
}
