<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('party_code')->nullable()->unique(); // We'll keep this as it is used in the UI

            // Name breakdown
            $table->string('firstname')->nullable()->index();
            $table->string('lastname')->nullable()->index();

            // Contact Information
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();

            // Business Information
            $table->string('company_name')->nullable();
            $table->string('gst_no')->nullable()->index();
            $table->string('pan_no')->nullable()->index();

            // Financial Information
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->integer('credit_days')->default(0);

            // Status & Control
            $table->string('status')->default('active')->index();
            $table->boolean('is_active')->default(true);
            $table->text('internal_notes')->nullable();

            // Address Information
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->foreignId('village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->string('village_name')->nullable();
            $table->string('post_office')->nullable();
            $table->string('taluka')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();

            $table->timestamps();
            $table->softDeletes()->index();
        });
    }

    public function down(): void {
        Schema::dropIfExists('suppliers');
    }
};
