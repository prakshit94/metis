<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Core\Models\Village;

class DebugVillages extends Command
{
    protected $signature = 'debug:villages';
    protected $description = 'Debug villages sync query';

    public function handle()
    {
        $total = Village::count();
        $this->info("Total villages: " . $total);

        $nullOfficeId = Village::whereNull('office_id')->count();
        $this->info("Null office_id: " . $nullOfficeId);

        $emptyOfficeId = Village::where('office_id', '')->count();
        $this->info("Empty string office_id: " . $emptyOfficeId);

        $nullTypeCode = Village::whereNull('office_type_code')->count();
        $this->info("Null office_type_code: " . $nullTypeCode);

        $emptyTypeCode = Village::where('office_type_code', '')->count();
        $this->info("Empty string office_type_code: " . $emptyTypeCode);

        $invalid = Village::where('office_type_code', 'INVALID')->count();
        $this->info("INVALID office_type_code: " . $invalid);

        $failed = Village::where('office_type_code', 'FAILED')->count();
        $this->info("FAILED office_type_code: " . $failed);

        $apiError = Village::where('office_type_code', 'API_ERROR')->count();
        $this->info("API_ERROR office_type_code: " . $apiError);

        $query = Village::where(function($q) {
            $q->whereNull('office_id')
              ->orWhereNull('office_type_code')
              ->orWhereIn('office_type_code', ['INVALID', 'FAILED', 'API_ERROR', '']);
        });

        $this->info("Query SQL: " . $query->toSql());
        $this->info("Query Bindings: " . json_encode($query->getBindings()));
        $this->info("Total matching query: " . $query->count());

        $firstFew = Village::limit(5)->get();
        foreach($firstFew as $v) {
            $this->info("Village {$v->id} - Pincode: {$v->pincode}, Office ID: '{$v->office_id}', Type: '{$v->office_type_code}'");
        }
    }
}
