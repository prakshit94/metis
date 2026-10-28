<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('two_factor_required')->default(false));
        Schema::table('two_factor_login_challenges', fn (Blueprint $table) => $table->string('purpose', 20)->default('login'));
    }

    public function down(): void
    {
        Schema::table('two_factor_login_challenges', fn (Blueprint $table) => $table->dropColumn('purpose'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('two_factor_required'));
    }
};
