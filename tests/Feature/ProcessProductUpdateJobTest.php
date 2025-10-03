<?php

namespace Tests\Feature;

use App\Enums\ProcessedJobStatus;
use App\Jobs\ProcessProductUpdateJob;
use App\Models\ProcessedJob;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProcessProductUpdateJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_product_update_job_processes_payload(): void
    {
        $product = Product::factory()->create([
            'sku' => 'SKU-STARTER-001',
            'stock' => 10,
        ]);

        $payload = [
            'message_id' => (string) Str::uuid(),
            'type' => 'stock',
            'data' => [
                'sku' => 'SKU-STARTER-001',
                'quantity' => 99,
            ],
        ];

        ProcessProductUpdateJob::dispatchSync($payload);

        $product->refresh();

        $this->assertSame(99, $product->stock);

        $this->assertDatabaseHas('processed_jobs', [
            'message_id' => $payload['message_id'],
            'status' => ProcessedJobStatus::Completed->value,
        ]);
    }

    public function test_job_is_idempotent_and_skips_reprocessing(): void
    {
        Product::factory()->create([
            'sku' => 'SKU-999',
            'stock' => 10,
        ]);

        $payload = [
            'message_id' => (string) Str::uuid(),
            'type' => 'stock',
            'data' => [
                'sku' => 'SKU-999',
                'quantity' => 25,
            ],
        ];

        ProcessProductUpdateJob::dispatchSync($payload);

        $product = Product::where('sku', 'SKU-999')->firstOrFail();
        $this->assertSame(25, $product->stock);

        $updatedAt = $product->updated_at;

        ProcessProductUpdateJob::dispatchSync($payload);

        $product->refresh();
        $this->assertTrue($product->updated_at->equalTo($updatedAt));
    }

    public function test_job_failure_records_error_and_throws_exception(): void
    {
        $payload = [
            'message_id' => (string) Str::uuid(),
            'type' => 'stock',
            'data' => [
                'sku' => 'SKU-NOT-EXISTS',
                'quantity' => 10,
            ],
        ];

        $this->expectExceptionMessage('Product with SKU SKU-NOT-EXISTS was not found.');

        try {
            ProcessProductUpdateJob::dispatchSync($payload);
        } catch (\Throwable $exception) {
            $this->assertDatabaseHas('processed_jobs', [
                'message_id' => $payload['message_id'],
                'status' => ProcessedJobStatus::Failed->value,
            ]);

            throw $exception;
        }
    }

    public function test_job_validation_errors_prevent_processing(): void
    {
        Product::factory()->create([
            'sku' => 'SKU-VALID',
        ]);

        $payload = [
            'message_id' => (string) Str::uuid(),
            'type' => 'stock',
            'data' => [
                'sku' => 'SKU-VALID',
                // missing quantity
            ],
        ];

        $this->expectException(ValidationException::class);

        ProcessProductUpdateJob::dispatchSync($payload);

        $this->assertDatabaseMissing('processed_jobs', [
            'message_id' => $payload['message_id'],
        ]);
    }
}
