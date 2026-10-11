<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $providers = config('booking-engine.tables.providers', 'booking_providers');

        Schema::create(config('booking-engine.tables.availability_rules', 'booking_availability_rules'), function (Blueprint $table) use ($providers): void {
            $table->id();
            $table->foreignId('provider_id')->constrained($providers)->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // ISO-8601: 1 = Monday ... 7 = Sunday
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('slot_interval_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['provider_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('booking-engine.tables.availability_rules', 'booking_availability_rules'));
    }
};
