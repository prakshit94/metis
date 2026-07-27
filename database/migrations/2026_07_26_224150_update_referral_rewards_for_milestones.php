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
        Schema::table('referral_rewards', function (Blueprint $table) {
            $table->unsignedBigInteger('referral_program_id')->nullable()->after('order_id');
            $table->unsignedBigInteger('milestone_id')->nullable()->after('referral_program_id');
            $table->string('reward_type')->default('wallet')->after('milestone_id');
            
            $table->foreign('referral_program_id')->references('id')->on('referral_programs')->nullOnDelete();
            $table->foreign('milestone_id')->references('id')->on('referral_program_milestones')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referral_rewards', function (Blueprint $table) {
            $table->dropForeign(['referral_program_id']);
            $table->dropForeign(['milestone_id']);
            $table->dropColumn(['referral_program_id', 'milestone_id', 'reward_type']);
        });
    }
};
