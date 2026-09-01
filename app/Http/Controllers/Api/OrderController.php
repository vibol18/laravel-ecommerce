<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CheckoutRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use ApiResponses;

    protected const TAX_RATE = 0.10;

    public function index(Request $request)
    {
        $query = Order::with('items', 'payment')
            ->where('user_id', $request->user()->id);

        $orders = $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15));

        return $this->paginatedResponse(OrderResource::collection($orders), 'Orders retrieved successfully');
    }

    public function adminIndex(Request $request)
    {
        $query = Order::query()
            ->with('user', 'items', 'payment')
            ->when($request->has('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->has('search'), function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('order_number', 'like', '%'.$request->search.'%')
                        ->orWhereHas('user', function ($userQuery) use ($request) {
                            $userQuery->where('name', 'like', '%'.$request->search.'%')
                                ->orWhere('email', 'like', '%'.$request->search.'%');
                        });
                });
            });

        $orders = $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15));

        return $this->paginatedResponse(OrderResource::collection($orders), 'Orders retrieved successfully');
    }

    public function show(Request $request, Order $order)
    {
        if ($request->user()->isAdmin() || $order->user_id === $request->user()->id) {
            $order->load('items', 'payment', 'user');

            return $this->successResponse(new OrderResource($order), 'Order retrieved successfully');
        }

        return $this->errorResponse('Unauthorized', 403);
    }

    public function checkout(CheckoutRequest $request)
    {
        $userId = $request->user()->id;

        $cartItems = CartItem::with('product')
            ->where('user_id', $userId)
            ->get();

        if ($cartItems->isEmpty()) {
            return $this->errorResponse('Your cart is empty', 422);
        }

        foreach ($cartItems as $item) {
            if ($item->product->status !== 'active') {
                return $this->errorResponse("Product '{$item->product->name}' is not available", 422);
            }
            if ($item->product->stock < $item->quantity) {
                return $this->errorResponse(
                    "Insufficient stock for '{$item->product->name}'. Only {$item->product->stock} available.",
                    422
                );
            }
        }

        $subtotal = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });
        $tax = round($subtotal * self::TAX_RATE, 2);
        $shippingCost = $request->input('shipping_cost', 0);
        $total = round($subtotal + $tax + $shippingCost, 2);

        $order = DB::transaction(function () use ($request, $userId, $cartItems, $subtotal, $tax, $shippingCost, $total) {
            $order = Order::create([
                'user_id' => $userId,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'shipping_address' => $request->shipping_address,
                'billing_address' => $request->billing_address,
                'notes' => $request->notes,
            ]);

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'price' => $item->product->price,
                    'quantity' => $item->quantity,
                    'total' => round($item->product->price * $item->quantity, 2),
                ]);

                $item->product->decrement('stock', $item->quantity);
                $item->delete();
            }

            Payment::create([
                'order_id' => $order->id,
                'method' => $request->payment_method,
                'status' => 'pending',
                'amount' => $total,
            ]);

            return $order;
        });

        return $this->successResponse(
            new OrderResource($order->load('items', 'payment')),
            'Order placed successfully',
            201
        );
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $newStatus = $request->status;
        $timestamps = [
            'paid' => 'paid_at',
            'shipped' => 'shipped_at',
            'completed' => 'completed_at',
        ];

        $data = ['status' => $newStatus];
        if (isset($timestamps[$newStatus])) {
            $data[$timestamps[$newStatus]] = now();
        }

        if ($newStatus === 'cancelled' && $order->status === 'pending') {
            foreach ($order->items as $item) {
                $item->product()->increment('stock', $item->quantity);
            }
        }

        $order->update($data);

        if ($newStatus === 'paid' && $order->payment) {
            $order->payment()->update(['status' => 'completed', 'paid_at' => now()]);
        }

        return $this->successResponse(
            new OrderResource($order->load('items', 'payment', 'user')),
            'Order status updated successfully'
        );
    }
}
