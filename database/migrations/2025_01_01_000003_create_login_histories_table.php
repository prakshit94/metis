<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('email_attempted');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->enum('device_type', ['web', 'mobile'])->default('web');
            $table->enum('status', ['success', 'failed', 'impersonated'])->default('failed');
            $table->string('failure_reason')->nullable()
                ->comment('invalid_credentials | account_suspended | account_inactive | throttled | impersonated_login');
            $table->timestamp('attempted_at')->useCurrent();

            $table->index(['email_attempted', 'ip_address']);
            $table->index('attempted_at');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};
