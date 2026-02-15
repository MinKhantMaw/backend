<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = [
        'status',
    ];

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'slug',
        'description',
        'price',
        'compare_at_price',
        'stock',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primaryImage(): HasMany
    {
        return $this->images()->where('is_primary', true);
    }

    public function getStatusAttribute(): string
    {
        return $this->is_active ? 'active' : 'inactive';
    }
}
