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
        Schema::create('daily_consumption_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('total_kwh', 10, 3);
            $table->decimal('avg_watts', 10, 2);
            $table->decimal('min_watts', 10, 2);
            $table->decimal('max_watts', 10, 2);
            $table->unsignedInteger('readings_count');
            $table->timestamps();

            $table->unique(['device_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_consumption_summaries');
    }
};
