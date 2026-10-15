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
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id', 'created_at']);
        });
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
        });
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'created_at']);
        });
    }
};
