<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $orders = $request->user()->orders()->with('payment');
        $query = Order::with('payment')
            ->where('user_id', $request->user()->id);

        $payments = $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15));

        return $this->paginatedResponse($payments, 'Payments retrieved successfully');
    }

    public function markPaid(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        if ($order->status === 'cancelled') {
            return $this->errorResponse('Cannot mark a cancelled order as paid', 422);
        }

        $order->update(['status' => 'paid', 'paid_at' => now()]);

        if ($order->payment) {
            $order->payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'transaction_id' => $request->transaction_id ?? 'TXN-'.strtoupper(Str::random(10)),
            ]);
        }

        return $this->successResponse(
            new OrderResource($order->load('payment', 'items')),
            'Payment marked as completed'
        );
    }

    public function methods()
    {
        return $this->successResponse([
            ['value' => 'cod', 'label' => 'Cash on Delivery'],
            ['value' => 'bank_transfer', 'label' => 'Bank Transfer'],
            ['value' => 'credit_card', 'label' => 'Credit Card'],
            ['value' => 'paypal', 'label' => 'PayPal'],
            ['value' => 'stripe', 'label' => 'Stripe'],
        ], 'Payment methods retrieved successfully');
    }
}
