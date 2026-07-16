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
        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('return_no')->unique();
            $table->string('status')->default('pending'); // pending, received, qc_in_progress, completed, rejected
            $table->string('financial_status')->default('pending'); // pending, partial_refund, fully_refunded, credited
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('refund_amount', 15, 2)->default(0);
            $table->decimal('credit_note_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('order_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('requested_qty', 15, 4)->default(0);
            $table->decimal('received_qty', 15, 4)->default(0);
            $table->decimal('restocked_qty', 15, 4)->default(0);
            $table->decimal('damaged_qty', 15, 4)->default(0);
            $table->string('qc_status')->default('pending'); // pending, passed, failed
            $table->text('qc_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_no')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_return_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_return_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_remaining', 15, 2);
            $table->string('status')->default('active'); // active, used, expired
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('order_return_items');
        Schema::dropIfExists('order_returns');
    }
};
