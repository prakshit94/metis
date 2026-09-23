<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable(); // Context for agents
            $table->string('color', 20)->nullable(); // UI badge color
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('level')->default(1)->index(); // Indexed for quick level lookups
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index(); // Indexed for active filtering
            $table->timestamps();
            $table->softDeletes(); // CRITICAL: Prevent historical call_logs from losing tags

            $table->foreign('parent_id')->references('id')->on('call_tags')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_tags');
    }
};
