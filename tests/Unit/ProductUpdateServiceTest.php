<?php

namespace Tests\Unit;

use App\Enums\ProductUpdateType;
use App\Exceptions\ProductUpdateException;
use App\Models\Product;
use App\Services\ProductUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductUpdateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_requires_base_product(): void
    {
        $service = app(ProductUpdateService::class);

        $this->expectException(ProductUpdateException::class);

        $service->handle(ProductUpdateType::Stock, [
            'sku' => 'UNKNOWN',
            'quantity' => 10,
        ]);
    }

    public function test_service_updates_stock(): void
    {
        $product = Product::factory()->create([
            'sku' => 'SKU-123',
            'stock' => 5,
        ]);

        $service = app(ProductUpdateService::class);

        $service->handle(ProductUpdateType::Stock, [
            'sku' => 'SKU-123',
            'quantity' => 30,
        ]);

        $product->refresh();

        $this->assertSame(30, $product->stock);
    }

    public function test_service_updates_price_and_currency(): void
    {
        $product = Product::factory()->create([
            'sku' => 'SKU-PRICE',
            'price' => 9.99,
            'currency' => 'USD',
        ]);

        $service = app(ProductUpdateService::class);

        $service->handle(ProductUpdateType::Price, [
            'sku' => 'SKU-PRICE',
            'amount' => 19.95,
            'currency' => 'brl',
        ]);

        $product->refresh();

        $this->assertSame('BRL', $product->currency);
        $this->assertEquals(19.95, $product->price);
    }
}
