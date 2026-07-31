<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::table('orders', function (Blueprint $table) {
    if (!Schema::hasColumn('orders', 'pending_at')) {
        $table->dateTime('pending_at')->nullable();
        $table->foreignId('pending_by')->nullable()->constrained('users')->nullOnDelete();
        $table->dateTime('confirmed_at')->nullable();
        $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
        $table->dateTime('processing_at')->nullable();
        $table->foreignId('processing_by')->nullable()->constrained('users')->nullOnDelete();
        $table->dateTime('ready_to_ship_at')->nullable();
        $table->foreignId('ready_to_ship_by')->nullable()->constrained('users')->nullOnDelete();
        $table->dateTime('dispatched_at')->nullable();
        $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
        $table->dateTime('shipped_at')->nullable();
        $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete();
        $table->dateTime('delivered_at')->nullable();
        $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
        $table->dateTime('cancelled_at')->nullable();
        $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
        $table->dateTime('returned_at')->nullable();
        $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
        $table->dateTime('return_requested_at')->nullable();
        $table->foreignId('return_requested_by')->nullable()->constrained('users')->nullOnDelete();
    }
});
echo "Schema updated.\n";
