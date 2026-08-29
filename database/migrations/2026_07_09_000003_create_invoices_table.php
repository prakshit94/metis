<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->dateTime('invoice_date');
            $table->dateTime('due_date')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('tax_amount', 15, 2);
            $table->decimal('net_amount', 15, 2);
            $table->enum('status', ['unpaid', 'partially_paid', 'paid', 'cancelled'])->default('unpaid')->index();
            $table->timestamps();
            $table->softDeletes()->index();

            $table->index('created_at');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
