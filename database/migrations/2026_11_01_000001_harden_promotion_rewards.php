<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing personal ownership must not be reassigned by a retry/backfill.
        if (DB::table('referral_rewards')->select('coupon_code')->whereNotNull('coupon_code')->where('coupon_code', '!=', '')
            ->groupBy('coupon_code')->havingRaw('COUNT(DISTINCT referrer_id) > 1')->exists()) {
            throw new RuntimeException('A reward coupon is linked to different customers; review ownership before migrating.');
        }
        if (Schema::hasColumn('coupons', 'recipient_party_id')
            && DB::table('referral_rewards as r')->join('coupons as c', 'c.code', '=', 'r.coupon_code')
                ->whereNotNull('c.recipient_party_id')->whereColumn('c.recipient_party_id', '!=', 'r.referrer_id')->exists()) {
            throw new RuntimeException('An issued reward coupon belongs to a different customer; review ownership before migrating.');
        }
        // Refuse malformed targets instead of silently turning them into an unrestricted campaign.
        foreach (['coupons', 'offers'] as $table) {
            foreach (DB::table($table)->cursor() as $row) {
                foreach (['applicable_products', 'applicable_categories', 'excluded_products', 'excluded_categories'] as $field) {
                    $this->normalizeTargets($row->$field, $table.'.'.$row->id.'.'.$field);
                }
            }
        }
        // MySQL commits DDL independently. Resume cleanly if an earlier attempt
        // created some schema objects before its data backfill was interrupted.
        if (! Schema::hasColumn('coupons', 'recipient_party_id')) {
            Schema::table('coupons', fn (Blueprint $table) => $table->unsignedBigInteger('recipient_party_id')->nullable());
        }
        $recipientForeign = collect(Schema::getForeignKeys('coupons'))
            ->first(fn ($foreign) => $foreign['columns'] === ['recipient_party_id']);
        if (! $recipientForeign) {
            Schema::table('coupons', fn (Blueprint $table) => $table->foreign('recipient_party_id')->references('id')->on('parties')->cascadeOnDelete());
        } elseif ($recipientForeign['foreign_table'] !== 'parties' || $recipientForeign['foreign_columns'] !== ['id'] || strtolower($recipientForeign['on_delete']) !== 'cascade') {
            throw new RuntimeException('Unexpected recipient_party_id foreign key; review the schema before retrying.');
        }
        if (! Schema::hasColumn('referral_program_milestones', 'retired_at')) {
            Schema::table('referral_program_milestones', fn (Blueprint $table) => $table->timestamp('retired_at')->nullable());
        }
        foreach (['referral_threshold', 'entitlement_key', 'recovery_due'] as $column) {
            if (Schema::hasColumn('referral_rewards', $column)) continue;
            Schema::table('referral_rewards', function (Blueprint $table) use ($column) {
                match ($column) {
                    'referral_threshold' => $table->unsignedInteger($column)->nullable(),
                    'entitlement_key' => $table->string($column, 100)->nullable(),
                    'recovery_due' => $table->decimal($column, 10, 2)->default(0),
                };
            });
        }
        if (! Schema::hasIndex('referral_rewards', ['entitlement_key'], 'unique')) {
            Schema::table('referral_rewards', fn (Blueprint $table) => $table->unique('entitlement_key'));
        }
        if (! Schema::hasTable('promotion_locks')) {
            Schema::create('promotion_locks', fn (Blueprint $table) => $table->string('name')->primary());
        }
        DB::table('promotion_locks')->insertOrIgnore(['name' => 'referral_program']);
        foreach (['coupons', 'offers'] as $table) {
            DB::table($table)->orderBy('id')->chunkById(200, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    $values = [];
                    foreach (['applicable_products', 'applicable_categories', 'excluded_products', 'excluded_categories'] as $field) {
                        if ($row->$field !== null) {
                            $values[$field] = $this->normalizeTargets($row->$field, $table.'.'.$row->id.'.'.$field);
                        }
                    }
                    if ($values) DB::table($table)->where('id', $row->id)->update($values);
                }
            });
        }
        // Older customers get a code in this explicit write migration, never during a page GET.
        DB::table('parties')->where('type', 'customer')->whereNull('deleted_at')
            ->where(fn ($query) => $query->whereNull('referral_code')->orWhere('referral_code', ''))
            ->orderBy('id')->chunkById(200, function ($rows) {
                DB::transaction(function () use ($rows) {
                    foreach ($rows as $row) {
                        for ($attempt = 0; $attempt < 5; $attempt++) {
                            try {
                                DB::table('parties')->where('id', $row->id)
                                    ->where(fn ($query) => $query->whereNull('referral_code')->orWhere('referral_code', ''))
                                    ->update(['referral_code' => 'REF-'.strtoupper(bin2hex(random_bytes(8)))]);
                                break;
                            } catch (\Illuminate\Database\UniqueConstraintViolationException $error) {
                                if ($attempt === 4) throw $error;
                            }
                        }
                    }
                }, 3);
            });
        // Backfill only rewards explicitly linked to their issued coupon.
        DB::table('referral_rewards')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $threshold = DB::table('referral_program_milestones')->where('id', $row->milestone_id)->value('required_referrals');
                DB::table('referral_rewards')->where('id', $row->id)->whereNull('referral_threshold')->update(['referral_threshold' => $threshold]);
                if (! $row->coupon_code) continue;
                $changes = ['recipient_party_id' => $row->referrer_id];
                if ($row->reward_type === 'product' && DB::table('products')->where('id', $row->reward_value)->exists()) {
                    $changes += ['type' => 'free_product', 'value' => 0, 'free_product_id' => $row->reward_value, 'free_qty' => 1, 'applicable_products' => null];
                }
                if ($row->reward_type === 'product' && ! isset($changes['free_product_id'])) {
                    $changes += ['is_active' => false, 'status' => 'inactive'];
                }
                DB::table('coupons')->where('code', $row->coupon_code)->update($changes);
            }
        });
    }

    private function normalizeTargets($value, string $location): ?string
    {
        try {
            for ($depth = 0; is_string($value) && $depth < 3; $depth++) {
                $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            }
        } catch (JsonException $error) {
            throw new RuntimeException($location.' is not a valid promotion target array.', 0, $error);
        }
        if ($value === null) return null;
        if (! is_array($value)) throw new RuntimeException($location.' must contain an array of positive integer IDs.');
        foreach ($value as $id) {
            if ((! is_int($id) && ! is_string($id) && ! is_float($id)) || ! is_numeric($id)
                || ! is_finite((float) $id) || (int) $id <= 0 || (float) $id !== (float) (int) $id) {
                throw new RuntimeException($location.' contains an invalid target ID; review it before migrating.');
            }
        }
        return json_encode(array_values(array_unique(array_map('intval', $value))), JSON_THROW_ON_ERROR);
    }

    public function down(): void
    {
        // Dropping these fields would remove ownership or financial history.
        // Refuse before any DDL so operators can use a reviewed forward fix instead.
        $hasHistory = Schema::hasColumn('coupons', 'recipient_party_id')
            && DB::table('coupons')->whereNotNull('recipient_party_id')->exists();
        $hasHistory = $hasHistory || (Schema::hasColumn('referral_program_milestones', 'retired_at')
            && DB::table('referral_program_milestones')->whereNotNull('retired_at')->exists());
        foreach (['referral_threshold', 'entitlement_key', 'recovery_due'] as $column) {
            if (! Schema::hasColumn('referral_rewards', $column)) continue;
            $query = DB::table('referral_rewards');
            $hasHistory = $hasHistory || ($column === 'recovery_due'
                ? $query->where($column, '>', 0)->exists()
                : $query->whereNotNull($column)->exists());
        }
        if ($hasHistory) {
            throw new RuntimeException('Cannot roll back promotion ownership or reward history. Preserve the data and deploy a reviewed forward migration.');
        }

        Schema::dropIfExists('promotion_locks');
        foreach (Schema::getIndexes('referral_rewards') as $index) {
            if ($index['unique'] && ! $index['primary'] && $index['columns'] === ['entitlement_key']) {
                Schema::table('referral_rewards', fn (Blueprint $table) => $table->dropUnique($index['name']));
            }
        }
        $rewardColumns = array_values(array_filter(['referral_threshold', 'entitlement_key', 'recovery_due'],
            fn ($column) => Schema::hasColumn('referral_rewards', $column)));
        if ($rewardColumns) Schema::table('referral_rewards', fn (Blueprint $table) => $table->dropColumn($rewardColumns));
        if (Schema::hasColumn('referral_program_milestones', 'retired_at')) {
            Schema::table('referral_program_milestones', fn (Blueprint $table) => $table->dropColumn('retired_at'));
        }
        if (Schema::hasColumn('coupons', 'recipient_party_id')) {
            foreach (Schema::getForeignKeys('coupons') as $foreign) {
                if ($foreign['columns'] === ['recipient_party_id']) {
                    Schema::table('coupons', fn (Blueprint $table) => $table->dropForeign($foreign['name']));
                }
            }
            Schema::table('coupons', fn (Blueprint $table) => $table->dropColumn('recipient_party_id'));
        }
    }
};
