<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $providers = config('booking-engine.tables.providers', 'booking_providers');

        Schema::create(config('booking-engine.tables.availability_exceptions', 'booking_availability_exceptions'), function (Blueprint $table) use ($providers): void {
            $table->id();
            $table->foreignId('provider_id')->constrained($providers)->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_closed')->default(true);
            $table->json('windows')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('booking-engine.tables.availability_exceptions', 'booking_availability_exceptions'));
    }
};
