<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete()->after('designation');
            $table->foreignId('employment_type_id')->nullable()->constrained('employment_types')->nullOnDelete()->after('employment_type');
        });

        // Data migration
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $updateData = [];
            
            if ($user->designation) {
                $designation = DB::table('designations')->where('name', $user->designation)->first();
                if ($designation) {
                    $updateData['designation_id'] = $designation->id;
                } else {
                    $newDesignationId = DB::table('designations')->insertGetId([
                        'name' => $user->designation,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $updateData['designation_id'] = $newDesignationId;
                }
            }

            if ($user->employment_type) {
                $empType = DB::table('employment_types')->where('name', $user->employment_type)->first();
                if ($empType) {
                    $updateData['employment_type_id'] = $empType->id;
                } else {
                    $newEmpTypeId = DB::table('employment_types')->insertGetId([
                        'name' => $user->employment_type,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $updateData['employment_type_id'] = $newEmpTypeId;
                }
            }

            if (!empty($updateData)) {
                DB::table('users')->where('id', $user->id)->update($updateData);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['designation_id']);
            $table->dropForeign(['employment_type_id']);
            $table->dropColumn(['designation_id', 'employment_type_id']);
        });
    }
};
