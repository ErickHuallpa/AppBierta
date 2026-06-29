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
            $table->string('payment_method')->nullable();
            $table->string('payment_receipt')->nullable();
            $table->string('payment_status')->default('pending');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('delivery_user_id')->nullable();
            
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            $table->foreign('delivery_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropForeign(['delivery_user_id']);
            $table->dropColumn(['payment_method', 'payment_receipt', 'payment_status', 'location_id', 'delivery_user_id']);
        });
    }
};
