<?php

namespace App\Services;

use App\Enums\ProductUpdateType;
use App\Exceptions\ProductUpdateException;
use App\Models\Product;
use Illuminate\Database\DatabaseManager;

class ProductUpdateService
{
    public function __construct(private readonly DatabaseManager $database)
    {
    }

    public function handle(ProductUpdateType $type, array $data): Product
    {
        $sku = $data['sku'] ?? null;

        if (! is_string($sku) || trim($sku) === '') {
            throw new ProductUpdateException('Missing or invalid SKU provided.');
        }

        return $this->database->transaction(function () use ($type, $data, $sku) {
            $product = Product::query()->lockForUpdate()->where('sku', $sku)->first();

            if (! $product) {
                throw new ProductUpdateException("Product with SKU {$sku} was not found.");
            }

            match ($type) {
                ProductUpdateType::Stock => $this->updateStock($product, $data),
                ProductUpdateType::Price => $this->updatePrice($product, $data),
                ProductUpdateType::Description => $this->updateDescription($product, $data),
                ProductUpdateType::Images => $this->updateImages($product, $data),
                ProductUpdateType::Tags => $this->updateTags($product, $data),
            };

            $product->save();

            return $product->fresh();
        });
    }

    protected function updateStock(Product $product, array $data): void
    {
        $quantity = $data['quantity'] ?? null;

        if (! is_int($quantity) && ! (is_numeric($quantity) && (int) $quantity >= 0)) {
            throw new ProductUpdateException('Invalid quantity provided for stock update.');
        }

        $product->stock = (int) $quantity;
    }

    protected function updatePrice(Product $product, array $data): void
    {
        $amount = $data['amount'] ?? null;

        if (! is_numeric($amount)) {
            throw new ProductUpdateException('Invalid amount provided for price update.');
        }

        $currency = $data['currency'] ?? $product->currency;

        if (! is_string($currency) || strlen($currency) !== 3) {
            throw new ProductUpdateException('Invalid currency provided for price update.');
        }

        $product->price = round((float) $amount, 2);
        $product->currency = strtoupper($currency);
    }

    protected function updateDescription(Product $product, array $data): void
    {
        $description = $data['description'] ?? null;

        if (! is_string($description) || trim($description) === '') {
            throw new ProductUpdateException('Invalid description provided for update.');
        }

        $product->description = $description;
    }

    protected function updateImages(Product $product, array $data): void
    {
        $images = $data['images'] ?? null;

        if (! is_array($images) || $images === []) {
            throw new ProductUpdateException('Invalid images array provided for update.');
        }

        $sanitized = collect($images)
            ->filter(fn ($url) => is_string($url) && trim($url) !== '')
            ->values()
            ->all();

        if ($sanitized === []) {
            throw new ProductUpdateException('Images array must contain at least one valid URL.');
        }

        $product->images = $sanitized;
    }

    protected function updateTags(Product $product, array $data): void
    {
        $tags = $data['tags'] ?? null;

        if (! is_array($tags) || $tags === []) {
            throw new ProductUpdateException('Invalid tags array provided for update.');
        }

        $sanitized = collect($tags)
            ->filter(fn ($tag) => is_string($tag) && trim($tag) !== '')
            ->map(fn ($tag) => trim($tag))
            ->values()
            ->all();

        if ($sanitized === []) {
            throw new ProductUpdateException('Tags array must contain at least one valid tag.');
        }

        $product->tags = $sanitized;
    }
}
