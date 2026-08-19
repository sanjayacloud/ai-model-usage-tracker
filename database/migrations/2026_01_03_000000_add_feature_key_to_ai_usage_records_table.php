<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn($this->table(), 'feature_key')) {
            return;
        }

        Schema::table($this->table(), function (Blueprint $table) {
            $table->string('feature_key')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table($this->table(), function (Blueprint $table) {
            $table->dropColumn('feature_key');
        });
    }

    private function table(): string
    {
        return config('ai-model-usage-tracker.database.table', 'ai_usage_records');
    }
};
