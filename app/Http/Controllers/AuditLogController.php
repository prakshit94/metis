<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Support\Facades\Gate;

class AuditLogController
{
    /**
     * Display a listing of the audit logs.
     */
    public function index(Request $request)
    {
        // Enforce basic permission if you have one, or just require auth for now.
        // Assuming Super Admin can view, but let's allow basic view logic.
        // We will pass the data as JSON if it's an AJAX request (for Alpine) or standard Blade.

        if ($request->wantsJson()) {
            $query = Audit::with('user')->latest();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('event', 'like', "%{$search}%")
                      ->orWhere('auditable_type', 'like', "%{$search}%")
                      ->orWhereHas('user', function($uq) use ($search) {
                          $uq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('event')) {
                $query->where('event', $request->event);
            }

            $perPage = $request->input('per_page', 15);
            $audits = $query->paginate($perPage);

            // Transform the class names for frontend friendly display
            $audits->getCollection()->transform(function ($audit) {
                $modelParts = explode('\\', $audit->auditable_type);
                $audit->model_name = end($modelParts);
                return $audit;
            });

            // Calculate stats
            $stats = [
                'total' => Audit::count(),
                'created' => Audit::where('event', 'created')->count(),
                'updated' => Audit::where('event', 'updated')->count(),
                'deleted' => Audit::where('event', 'deleted')->count(),
            ];

            return response()->json([
                'data' => $audits->items(),
                'total' => $audits->total(),
                'current_page' => $audits->currentPage(),
                'last_page' => $audits->lastPage(),
                'stats' => $stats,
            ]);
        }

        return view('admin.audit-logs.index');
    }

    /**
     * Clear all audit logs (Super Admin only).
     */
    public function clearAll(Request $request)
    {
        if (!auth()->user()->hasRole('Super Admin')) {
            return response()->json(['message' => 'Unauthorized action. Only Super Admin can clear logs.'], 403);
        }

        Audit::truncate();

        return response()->json(['message' => 'All audit logs have been cleared successfully.']);
    }

    /**
     * Delete specific logs.
     */
    public function destroy(Request $request)
    {
        if (!auth()->user()->hasRole('Super Admin')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer'
        ]);

        Audit::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected logs deleted.']);
    }
}
