<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menyimpan event donor darah (DonorYuks Event) yang
     * diselenggarakan oleh PMI / rumah sakit beserta lokasi & kuota.
     */
    public function up(): void
    {
        Schema::create('donor_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('organizer');
            $table->text('description')->nullable();
            $table->string('location_name');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->dateTime('event_date');
            $table->unsignedInteger('quota');
            $table->timestamps();

            $table->index('event_date', 'idx_donor_events_date');
            $table->index(['latitude', 'longitude'], 'idx_donor_events_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donor_events');
    }
};
