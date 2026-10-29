<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pet_memorial_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pet_memorial_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();
        });

        Schema::table('pet_memorials', function (Blueprint $table): void {
            $table->dropColumn('cover_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pet_memorials', function (Blueprint $table): void {
            $table->string('cover_image')->nullable();
        });

        Schema::dropIfExists('pet_memorial_images');
    }
};
