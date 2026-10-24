<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'thumbnail' => $this->thumbnail_url,
            'price' => (float) $this->price,
            'sale_price' => $this->sale_price !== null ? (float) $this->sale_price : null,
            'effective_price' => $this->effective_price,
            'discount_percent' => $this->discount_percent,
            'is_on_sale' => $this->is_on_sale,
            'stock' => $this->stock,
            'short_description' => $this->short_description,
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'brand' => $this->whenLoaded('brand', fn () => new BrandResource($this->brand)),
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
