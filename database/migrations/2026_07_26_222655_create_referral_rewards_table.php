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
        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_id')->index();
            $table->unsignedBigInteger('referred_id')->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->decimal('reward_amount', 10, 2)->default(0);
            $table->string('status')->default('completed'); // e.g. completed, pending, revoked
            $table->timestamps();

            $table->foreign('referrer_id')->references('id')->on('parties')->cascadeOnDelete();
            $table->foreign('referred_id')->references('id')->on('parties')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_rewards');
    }
};
