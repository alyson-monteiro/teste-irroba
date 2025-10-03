<?php

namespace Tests\Feature;

use App\Enums\ProcessedJobStatus;
use App\Models\ProcessedJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_endpoint_returns_metrics_with_valid_api_key(): void
    {
        config()->set('services.product_hub.monitoring_api_key', 'secret-key');

        ProcessedJob::factory()->create([
            'status' => ProcessedJobStatus::Completed,
        ]);

        ProcessedJob::factory()->failed()->create([
            'message_id' => 'failed-job',
        ]);

        ProcessedJob::factory()->create([
            'status' => ProcessedJobStatus::Pending,
            'processed_at' => null,
            'error_message' => null,
            'failed_at' => null,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => 'secret-key'])
            ->getJson('/api/monitoring/metrics');

        $response->assertOk()
            ->assertJsonStructure([
                'stats' => [
                    'queued_connection',
                    'queue_name',
                    'total_processed',
                    'total_failed',
                    'total_pending',
                    'oldest_pending_started_at',
                ],
                'recent_failures',
            ]);

        $this->assertSame(1, $response->json('stats.total_failed'));
        $this->assertSame(1, $response->json('stats.total_processed'));
        $this->assertSame(1, $response->json('stats.total_pending'));
    }

    public function test_monitoring_endpoint_requires_api_key(): void
    {
        config()->set('services.product_hub.monitoring_api_key', 'secret-key');

        $response = $this->getJson('/api/monitoring/metrics');

        $response->assertUnauthorized();
    }
}
