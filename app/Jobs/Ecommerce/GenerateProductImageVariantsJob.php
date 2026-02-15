<?php

namespace App\Jobs\Ecommerce;

use App\Models\Ecommerce\ProductImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateProductImageVariantsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $productImageId)
    {
    }

    public function handle(): void
    {
        $image = ProductImage::query()->find($this->productImageId);

        if (! $image) {
            return;
        }

        // Replace with image manipulation pipeline in production.
        Log::info('Generate image variants job executed.', ['product_image_id' => $image->id]);
    }
}
