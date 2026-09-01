<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartItemResource;
use App\Models\CartItem;
use App\Models\Product;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $cartItems = CartItem::with('product.category')
            ->where('user_id', $request->user()->id)
            ->get();

        $subtotal = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        return $this->successResponse([
            'items' => CartItemResource::collection($cartItems),
            'items_count' => $cartItems->sum('quantity'),
            'subtotal' => round((float) $subtotal, 2),
        ], 'Cart retrieved successfully');
    }

    public function add(AddToCartRequest $request)
    {
        $userId = $request->user()->id;
        $product = Product::findOrFail($request->product_id);

        if ($product->status !== 'active') {
            return $this->errorResponse('Product is not available for purchase', 422);
        }

        if ($product->stock < $request->quantity) {
            return $this->errorResponse("Insufficient stock. Only {$product->stock} available.", 422);
        }

        $cartItem = CartItem::firstOrCreate(
            [
                'user_id' => $userId,
                'product_id' => $request->product_id,
            ],
            [
                'quantity' => 0,
            ]
        );

        $newQuantity = $cartItem->quantity + $request->quantity;
        if ($newQuantity > $product->stock) {
            return $this->errorResponse("Cannot add more than available stock ({$product->stock}).", 422);
        }

        $cartItem->update(['quantity' => $newQuantity]);

        return $this->successResponse(
            new CartItemResource($cartItem->load('product.category')),
            'Item added to cart successfully',
            201
        );
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem)
    {
        if ($cartItem->user_id !== $request->user()->id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $product = $cartItem->product;
        if ($request->quantity > $product->stock) {
            return $this->errorResponse("Insufficient stock. Only {$product->stock} available.", 422);
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return $this->successResponse(
            new CartItemResource($cartItem->load('product.category')),
            'Cart item updated successfully'
        );
    }

    public function remove(Request $request, CartItem $cartItem)
    {
        if ($cartItem->user_id !== $request->user()->id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $cartItem->delete();

        return $this->successResponse(null, 'Item removed from cart successfully');
    }

    public function clear(Request $request)
    {
        CartItem::where('user_id', $request->user()->id)->delete();

        return $this->successResponse(null, 'Cart cleared successfully');
    }
}
