<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Se reemplaza el modelo de "canales" dentro de un solo dispositivo
     * por dispositivos independientes (uno por relevador), controlados
     * mediante los nuevos campos agregados a la tabla devices.
     */
    public function up(): void
    {
        Schema::dropIfExists('device_relay_channels');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('device_relay_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('channel');
            $table->string('label')->nullable();
            $table->string('desired_state')->default('off');
            $table->string('reported_state')->nullable();
            $table->timestamp('commanded_at')->nullable();
            $table->foreignId('commanded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reported_at')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'channel']);
        });
    }
};
