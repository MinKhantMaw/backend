<?php

namespace App\Services\Ecommerce;

use App\Models\Ecommerce\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductCatalogService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 24), 100);

        $query = Product::query()
            ->with(['category', 'images'])
            ->where('is_active', true);

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage)->appends($filters);
    }

    public function findBySlug(string $slug): Product
    {
        return Product::query()
            ->with(['category', 'images'])
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['q'])) {
            $query->where('name', 'like', '%'.$filters['q'].'%');
        }

        if (! empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (! empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        $sort = $filters['sort'] ?? '-created_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        if (in_array($column, ['created_at', 'price', 'name'], true)) {
            $query->orderBy($column, $direction);
        }
    }
}
