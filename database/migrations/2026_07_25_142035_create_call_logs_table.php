<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->foreignId('tag_l1_id')->nullable()->constrained('call_tags')->onDelete('set null');
            $table->foreignId('tag_l2_id')->nullable()->constrained('call_tags')->onDelete('set null');
            $table->foreignId('tag_l3_id')->nullable()->constrained('call_tags')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('parties')->onDelete('set null');
            $table->foreign('agent_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
