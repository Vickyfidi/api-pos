<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetTransactionsRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\PaginatedResource;
use App\Http\Resources\TransactionResource;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends Controller
{
    public function index(GetTransactionsRequest $request)
    {
        $transactions = Transaction::with('customer', 'items.product')
            ->search($request->input('search'))
            ->latest()->paginate($request->input('limit', 10));

        return ApiResponse::success(new PaginatedResource($transactions, TransactionResource::class), 'Transction List');
    }

    public function store(StoreTransactionRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $code = 'INV-' . now()->format('Ymd') . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);

            $itemSubtotals = [];

            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                if (!$product) {
                    throw new \Exception("Product with ID {$item['product_id']} not found.");
                }

                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product '{$product->name}'. Available: {$product->stock}, requested: {$item['quantity']}.");
                }

                $itemSubtotals[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'subtotal' => $product->price * $item['quantity'],
                ];
            }

            $subtotal = array_sum(array_column($itemSubtotals, 'subtotal'));
            $tax = round($subtotal * 0.11, 2);
            $total = $subtotal + $tax;

            $transaction = Transaction::create([
                'code' => $code,
                'customer_id' => $validated['customer_id'],
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ]);

            $itemsData = [];
            foreach ($itemSubtotals as $item) {
                $itemsData[] = [
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product']->id,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $item['product']->decrement('stock', $item['quantity']);
            }

            $transaction->items()->insert($itemsData);

            DB::commit();

            $transaction->load('customer', 'items.product');

            return ApiResponse::success(
                new TransactionResource($transaction),
                'Transaction created successfully',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::error(
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    public function show(string $id)
    {
        $transaction = Transaction::with('customer', 'items.product')->find($id);

        if (!$transaction) {
            return ApiResponse::error(
                'Transaction not found',
                Response::HTTP_NOT_FOUND
            );
        }

        return ApiResponse::success(
            new TransactionResource($transaction),
            'Transaction Details'
        );
    }
}
