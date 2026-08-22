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
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->unsignedBigInteger('referral_program_id')->nullable();
            $table->unsignedBigInteger('milestone_id')->nullable();
            $table->string('reward_type')->default('wallet');
            $table->string('reward_value')->nullable();
            $table->decimal('reward_amount', 10, 2)->default(0);
            $table->string('status')->default('completed')->index(); // e.g. completed, pending, revoked
            $table->timestamps();

            $table->foreign('referrer_id')->references('id')->on('parties')->cascadeOnDelete();
            $table->foreign('referred_id')->references('id')->on('parties')->cascadeOnDelete();
            $table->foreign('referral_program_id')->references('id')->on('referral_programs')->nullOnDelete();
            $table->foreign('milestone_id')->references('id')->on('referral_program_milestones')->nullOnDelete();
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
