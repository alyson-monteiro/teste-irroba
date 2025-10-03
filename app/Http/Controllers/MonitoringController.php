<?php

namespace App\Http\Controllers;

use App\Enums\ProcessedJobStatus;
use App\Models\ProcessedJob;
use Illuminate\Http\JsonResponse;

class MonitoringController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $failedJobs = ProcessedJob::query()
            ->where('status', ProcessedJobStatus::Failed->value)
            ->orderByDesc('failed_at')
            ->limit(10)
            ->get(['message_id', 'job_type', 'error_message', 'failed_at', 'attempts']);

        $pendingStatuses = [
            ProcessedJobStatus::Pending->value,
            ProcessedJobStatus::Processing->value,
        ];

        $stats = [
            'queued_connection' => config('queue.default'),
            'queue_name' => config('queue.product_update_queue', config('queue.connections.sqs.queue')),
            'total_processed' => ProcessedJob::where('status', ProcessedJobStatus::Completed->value)->count(),
            'total_failed' => ProcessedJob::where('status', ProcessedJobStatus::Failed->value)->count(),
            'total_pending' => ProcessedJob::whereIn('status', $pendingStatuses)->count(),
            'oldest_pending_started_at' => optional(
                ProcessedJob::whereIn('status', $pendingStatuses)
                    ->orderBy('created_at')
                    ->value('created_at')
            )?->toDateTimeString(),
        ];

        return response()->json([
            'stats' => $stats,
            'recent_failures' => $failedJobs,
        ]);
    }
}

