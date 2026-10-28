<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_two_factor_settings', function (Blueprint $table) {
            $table->string('method', 20)->default('authenticator');
            $table->string('email_address')->nullable();
            $table->string('pending_method', 20)->nullable();
            $table->string('pending_email')->nullable();
        });
        Schema::create('two_factor_email_codes', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('purpose', 20);
            $table->string('email_address');
            $table->string('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamp('sent_at');
            $table->index(['user_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factor_email_codes');
        Schema::table('user_two_factor_settings', function (Blueprint $table) {
            $table->dropColumn(['method', 'email_address', 'pending_method', 'pending_email']);
        });
    }
};
