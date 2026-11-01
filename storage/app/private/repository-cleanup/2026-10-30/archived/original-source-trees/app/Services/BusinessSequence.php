<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** Database locks shared by HTTP, queue and CLI workers; scoped by business key. */
class BusinessSequence
{
    public function run(string $name, \Closure $operation): mixed
    {
        return DB::transaction(function () use ($name, $operation) {
            // An upsert takes an exclusive lock directly; INSERT IGNORE would take
            // shared duplicate-key locks that deadlock when multiple workers upgrade.
            DB::table('business_sequences')->upsert([['name' => $name, 'value' => null]], ['name'], ['name']);
            $row = DB::table('business_sequences')->where('name', $name)->lockForUpdate()->first();

            return $operation($row);
        }, 3);
    }

    /** AWBs are consumed before remote booking; never reuse them on a booking rollback. */
    public function reserveCommitted(string $name, \Closure $operation): mixed
    {
        $connection = DB::getDefaultConnection();
        $allocationConnection = 'metis_awb_allocation';
        config(['database.connections.'.$allocationConnection => config('database.connections.'.$connection)]);
        try {
            return DB::usingConnection($allocationConnection, fn () => $this->run($name, $operation));
        } finally {
            DB::disconnect($allocationConnection);
        }
    }

    public function next(string $name, \Closure $initialValue): int
    {
        return $this->run($name, function ($row) use ($name, $initialValue) {
            $value = (int) ($row->value ?? $initialValue()) + 1;
            DB::table('business_sequences')->where('name', $name)->update(['value' => $value]);

            return $value;
        });
    }
}
