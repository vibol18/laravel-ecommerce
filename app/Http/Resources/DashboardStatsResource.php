<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'total_sales' => $this['total_sales'],
            'total_orders' => $this['total_orders'],
            'total_products' => $this['total_products'],
            'total_customers' => $this['total_customers'],
            'total_categories' => $this['total_categories'],
            'pending_orders' => $this['pending_orders'],
            'low_stock_products' => $this['low_stock_products'],
            'recent_orders' => OrderResource::collection($this['recent_orders']),
        ];
    }
}
