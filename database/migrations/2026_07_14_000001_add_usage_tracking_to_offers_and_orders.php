<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->unsignedInteger('used_count')->default(0)->after('is_active');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('applied_offer_id')->nullable()->after('coupon_code');
            $table->foreign('applied_offer_id')->references('id')->on('offers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['applied_offer_id']);
            $table->dropColumn('applied_offer_id');
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('used_count');
        });
    }
};
