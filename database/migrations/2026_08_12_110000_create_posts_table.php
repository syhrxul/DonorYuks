<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menyimpan artikel edukasi & berita seputar donor darah.
     * Hanya artikel berstatus published yang ditampilkan ke publik.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('category', ['edukasi', 'berita', 'tips'])->default('edukasi');
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('image')->nullable()->comment('Path file gambar sampul');
            $table->string('author')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('category', 'idx_posts_category');
            $table->index('published_at', 'idx_posts_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
