<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menyimpan riwayat percakapan pengguna dengan AI Chatbot "Bloody".
     */
    public function up(): void
    {
        Schema::create('chat_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->longText('answer');
            $table->string('provider')->default('mock');
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'idx_chat_histories_user_created');
        });

        // Index tambahan untuk mempercepat query geo-matching & status.
        Schema::table('blood_requests', function (Blueprint $table) {
            $table->index(['status', 'blood_type'], 'idx_blood_requests_status_blood_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blood_requests', function (Blueprint $table) {
            $table->dropIndex('idx_blood_requests_status_blood_type');
        });

        Schema::dropIfExists('chat_histories');
    }
};
