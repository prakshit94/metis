<?php

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\Department;
use App\Modules\Users\Models\User;
use Illuminate\Http\JsonResponse;

class OrgChartController extends Controller
{
    public function index(): JsonResponse
    {
        // 1. Fetch departments with children (nested up to 3 levels) and their manager and users count
        $departments = Department::withCount('users')->with([
            'manager',
            'children' => function($q) {
                $q->withCount('users')->with([
                    'manager', 
                    'children' => function($q2) {
                        $q2->withCount('users')->with(['manager']);
                    }
                ]);
            }
        ])->whereNull('parent_id')->get();

        // 2. Fetch standalone users (CEO/Founders who don't belong to any department)
        $standaloneUsers = User::whereNull('department_id')
                                ->whereNull('manager_id')
                                ->with(['subordinates'])
                                ->get();

        return response()->json([
            'departments' => $departments,
            'standalone_users' => $standaloneUsers,
        ]);
    }
}
