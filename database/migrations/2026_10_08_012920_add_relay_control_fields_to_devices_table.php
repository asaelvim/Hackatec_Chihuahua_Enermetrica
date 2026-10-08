<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Permite que un dispositivo (p.ej. "Aire acondicionado") sea
     * controlado por uno de los 5 relevadores físicos de otro
     * dispositivo (el ESP32), y guarda el estado deseado (status, ya
     * existente) vs. el que el ESP32 confirmó haber aplicado.
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->foreignId('controller_device_id')->nullable()->after('device_model_id')
                ->constrained('devices')->nullOnDelete();
            $table->unsignedTinyInteger('relay_channel')->nullable()->after('controller_device_id');
            $table->string('reported_status')->nullable()->after('status');
            $table->timestamp('commanded_at')->nullable()->after('last_reading_at');
            $table->foreignId('commanded_by')->nullable()->after('commanded_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reported_at')->nullable()->after('commanded_by');

            $table->unique(['controller_device_id', 'relay_channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropUnique(['controller_device_id', 'relay_channel']);
            $table->dropConstrainedForeignId('controller_device_id');
            $table->dropConstrainedForeignId('commanded_by');
            $table->dropColumn(['relay_channel', 'reported_status', 'commanded_at', 'reported_at']);
        });
    }
};
