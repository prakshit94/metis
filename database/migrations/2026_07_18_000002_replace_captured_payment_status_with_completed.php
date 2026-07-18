<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE payments MODIFY status ENUM('pending', 'authorized', 'captured', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending'");
        DB::table('payments')->where('status', 'captured')->update(['status' => 'completed']);
        DB::statement("ALTER TABLE payments MODIFY status ENUM('pending', 'authorized', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY status ENUM('pending', 'authorized', 'captured', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending'");
        DB::table('payments')->where('status', 'completed')->update(['status' => 'captured']);
        DB::statement("ALTER TABLE payments MODIFY status ENUM('pending', 'authorized', 'captured', 'failed', 'refunded') NOT NULL DEFAULT 'pending'");
    }
};
