<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_tag_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_tag_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('label');
            $table->string('type')->default('text')->index(); // text, select, textarea, date
            $table->json('options')->nullable(); // for selects
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_tag_form_fields');
    }
};
