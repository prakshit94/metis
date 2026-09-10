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
        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->morphs('targetable'); // Creates targetable_id and targetable_type
            $table->string('metric_type')->index();
            $table->string('period_type')->index();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('target_amount', 15, 2);
            $table->decimal('achieved_amount', 15, 2)->default(0);
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['targetable_type', 'targetable_id', 'metric_type', 'period_type', 'start_date'], 'targets_unique_composite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};
