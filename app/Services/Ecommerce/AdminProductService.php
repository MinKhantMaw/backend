<?php

namespace App\Services\Ecommerce;

use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\ProductImage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminProductService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Product::query()->with(['category', 'images'])->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function create(array $payload): Product
    {
        return DB::transaction(function () use ($payload) {
            return Product::query()->create($payload);
        });
    }

    public function update(Product $product, array $payload): Product
    {
        return DB::transaction(function () use ($product, $payload) {
            $product->fill($payload)->save();

            return $product->fresh(['category', 'images']);
        });
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function uploadImage(Product $product, UploadedFile $file, bool $isPrimary = false): ProductImage
    {
        return DB::transaction(function () use ($product, $file, $isPrimary) {
            if ($isPrimary) {
                $product->images()->update(['is_primary' => false]);
            }

            $path = $file->storeAs(
                'products/'.$product->id,
                Str::uuid()->toString().'.'.$file->getClientOriginalExtension(),
                'public'
            );

            return $product->images()->create([
                'url' => Storage::disk('public')->url($path),
                'sort_order' => (int) ($product->images()->max('sort_order') ?? 0) + 1,
                'is_primary' => $isPrimary,
            ]);
        });
    }
}
