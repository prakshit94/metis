<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('party_code')->nullable()->unique();
            $table->string('referral_code')->nullable()->unique();
            $table->unsignedBigInteger('referred_by')->nullable();
            $table->string('type')->index(); // customer, supplier, vendor, etc.

            // Name breakdown
            $table->string('firstname')->nullable()->index();
            $table->string('middlename')->nullable();
            $table->string('lastname')->nullable()->index();

            // Contact Information
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('alternatemobile')->nullable();
            $table->string('relative_name')->nullable();
            $table->string('relative_phone', 20)->nullable();

            // Source / Classification
            $table->json('source')->nullable();
            $table->string('category')->nullable(); // individual/business

            // Business Information
            $table->string('company_name')->nullable();
            $table->string('gst_no')->nullable()->index();
            $table->string('pan_no')->nullable()->index();
            $table->string('tax_no')->nullable();

            // Agriculture Profile
            $table->decimal('land_area', 10, 2)->nullable();
            $table->string('land_unit')->default('acre');
            $table->json('crops')->nullable();
            $table->json('irrigation_type')->nullable();

            // Financial Information
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->integer('credit_days')->default(0);
            $table->decimal('outstanding_balance', 15, 2)->default(0);
            $table->decimal('wallet_balance', 15, 2)->default(0);
            $table->date('credit_valid_till')->nullable();

            // KYC & Compliance
            $table->string('aadhaar_last4')->nullable();
            $table->boolean('kyc_completed')->default(false);
            $table->timestamp('kyc_verified_at')->nullable();

            // Engagement Tracking
            $table->date('first_purchase_at')->nullable();
            $table->date('last_purchase_at')->nullable();
            $table->unsignedInteger('orders_count')->default(0);

            // Status & Control
            $table->string('status')->default('active')->index();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_blacklisted')->default(false);
            $table->text('internal_notes')->nullable();
            $table->json('tags')->nullable();

            // Accounting Relation (Nullable, not constrained)
            $table->unsignedBigInteger('account_type_id')->nullable();

            // Audit Fields
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes()->index();

            $table->foreign('referred_by')->references('id')->on('parties')->nullOnDelete();

            // Performance indexes
            $table->index('created_at');
            $table->index(['status', 'created_at']);
            $table->index(['type', 'is_active']);
            $table->index(['firstname', 'lastname']);
            $table->index(['party_code']);
            $table->index(['company_name']);
            $table->index(['phone', 'is_active']);
            $table->index(['email', 'is_active']);
            
            // Fulltext index if using MySQL 5.7+ / MariaDB / PostGreSQL. We fallback safely or use simple fulltext.
            $table->fullText(['firstname', 'lastname', 'company_name', 'email', 'phone'], 'ft_party_search');
        });
    }

    public function down(): void {
        Schema::dropIfExists('parties');
    }
};
