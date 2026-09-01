<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'compare_price' => $this->compare_price !== null ? (float) $this->compare_price : null,
            'stock' => $this->stock,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => $this->images,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'weight' => $this->weight !== null ? (float) $this->weight : null,
            'average_rating' => $this->average_rating ? round((float) $this->average_rating, 2) : null,
            'reviews_count' => $this->reviews_count,
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
