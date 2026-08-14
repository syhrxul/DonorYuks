<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mencatat aktivitas donor (personal / event) mulai dari
     * matching, konfirmasi, hingga donasi terselesaikan.
     */
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blood_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('donor_event_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['personal', 'event']);
            $table->enum('status', ['matched', 'confirmed', 'completed', 'cancelled'])->default('matched');
            $table->string('ticket_code')->nullable()->unique();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_donations_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
