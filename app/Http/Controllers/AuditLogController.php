<?php

namespace App\Http\Controllers;

use App\Modules\Core\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:audit-log-view', only: ['index']),
            new Middleware('permission:audit-log-delete', only: ['clearAll', 'destroy']),
        ];
    }

    /**
     * Display a listing of the audit logs.
     * 
     * @return array{data: array<int, \OwenIt\Auditing\Models\Audit&array{model_name: string, user: \App\Modules\Users\Models\User|null}>, total: int, current_page: int, last_page: int, stats: array{total: int, created: int, updated: int, deleted: int}}
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
                $query->where(function ($q) use ($search) {
                    $q->where('event', 'like', "%{$search}%")
                        ->orWhere('auditable_type', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->filled('event')) {
                $query->where('event', $request->event);
            }

            $perPage = $request->input('per_page', 15);
            $audits = $query->paginate($perPage);

            // Collect all user IDs from old/new values to prevent N+1 queries
            $userIdsToFetch = [];
            $userKeys = ['created_by', 'updated_by', 'changed_by', 'deleted_by', 'user_id', 'manager_id'];
            
            foreach ($audits as $audit) {
                foreach (['old_values', 'new_values'] as $valueType) {
                    $values = $audit->{$valueType};
                    if (is_array($values)) {
                        foreach ($userKeys as $key) {
                            if (isset($values[$key]) && is_numeric($values[$key])) {
                                $userIdsToFetch[] = $values[$key];
                            }
                        }
                    }
                }
            }
            
            $userIdsToFetch = array_unique($userIdsToFetch);
            $userNames = count($userIdsToFetch) > 0 
                ? \App\Modules\Users\Models\User::whereIn('id', $userIdsToFetch)->pluck('name', 'id')->toArray() 
                : [];

            // Transform the class names for frontend friendly display
            $audits->getCollection()->transform(function ($audit) use ($userKeys, $userNames) {
                $modelParts = explode('\\', $audit->auditable_type);
                $audit->model_name = end($modelParts);

                // Replace user IDs with actual names in the JSON payload
                $oldValues = $audit->old_values;
                $newValues = $audit->new_values;

                foreach ($userKeys as $key) {
                    if (is_array($oldValues) && isset($oldValues[$key]) && isset($userNames[$oldValues[$key]])) {
                        $oldValues[$key] = $userNames[$oldValues[$key]] . ' (ID: ' . $oldValues[$key] . ')';
                    }
                    if (is_array($newValues) && isset($newValues[$key]) && isset($userNames[$newValues[$key]])) {
                        $newValues[$key] = $userNames[$newValues[$key]] . ' (ID: ' . $newValues[$key] . ')';
                    }
                }

                $formatValue = function ($key, $value) {
                    if (is_null($value)) return $value;

                    // Format amounts
                    $amountKeys = ['amount', 'price', 'cost', 'tax', 'discount', 'total', 'net'];
                    foreach ($amountKeys as $ak) {
                        if (str_contains($key, $ak) && is_numeric($value)) {
                            return '₹' . number_format((float)$value, 2);
                        }
                    }

                    // Format dates
                    $dateKeys = ['_at', '_date', 'date_', 'time_', '_until', 'dob'];
                    foreach ($dateKeys as $dk) {
                        if (str_contains($key, $dk) && is_string($value) && strtotime($value) !== false) {
                            try {
                                return \Illuminate\Support\Carbon::parse($value)->format('d M Y, h:i A');
                            } catch (\Exception $e) {
                                return $value;
                            }
                        }
                    }

                    return $value;
                };

                if (is_array($oldValues)) {
                    foreach ($oldValues as $k => $v) {
                        $oldValues[$k] = $formatValue($k, $v);
                    }
                }

                if (is_array($newValues)) {
                    foreach ($newValues as $k => $v) {
                        $newValues[$k] = $formatValue($k, $v);
                    }
                }

                $audit->old_values = $oldValues;
                $audit->new_values = $newValues;

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
        if (! auth()->user()->hasRole('Super Admin')) {
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
        if (! auth()->user()->hasRole('Super Admin')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:audits,id',
        ]);

        Audit::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected logs deleted.']);
    }

    /**
     * Fetch recent activity logs for the authenticated user.
     */
    public function recentActivities(Request $request)
    {
        abort_unless(
            $request->user()?->can('audit-log-view')
            || $request->user()?->hasAnyRole(['Super Admin', 'Admin']),
            403,
            'You do not have permission to view activity logs.'
        );

        $user = auth()->user();
        $activities = Activity::with('causer')->latest()->limit(15)->get();
        $readIds = $user ? $user->readActivities()->whereIn('activity_id', $activities->pluck('id'))->pluck('activity_id')->toArray() : [];

        $unreadCount = $activities->whereNotIn('id', $readIds)->count();

        return response()->json([
            'count' => $unreadCount,
            'activities' => $activities->map(function ($a) use ($readIds) {
                return [
                    'id' => $a->id,
                    'description' => $a->description,
                    'subject_type' => class_basename($a->subject_type),
                    'causer_name' => $a->causer->name ?? 'System',
                    'causer_photo' => $a->causer->photo ?? null,
                    'time_ago' => $a->created_at->diffForHumans(),
                    'is_read' => in_array($a->id, $readIds),
                ];
            }),
        ]);
    }

    /**
     * Mark specific or all activities as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['success' => false], 401);
        }

        if ($id === 'all') {
            $activityIds = Activity::latest()->limit(50)->pluck('id');
            $user->readActivities()->syncWithoutDetaching($activityIds);
        } else {
            $user->readActivities()->syncWithoutDetaching([$id]);
        }

        return response()->json(['success' => true]);
    }
}
