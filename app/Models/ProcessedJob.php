<?php

namespace App\Models;

use App\Enums\ProcessedJobStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ProcessedJob extends Model
{
    use HasFactory;

    // Se o nome da tabela NÃO for "processed_jobs", defina:
    // protected $table = 'processed_jobs';

    protected $fillable = [
        'message_id',
        'job_type',
        'status',
        'attempts',
        'payload',
        'error_message',
        'processed_at',
        'failed_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'processed_at' => 'datetime',
        'failed_at'    => 'datetime',
        'status'       => ProcessedJobStatus::class, // string-backed enum recomendado
    ];

    // Defaults defensivos (caso migration não tenha DEFAULT)
    protected $attributes = [
        'attempts' => 0,
        // 'status' => ProcessedJobStatus::Pending->value, // opcional se quiser default aqui
    ];

    public function markProcessing(): void
    {
        // Evita ++ em valor possivelmente null e é atômico no BD
        if ($this->exists) {
            $this->increment('attempts');
            $this->status = ProcessedJobStatus::Processing;
            $this->save();
        } else {
            // fallback se o registro ainda não estiver persisted
            $this->attempts = (int) $this->attempts + 1;
            $this->status = ProcessedJobStatus::Processing;
            $this->save();
        }
    }

    public function markCompleted(): void
    {
        $this->status       = ProcessedJobStatus::Completed;
        $this->processed_at = Carbon::now();
        $this->error_message = null;
        $this->save();
    }

    public function markFailed(string $message): void
    {
        $this->status       = ProcessedJobStatus::Failed;
        $this->failed_at    = Carbon::now();
        $this->error_message = $message;
        $this->save();
    }
}
