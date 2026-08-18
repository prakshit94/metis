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
        Schema::create('referral_program_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_program_id')->constrained()->cascadeOnDelete();
            $table->integer('required_referrals');
            $table->string('reward_type'); // wallet, product, coupon
            $table->string('reward_value'); // amount, product_id, or coupon code
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_program_milestones');
    }
};
