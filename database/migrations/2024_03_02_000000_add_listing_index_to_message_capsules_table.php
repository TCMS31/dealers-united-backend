<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `GET /users/{user}/message-capsules` filters on user_id and orders by
 * scheduled_opening_time. The original table indexed neither, so the listing
 * was a full scan plus a filesort. This covering index turns it into a range
 * read that is already in the required order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_capsules', function (Blueprint $table) {
            $table->index(['user_id', 'scheduled_opening_time'], 'message_capsules_user_schedule_index');
        });
    }

    public function down(): void
    {
        Schema::table('message_capsules', function (Blueprint $table) {
            $table->dropIndex('message_capsules_user_schedule_index');
        });
    }
};
