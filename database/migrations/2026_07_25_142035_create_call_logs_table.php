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
            $table->foreignId('customer_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tag_l1_id')->nullable()->constrained('call_tags')->onDelete('set null');
            $table->foreignId('tag_l2_id')->nullable()->constrained('call_tags')->onDelete('set null');
            $table->foreignId('tag_l3_id')->nullable()->constrained('call_tags')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
