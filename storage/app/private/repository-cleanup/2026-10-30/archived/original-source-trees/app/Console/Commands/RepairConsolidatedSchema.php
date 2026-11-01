<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairConsolidatedSchema extends Command
{
    protected $signature = 'schema:repair-consolidated {--apply : Apply the additive repairs and adopt already-created split tables}';

    protected $description = 'Repair existing installations after migration consolidation; previews changes unless --apply is provided';

    public function handle(): int
    {
        foreach (['migrations', 'shipments', 'shipment_tracking_events'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Missing baseline table: {$table}. Run the normal migrations on a fresh installation.");

                return self::FAILURE;
            }
        }

        $columns = [
            'shipments' => ['india_post_tracking_response' => 'json'],
            'shipment_tracking_events' => [
                'provider' => 'string',
                'provider_event_code' => 'string',
                'provider_office_id' => 'string',
                'provider_remarks' => 'text',
                'provider_is_rts' => 'boolean',
                'provider_payload' => 'json',
            ],
        ];
        $changes = 0;

        foreach ($columns as $tableName => $definitions) {
            foreach ($definitions as $column => $type) {
                if (Schema::hasColumn($tableName, $column)) {
                    continue;
                }

                $this->line("Add nullable column: {$tableName}.{$column}");
                $changes++;
                if ($this->option('apply')) {
                    Schema::table($tableName, function (Blueprint $table) use ($column, $type): void {
                        $table->{$type}($column)->nullable();
                    });
                }
            }
        }

        foreach (['provider', 'provider_event_code'] as $column) {
            $index = "shipment_tracking_events_{$column}_index";
            if (! Schema::hasIndex('shipment_tracking_events', $index)) {
                $this->line("Add index: {$index}");
                $changes++;
                if ($this->option('apply')) {
                    Schema::table('shipment_tracking_events', function (Blueprint $table) use ($column, $index): void {
                        $table->index($column, $index);
                    });
                }
            }
        }

        // A previously recorded combined migration proves that these tables were
        // already created. Keep its batch so rollback order remains consistent.
        foreach ($this->splitMigrations() as $source => $tables) {
            $batch = DB::table('migrations')->where('migration', $source)->value('batch');
            if ($batch === null) {
                continue;
            }

            foreach ($tables as $table => $migration) {
                if (DB::table('migrations')->where('migration', $migration)->exists()) {
                    continue;
                }
                if (! Schema::hasTable($table)) {
                    $this->error("Cannot adopt {$migration}: table {$table} is missing.");

                    return self::FAILURE;
                }

                $this->line("Adopt existing table: {$table} (batch {$batch})");
                $changes++;
                if ($this->option('apply')) {
                    DB::table('migrations')->insert(['migration' => $migration, 'batch' => $batch]);
                }
            }
        }

        $this->info($changes === 0
            ? 'Schema is already reconciled; no changes needed.'
            : ($this->option('apply') ? 'Schema repair completed.' : 'Preview only. Run with --apply to make these changes.'));

        return self::SUCCESS;
    }

    private function splitMigrations(): array
    {
        return [
            '0001_01_01_000001_create_cache_table' => [
                'cache_locks' => '0001_01_01_000001a_create_cache_locks_table',
            ],
            '0001_01_01_000002_create_jobs_table' => [
                'job_batches' => '0001_01_01_000002a_create_job_batches_table',
                'failed_jobs' => '0001_01_01_000002b_create_failed_jobs_table',
            ],
            '2026_06_01_000100_create_chat_module_tables' => [
                'chat_conversations' => '2026_06_01_000100_create_chat_conversations_table',
                'chat_members' => '2026_06_01_000100a_create_chat_members_table',
                'chat_messages' => '2026_06_01_000100b_create_chat_messages_table',
                'chat_message_reads' => '2026_06_01_000100c_create_chat_message_reads_table',
                'chat_message_deliveries' => '2026_06_01_000100d_create_chat_message_deliveries_table',
                'chat_presence' => '2026_06_01_000100e_create_chat_presence_table',
                'chat_notifications' => '2026_06_01_000100f_create_chat_notifications_table',
                'chat_audit_logs' => '2026_06_01_000100g_create_chat_audit_logs_table',
            ],
            '2026_07_07_000016b_create_orders_table' => [
                'order_items' => '2026_07_07_000016ba_create_order_items_table',
                'order_status_logs' => '2026_07_07_000016bb_create_order_status_logs_table',
            ],
        ];
    }
}
