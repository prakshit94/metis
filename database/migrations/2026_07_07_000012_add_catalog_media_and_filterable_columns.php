<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->string('image')->nullable()->after('slug');
        });

        Schema::table('brands', function (Blueprint $table): void {
            $table->string('image')->nullable()->after('slug');
        });

        Schema::table('product_attributes', function (Blueprint $table): void {
            $table->boolean('is_filterable')->default(false)->after('type')->index();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn('image');
        });

        Schema::table('brands', function (Blueprint $table): void {
            $table->dropColumn('image');
        });

        Schema::table('product_attributes', function (Blueprint $table): void {
            $table->dropIndex(['is_filterable']);
            $table->dropColumn('is_filterable');
        });
    }
};
