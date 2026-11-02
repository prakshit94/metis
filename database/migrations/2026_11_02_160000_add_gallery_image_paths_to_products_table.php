<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('gallery_image_paths')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        if (DB::table('products')->whereJsonLength('gallery_image_paths', '>', 0)->exists()) {
            throw new RuntimeException('Preserve product gallery associations before rolling back this migration. Roll back application code while retaining this additive column instead.');
        }
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('gallery_image_paths');
        });
    }
};
