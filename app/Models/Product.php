<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'brand_id',
    'category_id',
    'name',
    'slug',
    'sku',
    'thumbnail',
    'price',
    'sale_price',
    'stock',
    'short_description',
    'description',
    'is_featured',
    'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $appends = ['thumbnail_url', 'effective_price', 'discount_percent', 'is_on_sale'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Apply the storefront listing filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(filled($filters['q'] ?? null), function (Builder $builder) use ($filters) {
                $term = '%'.trim((string) $filters['q']).'%';

                $builder->where(function (Builder $inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('short_description', 'like', $term);
                });
            })
            ->when(filled($filters['category'] ?? null), fn (Builder $builder) => $builder
                ->whereHas('category', fn ($category) => $category->where('slug', $filters['category'])))
            ->when(filled($filters['brand'] ?? null), fn (Builder $builder) => $builder
                ->whereHas('brand', fn ($brand) => $brand->where('slug', $filters['brand'])))
            ->when(! empty($filters['featured']), fn (Builder $builder) => $builder->where('is_featured', true))
            ->when(filled($filters['min_price'] ?? null), fn (Builder $builder) => $builder
                ->whereRaw('COALESCE(sale_price, price) >= ?', [(float) $filters['min_price']]))
            ->when(filled($filters['max_price'] ?? null), fn (Builder $builder) => $builder
                ->whereRaw('COALESCE(sale_price, price) <= ?', [(float) $filters['max_price']]))
            ->when(filled($filters['sort'] ?? null), function (Builder $builder) use ($filters) {
                match ($filters['sort']) {
                    'price_asc' => $builder->orderByRaw('COALESCE(sale_price, price) asc'),
                    'price_desc' => $builder->orderByRaw('COALESCE(sale_price, price) desc'),
                    'name' => $builder->orderBy('name'),
                    default => $builder->latest(),
                };
            }, fn (Builder $builder) => $builder->latest());
    }

    protected function effectivePrice(): Attribute
    {
        return Attribute::get(function (): float {
            $sale = $this->sale_price !== null ? (float) $this->sale_price : null;
            $price = (float) $this->price;

            return $sale !== null && $sale > 0 && $sale < $price ? $sale : $price;
        });
    }

    protected function isOnSale(): Attribute
    {
        return Attribute::get(fn (): bool => $this->effective_price < (float) $this->price);
    }

    protected function discountPercent(): Attribute
    {
        return Attribute::get(function (): int {
            $price = (float) $this->price;
            $effective = $this->effective_price;

            if ($price <= 0 || $effective >= $price) {
                return 0;
            }

            return (int) round((($price - $effective) / $price) * 100);
        });
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->thumbnail
                ? '/'.ltrim($this->thumbnail, '/')
                : '/images/products/placeholder.svg'
        );
    }
}
