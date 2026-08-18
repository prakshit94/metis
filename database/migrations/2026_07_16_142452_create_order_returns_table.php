<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('return_no')->unique();
            $table->string('status')->default('pending')->index();
            $table->string('financial_status')->default('pending');
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('refund_amount', 15, 2)->default(0);
            $table->decimal('credit_note_amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_returns');
    }
};
