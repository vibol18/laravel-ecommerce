<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'transaction_id' => $this->transaction_id,
            'method' => $this->method,
            'status' => $this->status,
            'amount' => (float) $this->amount,
            'metadata' => $this->metadata,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
        ];
    }
}
