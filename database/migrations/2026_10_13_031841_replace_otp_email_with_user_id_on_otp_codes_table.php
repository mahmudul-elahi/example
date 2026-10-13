<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Changing the OTP key invalidates every outstanding code, and codes are
        // short-lived by design, so drop them instead of migrating them.
        DB::table('otp_codes')->delete();

        Schema::table('otp_codes', function (Blueprint $table): void {
            $table->dropIndex(['email', 'purpose']);
            $table->dropColumn('email');
        });

        Schema::table('otp_codes', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->after('id');
            $table->index(['user_id', 'purpose']);
        });

        // Added last so MySQL reuses the composite index above for the foreign
        // key instead of creating a second, single-column index.
        Schema::table('otp_codes', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id', 'purpose']);
            $table->dropColumn('user_id');
        });

        Schema::table('otp_codes', function (Blueprint $table): void {
            $table->string('email')->after('id');
            $table->index(['email', 'purpose']);
        });
    }
};
