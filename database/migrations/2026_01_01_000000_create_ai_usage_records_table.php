<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable($this->table())) {
            return;
        }

        Schema::create($this->table(), function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invocation_id')->nullable()->index();

            $table->string('driver')->default('manual');
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->string('operation')->default('chat');

            $table->unsignedBigInteger('prompt_tokens')->default(0);
            $table->unsignedBigInteger('completion_tokens')->default(0);
            $table->unsignedBigInteger('cache_write_input_tokens')->default(0);
            $table->unsignedBigInteger('cache_read_input_tokens')->default(0);
            $table->unsignedBigInteger('reasoning_tokens')->default(0);
            $table->unsignedBigInteger('total_tokens')->default(0);

            $table->decimal('input_cost', 16, 8)->default(0);
            $table->decimal('output_cost', 16, 8)->default(0);
            $table->decimal('total_cost', 16, 8)->default(0);
            $table->string('currency', 3)->default('USD');

            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('status')->default('success');
            $table->text('error')->nullable();
            $table->boolean('streamed')->default(false);

            $table->nullableMorphs('trackable');
            $table->json('metadata')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index('model');
            $table->index('provider');
            $table->index('operation');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return config('ai-model-usage-tracker.database.table', 'ai_usage_records');
    }
};
