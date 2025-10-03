<?php
//php artisan queue:work database --queue=product-updates --tries=3 --backoff=60 --timeout=120 -vvv

namespace App\Jobs;

use App\Enums\ProcessedJobStatus;
use App\Enums\ProductUpdateType;
use App\Models\ProcessedJob;
use App\Services\ProductUpdateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class ProcessProductUpdateJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * O payload bruto recebido da mensagem na fila.
     *
     * @var array<string, mixed>
     */
    public array $payload;

    /**
     * Permite tentativas de nova execucao antes de falhar o job.
     */
    public int $tries = 3;

    /**
     * Estrategia de backoff em segundos entre as tentativas.
     *
     * @var array<int>
     */
    public array $backoff = [60, 300, 900];

    /**
     * Janela de tempo (em segundos) na qual o job deve permanecer unico.
     */
    public int $uniqueFor = 86_400;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(ProductUpdateService $productUpdates): void
    {
        Log::info('Job iniciado', [
            'queue'   => $this->queue,
            'payload' => $this->payload,
        ]);
        $validated = $this->validatePayload($this->payload);
        $messageId = $validated['message_id'];
        $type = ProductUpdateType::from($validated['type']);
        $data = $validated['data'];

        $processedJob = DB::transaction(function () use ($messageId, $type, $validated) {
            $record = ProcessedJob::where('message_id', $messageId)->lockForUpdate()->first();

            if (! $record) {
                $record = ProcessedJob::create([
                    'message_id' => $messageId,
                    'job_type' => $type->value,
                    'status' => ProcessedJobStatus::Pending,
                    'payload' => $validated,
                ]);
            }

            if ($record->status === ProcessedJobStatus::Completed) {
                return $record;
            }

            $record->job_type = $type->value;
            $record->payload = $validated;
            $record->status = ProcessedJobStatus::Pending;
            $record->save();

            return $record;
        });

        if ($processedJob->status === ProcessedJobStatus::Completed) {
            Log::info('Skipping already processed job', ['message_id' => $messageId]);

            return;
        }

        try {
            $processedJob->markProcessing();
            $productUpdates->handle($type, $data);
            $processedJob->markCompleted();
        } catch (Throwable $exception) {
            $processedJob->markFailed($exception->getMessage());
            Log::error('Failed to process product update job', [
                'message_id' => $messageId,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    public function uniqueId(): string
    {
        return $this->payload['message_id'] ?? sha1(json_encode($this->payload));
    }

    protected function validatePayload(array $payload): array
    {
        $validator = Validator::make($payload, [
            'message_id' => ['required', 'string', 'max:191'],
            'type' => ['required', 'string', Rule::enum(ProductUpdateType::class)],
            'data' => ['required', 'array'],
        ]);

        $validated = $validator->validate();
        $type = ProductUpdateType::from($validated['type']);

        $dataRules = match ($type) {
            ProductUpdateType::Stock => [
                'sku' => ['required', 'string', 'max:191'],
                'quantity' => ['required', 'integer', 'min:0'],
            ],
            ProductUpdateType::Price => [
                'sku' => ['required', 'string', 'max:191'],
                'amount' => ['required', 'numeric', 'min:0'],
                'currency' => ['required', 'string', 'size:3'],
            ],
            ProductUpdateType::Description => [
                'sku' => ['required', 'string', 'max:191'],
                'description' => ['required', 'string'],
            ],
            ProductUpdateType::Images => [
                'sku' => ['required', 'string', 'max:191'],
                'images' => ['required', 'array', 'min:1'],
                'images.*' => ['required', 'url'],
            ],
            ProductUpdateType::Tags => [
                'sku' => ['required', 'string', 'max:191'],
                'tags' => ['required', 'array', 'min:1'],
                'tags.*' => ['required', 'string', 'max:50'],
            ],
        };

        $validated['data'] = Validator::make($validated['data'], $dataRules)->validate();

        return $validated;
    }
}

