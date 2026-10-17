<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\Users\Models\User;
use Illuminate\Support\Facades\DB;

class EmployeeIdService
{
    public function save(User $user): void
    {
        // Ordinary edits and manual IDs retain the existing save path.
        if (trim((string) $user->employee_id) !== '') {
            $user->save();

            return;
        }

        DB::transaction(function () use ($user): void {
            // Reuse the existing settings table; the unique key also serializes
            // first-time initialization when multiple requests arrive together.
            $key = 'users.employee_id.last_number';
            DB::table('system_settings')->upsert([[
                'key' => $key, 'value' => '0', 'created_at' => now(), 'updated_at' => now(),
            ]], ['key'], ['key']);
            $sequence = DB::table('system_settings')->where('key', $key)->lockForUpdate()->first();
            $last = (int) $sequence->value;

            // Include existing and soft-deleted IDs, including IDs entered by imports.
            foreach (DB::table('users')->where('employee_id', 'like', 'EMP-%')->pluck('employee_id') as $id) {
                if (preg_match('/^EMP-(\d{1,15})$/i', trim($id), $match)) {
                    $last = max($last, (int) $match[1]);
                }
            }

            do {
                $user->employee_id = 'EMP-'.str_pad((string) ++$last, 4, '0', STR_PAD_LEFT);
            } while (DB::table('users')->where('employee_id', $user->employee_id)->exists());

            $user->save();
            DB::table('system_settings')->where('key', $key)->update([
                'value' => (string) $last, 'updated_at' => now(),
            ]);
        });
    }
}
