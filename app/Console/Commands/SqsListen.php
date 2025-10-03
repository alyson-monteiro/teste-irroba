<?php

//php artisan sqs:listen

namespace App\Console\Commands;

use Aws\Sqs\SqsClient;
use Illuminate\Console\Command;

class SqsListen extends Command
{
    protected $signature = 'sqs:listen';
    protected $description = 'Listen indefinitely to raw SQS messages and print them';

    public function handle(): int
    {
        $client = new SqsClient([
            'region' => config('queue.connections.sqs.region'),
            'version' => 'latest',
            'credentials' => [
                'key' => config('queue.connections.sqs.key'),
                'secret' => config('queue.connections.sqs.secret'),
            ],
        ]);

        $queueUrl = rtrim(config('queue.connections.sqs.prefix'), '/')
            . '/' . config('queue.connections.sqs.queue')
            . (config('queue.connections.sqs.suffix') ?? '');

        $this->info("📡 Listening on {$queueUrl}...");

        while (true) {
            $result = $client->receiveMessage([
                'QueueUrl' => $queueUrl,
                'MaxNumberOfMessages' => 5,
                'WaitTimeSeconds' => 20, // long polling
            ]);

            $messages = $result->get('Messages') ?? [];

            foreach ($messages as $msg) {
                $this->line("----- RAW MESSAGE -----");
                $this->line($msg['Body']);
                $this->line("-----------------------");

                // ============================
                // 🔽 MÍNIMO PARA ENFILEIRAR NO WORKER INTERNO (database)
                // ============================
                try {
                    $body = $msg['Body'] ?? '';

                    // Tentativa simples de interpretar JSON; se falhar, empacota como 'raw'
                    $decoded = json_decode($body, true);
                    if (! is_array($decoded)) {
                        $decoded = [
                            'message_id' => $msg['MessageId'] ?? uniqid('sqs-', true),
                            'type'       => 'raw',
                            'data'       => ['body' => $body],
                        ];
                    } elseif (! isset($decoded['message_id'])) {
                        // garante um message_id (útil para idempotência futura)
                        $decoded['message_id'] = $msg['MessageId'] ?? uniqid('sqs-', true);
                    }

                    // Enfileira para o worker INTERNO (conexão 'database')
                    dispatch(new \App\Jobs\ProcessProductUpdateJob($decoded))
                        ->onConnection('database')
                        ->onQueue('product-updates');

                    $this->info("✅ Mensagem recebida e enfileirada (conn=database, queue=product-updates) | SQS MessageId: ".($msg['MessageId'] ?? 'n/a'));

                    // Deleta do SQS ORIGINAL apenas após despachar com sucesso
                    $client->deleteMessage([
                        'QueueUrl'      => $queueUrl,
                        'ReceiptHandle' => $msg['ReceiptHandle'],
                    ]);
                } catch (\Throwable $e) {
                    // Se falhar o dispatch, NÃO delete — deixa para reentrega/visibilidade
                    $this->error("❌ Falha ao enfileirar internamente: ".$e->getMessage());
                }
            }
        }

        return 0;
    }
}
