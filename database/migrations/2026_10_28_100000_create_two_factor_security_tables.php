<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_two_factor_settings', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->text('secret')->nullable();
            $table->text('pending_secret')->nullable();
            $table->timestamp('pending_expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('recovery_hashes')->nullable();
            $table->bigInteger('last_used_step')->default(-1);
            $table->string('version', 64);
        });
        Schema::create('two_factor_login_challenges', function (Blueprint $table) {
            $table->string('id', 64)->primary(); // SHA-256 of opaque challenge; never store the bearer value.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('credential_fingerprint', 64);
            $table->string('version', 64);
            $table->string('channel', 10);
            $table->boolean('remember')->default(false);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at')->index();
        });
        Schema::create('two_factor_security_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('event', 64);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factor_security_events');
        Schema::dropIfExists('two_factor_login_challenges');
        Schema::dropIfExists('user_two_factor_settings');
    }
};
