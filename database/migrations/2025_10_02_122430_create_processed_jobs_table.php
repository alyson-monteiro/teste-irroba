<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('processed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('message_id', 191)->unique();
            $table->string('job_type', 100);
            $table->string('status', 40)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->json('payload');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'job_type']);
            $table->index('processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processed_jobs');
    }
};
