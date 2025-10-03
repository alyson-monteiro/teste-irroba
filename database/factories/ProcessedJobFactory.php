<?php

namespace Database\Factories;

use App\Enums\ProcessedJobStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\ProcessedJob>
 */
class ProcessedJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['stock', 'price', 'description', 'images', 'tags']);

        return [
            'message_id' => (string) Str::uuid(),
            'job_type' => $type,
            'status' => ProcessedJobStatus::Completed,
            'attempts' => 1,
            'payload' => ['type' => $type, 'foo' => 'bar'],
            'error_message' => null,
            'processed_at' => now(),
            'failed_at' => null,
        ];
    }

    public function failed(): self
    {
        return $this->state(function () {
            return [
                'status' => ProcessedJobStatus::Failed,
                'error_message' => 'Example failure',
                'failed_at' => now(),
            ];
        });
    }
}
