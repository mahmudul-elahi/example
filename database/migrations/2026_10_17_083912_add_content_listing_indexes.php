<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the composite indexes the public listing queries filter and sort on.
     */
    public function up(): void
    {
        Schema::table('pet_memorials', function (Blueprint $table): void {
            $table->index(['status', 'visibility', 'id']);
        });

        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->index(['status', 'published_at', 'id']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->index(['status', 'id']);
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->index(['commentable_type', 'commentable_id', 'parent_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pet_memorials', function (Blueprint $table): void {
            $table->dropIndex(['status', 'visibility', 'id']);
        });

        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->dropIndex(['status', 'published_at', 'id']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['status', 'id']);
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->dropIndex(['commentable_type', 'commentable_id', 'parent_id', 'id']);
        });
    }
};
