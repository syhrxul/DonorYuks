<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menyimpan permohonan darah dari pemohon/pasien beserta
     * data medis, jumlah kantong, dan lokasi rumah sakit.
     */
    public function up(): void
    {
        Schema::create('blood_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('patient_name');
            $table->enum('blood_type', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']);
            $table->unsignedInteger('bags_needed');
            $table->unsignedInteger('bags_fulfilled')->default(0);
            $table->string('hospital_name');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->enum('urgency_level', ['normal', 'urgent', 'critical'])->default('normal');
            $table->string('medical_reference_proof')->nullable()->comment('Path file bukti medis/surat rujukan');
            $table->enum('status', ['open', 'matched', 'completed', 'cancelled'])->default('open');
            $table->timestamps();

            $table->index(['latitude', 'longitude'], 'idx_blood_requests_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blood_requests');
    }
};
