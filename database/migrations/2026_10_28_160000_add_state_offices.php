<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('teams')->whereNotNull('code')->select('code')->groupBy('code')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Resolve duplicate team codes before adding offices. No existing code is changed automatically.');
        }
        Schema::table('teams', fn (Blueprint $table) => $table->unique('code', 'teams_code_unique'));
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('code', 30);
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('pincode', 20)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['team_id', 'code']);
            $table->unique(['team_id', 'name']);
        });
        Schema::table('users', fn (Blueprint $table) => $table->foreignId('office_id')->nullable()->constrained('offices')->restrictOnDelete());
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('office_id')->nullable()->constrained('offices')->restrictOnDelete();
            $table->foreignId('state_team_id')->nullable()->constrained('teams')->restrictOnDelete();
        });
        Schema::create('organization_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('entity_type', 20);
            $table->unsignedBigInteger('entity_id');
            $table->string('event', 30);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('created_at');
            $table->index(['entity_type', 'entity_id', 'created_at']);
        });
    }

    public function down(): void
    {
        // Never silently discard historical office assignments through rollback.
        if (DB::table('users')->whereNotNull('office_id')->exists() || DB::table('orders')->whereNotNull('state_team_id')->orWhereNotNull('office_id')->exists()) {
            throw new RuntimeException('Office assignments or order history exist. Export and explicitly migrate them before rollback.');
        }
        Schema::dropIfExists('organization_events');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('office_id');
            $table->dropConstrainedForeignId('state_team_id');
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('office_id'));
        Schema::dropIfExists('offices');
        Schema::table('teams', fn (Blueprint $table) => $table->dropUnique('teams_code_unique'));
    }
};
