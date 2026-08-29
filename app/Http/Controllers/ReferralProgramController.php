<?php

namespace App\Http\Controllers;

use App\Models\ReferralProgram;
use App\Modules\Catalog\Models\Product;
use App\Modules\Core\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class ReferralProgramController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:promotions-view', only: ['index']),
            new Middleware('permission:promotions-create', only: ['store']),
            new Middleware('permission:promotions-edit', only: ['update', 'toggle', 'bulk']),
            new Middleware('permission:promotions-delete', only: ['destroy']),
        ];
    }

    public function index()
    {
        $programs = ReferralProgram::with('milestones')->latest()->get();

        $products = cache()->remember('referral_products_list', now()->addMinutes(60), function () {
            return Product::where('status', '!=', 'draft')
                ->orderBy('name')
                ->get(['id', 'name', 'sku']);
        });

        return view('promotions.referrals.index', compact('programs', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'milestones' => 'required|array|min:1',
            'milestones.*.required_referrals' => 'required|integer|min:0',
            'milestones.*.reward_type' => 'required|string|in:wallet,product,coupon',
            'milestones.*.reward_value' => 'required|string',
        ]);

        DB::transaction(function () use ($validated) {
            if (! empty($validated['is_active'])) {
                ReferralProgram::where('is_active', true)->update(['is_active' => false]);
            }

            $program = ReferralProgram::create([
                'name' => $validated['name'],
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'is_active' => $validated['is_active'] ?? false,
            ]);

            $program->milestones()->createMany($validated['milestones']);
        });

        return back()->with('success', 'Referral program created successfully.');
    }

    public function update(Request $request, $id)
    {
        $program = ReferralProgram::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'milestones' => 'required|array|min:1',
            'milestones.*.required_referrals' => 'required|integer|min:0',
            'milestones.*.reward_type' => 'required|string|in:wallet,product,coupon',
            'milestones.*.reward_value' => 'required|string',
        ]);

        DB::transaction(function () use ($validated, $program) {
            if (! empty($validated['is_active'])) {
                ReferralProgram::where('id', '!=', $program->id)->where('is_active', true)->update(['is_active' => false]);
            }

            $program->update([
                'name' => $validated['name'],
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'is_active' => $validated['is_active'] ?? false,
            ]);

            $program->milestones()->delete();
            $program->milestones()->createMany($validated['milestones']);
        });

        return back()->with('success', 'Referral program updated successfully.');
    }

    public function toggle($id)
    {
        $program = ReferralProgram::findOrFail($id);

        DB::transaction(function () use ($program) {
            if (! $program->is_active) {
                ReferralProgram::where('id', '!=', $program->id)->where('is_active', true)->update(['is_active' => false]);
            }
            $program->update(['is_active' => ! $program->is_active]);
        });

        return back()->with('success', 'Referral program status updated.');
    }

    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string|in:activate,deactivate,delete',
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:referral_programs,id',
        ]);

        $action = $validated['action'];
        $ids = $validated['ids'];

        DB::transaction(function () use ($action, $ids) {
            if ($action === 'delete') {
                ReferralProgram::whereIn('id', $ids)->delete();
            } elseif ($action === 'deactivate') {
                ReferralProgram::whereIn('id', $ids)->update(['is_active' => false]);
            } elseif ($action === 'activate') {
                // Only one program can be active at a time — activate the first ID only.
                $firstId = collect($ids)->first();
                if ($firstId) {
                    ReferralProgram::where('id', '!=', $firstId)->where('is_active', true)->update(['is_active' => false]);
                    ReferralProgram::where('id', $firstId)->update(['is_active' => true]);
                }
            }
        });

        return response()->json(['message' => 'Bulk action completed successfully.']);
    }

    public function destroy($id)
    {
        ReferralProgram::findOrFail($id)->delete();

        return back()->with('success', 'Referral program deleted.');
    }
}
