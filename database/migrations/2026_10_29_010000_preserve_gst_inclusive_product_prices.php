<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('selling_price_including_tax', 15, 2)->nullable()->after('selling_price');
        });
        // Derived taxable unit prices need precision; monetary totals retain their existing scale.
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_price', 21, 8)->change();
        });
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::table('products')->whereNotNull('selling_price_including_tax')->exists()) {
            throw new RuntimeException('Preserve inclusive product prices before rolling back this migration.');
        }
        // Keep the wider historical unit-price precision; shrinking it would lose saved data.
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('selling_price_including_tax');
        });
    }
};
