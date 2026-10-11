<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $services = config('booking-engine.tables.services', 'booking_services');
        $providers = config('booking-engine.tables.providers', 'booking_providers');

        Schema::create(config('booking-engine.tables.appointments', 'booking_appointments'), function (Blueprint $table) use ($services, $providers): void {
            $table->id();
            $table->foreignId('service_id')->constrained($services)->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained($providers)->cascadeOnDelete();
            $table->string('status')->default('confirmed');
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->string('timezone', 64)->nullable();
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->string('client_phone')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();

            // Supports the overlap query used by the availability engine and the
            // atomic guard added with BookingManager.
            $table->index(['provider_id', 'start_at', 'end_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('booking-engine.tables.appointments', 'booking_appointments'));
    }
};
