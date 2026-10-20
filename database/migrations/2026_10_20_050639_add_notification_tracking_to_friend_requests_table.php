<?php

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
        Schema::table('friend_requests', function (Blueprint $table): void {
            $table->timestamp('request_notified_at')->nullable()->after('responded_at');
            $table->timestamp('accepted_notified_at')->nullable()->after('request_notified_at');
            $table->softDeletes()->after('accepted_notified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('friend_requests', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropColumn(['request_notified_at', 'accepted_notified_at']);
        });
    }
};
