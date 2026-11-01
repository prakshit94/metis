<?php

namespace App\Http\Controllers;

use App\Models\ReferralProgram;
use App\Modules\Catalog\Models\Product;
use App\Modules\Core\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReferralProgramController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:promotions-view', only: ['index']),
            new Middleware('permission:promotions-create', only: ['store']),
            new Middleware('permission:promotions-edit', only: ['update', 'toggle']),
            new Middleware('permission:promotions-delete', only: ['destroy']),
            new Middleware('permission:promotions-edit|promotions-delete', only: ['bulk']),
        ];
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => 'nullable|in:active,inactive,expired,scheduled',
        ]);
        $statusFilter = array_key_exists('status', $request->query())
            ? ($filters['status'] ?? '')
            : 'active';
        $today = today()->toDateString();
        $query = ReferralProgram::with('milestones')->latest();
        match ($statusFilter) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            'expired' => $query->whereDate('end_date', '<', $today),
            'scheduled' => $query->whereDate('start_date', '>', $today),
            default => null,
        };
        $pagination = $query->paginate(25)->withQueryString();
        $programs = $pagination->getCollection();
        $totals = ReferralProgram::query()->selectRaw('COUNT(*) as total,
            COALESCE(SUM(is_active = 1), 0) as active, COALESCE(SUM(is_active = 0), 0) as inactive,
            COALESCE(SUM(start_date IS NULL AND end_date IS NULL), 0) as permanent,
            COALESCE(SUM(start_date IS NOT NULL OR end_date IS NOT NULL), 0) as time_bound,
            COALESCE(SUM(end_date < ?), 0) as expired, COALESCE(SUM(start_date > ?), 0) as scheduled', [$today, $today])->first();
        $programStats = collect($totals->getAttributes())->map(fn ($value) => (int) $value)->all();

        $rewardProductIds = $programs->flatMap(fn ($program) => $program->milestones->where('reward_type', 'product')->pluck('reward_value'));
        $products = Product::where('is_active', true)->whereIn('status', ['active', 'published'])->orderBy('name')->limit(50)->get(['id', 'name', 'sku'])
            ->merge(Product::whereIn('id', $rewardProductIds)->get(['id', 'name', 'sku']))->unique('id')->values();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(compact('programs', 'products', 'programStats', 'pagination'));
        }

        return view('promotions.referrals.index', compact('programs', 'products', 'programStats', 'pagination'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'.($request->filled('start_date') ? '|after_or_equal:start_date' : ''),
            'is_active' => 'boolean',
            'milestones' => 'required|array|min:1|max:100',
            'milestones.*.required_referrals' => 'required|integer|min:0|distinct',
            'milestones.*.reward_type' => 'required|string|in:wallet,product,coupon',
            'milestones.*.reward_value' => 'required|string',
        ]);
        $this->validateMilestoneValues($validated['milestones']);

        DB::transaction(function () use ($validated) {
            $this->lockPrograms();
            $this->validateActivation($validated);
            if (! empty($validated['is_active'])) {
                ReferralProgram::where('is_active', true)->get()->each->update(['is_active' => false]);
            }

            $program = ReferralProgram::create([
                'name' => $validated['name'],
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'is_active' => $validated['is_active'] ?? false,
            ]);

            $program->milestones()->createMany($validated['milestones']);
        });

        if (request()->is('api/*')) {
            return response()->json(['message' => 'Referral program created successfully.']);
        }

        return back()->with('success', 'Referral program created successfully.');
    }

    public function update(Request $request, $id)
    {
        $program = ReferralProgram::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'.($request->filled('start_date') ? '|after_or_equal:start_date' : ''),
            'is_active' => 'boolean',
            'milestones' => 'required|array|min:1|max:100',
            'milestones.*.required_referrals' => 'required|integer|min:0|distinct',
            'milestones.*.reward_type' => 'required|string|in:wallet,product,coupon',
            'milestones.*.reward_value' => 'required|string',
        ]);
        $this->validateMilestoneValues($validated['milestones']);

        DB::transaction(function () use ($validated, $program) {
            $this->lockPrograms();
            $program->refresh();
            $this->validateActivation($validated, $program->id);
            if (! empty($validated['is_active'])) {
                ReferralProgram::where('id', '!=', $program->id)->where('is_active', true)->get()->each->update(['is_active' => false]);
            }

            $program->update([
                'name' => $validated['name'],
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'is_active' => $validated['is_active'] ?? false,
            ]);

            $retained = [];
            foreach ($validated['milestones'] as $values) {
                $milestone = $program->milestones()->where('required_referrals', $values['required_referrals'])->first();
                if ($milestone) {
                    $milestone->update($values);
                } else {
                    $milestone = $program->milestones()->create($values);
                }
                $retained[] = $milestone->id;
            }
            $program->milestones()->whereNotIn('id', $retained)->get()->each->update(['retired_at' => now()]);
        });

        if (request()->is('api/*')) {
            return response()->json(['message' => 'Referral program updated successfully.']);
        }

        return back()->with('success', 'Referral program updated successfully.');
    }

    public function toggle($id)
    {
        $program = ReferralProgram::findOrFail($id);

        DB::transaction(function () use ($program) {
            $this->lockPrograms();
            $program->refresh();
            $this->validateActivation(['is_active' => ! $program->is_active, 'start_date' => $program->start_date], $program->id);
            if (! $program->is_active) {
                ReferralProgram::where('id', '!=', $program->id)->where('is_active', true)->get()->each->update(['is_active' => false]);
            }
            $program->update(['is_active' => ! $program->is_active]);
        });

        if (request()->is('api/*')) {
            return response()->json(['message' => 'Referral program status updated.']);
        }

        return back()->with('success', 'Referral program status updated.');
    }

    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string|in:activate,deactivate,delete',
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'integer|exists:referral_programs,id',
        ]);

        $action = $validated['action'];
        $ids = $validated['ids'];
        abort_unless($request->user()?->can($action === 'delete' ? 'promotions-delete' : 'promotions-edit'), 403);

        DB::transaction(function () use ($action, $ids) {
            $this->lockPrograms();
            if ($action === 'delete') {
                ReferralProgram::whereIn('id', $ids)->get()->each(fn ($program) => $this->deleteProgram($program));
            } elseif ($action === 'deactivate') {
                ReferralProgram::whereIn('id', $ids)->get()->each->update(['is_active' => false]);
            } elseif ($action === 'activate') {
                // Only one program can be active at a time — activate the first ID only.
                $firstId = collect($ids)->first();
                if ($firstId) {
                    $first = ReferralProgram::findOrFail($firstId);
                    $this->validateActivation(['is_active' => true, 'start_date' => $first->start_date], $firstId);
                    ReferralProgram::where('id', '!=', $firstId)->where('is_active', true)->get()->each->update(['is_active' => false]);
                    ReferralProgram::findOrFail($firstId)->update(['is_active' => true]);
                }
            }
        });

        return response()->json(['message' => 'Bulk action completed successfully.']);
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $this->lockPrograms();
            $this->deleteProgram(ReferralProgram::findOrFail($id));
        });

        if (request()->is('api/*')) {
            return response()->json(['message' => 'Referral program deleted.']);
        }

        return back()->with('success', 'Referral program deleted.');
    }

    private function lockPrograms(): void
    {
        DB::table('promotion_locks')->where('name', 'referral_program')->lockForUpdate()->first();
    }

    private function deleteProgram(ReferralProgram $program): void
    {
        if (DB::table('referral_rewards')->where('referral_program_id', $program->id)->exists()) {
            throw ValidationException::withMessages(['program' => 'This program has issued rewards. Deactivate it to preserve its history.']);
        }
        $program->delete();
    }

    private function validateActivation(array $values, ?int $id = null): void
    {
        // Preserve the one-enabled-program policy without silently disabling today's rewards.
        if (! empty($values['is_active']) && ! empty($values['start_date'])
            && \Illuminate\Support\Carbon::parse($values['start_date'])->startOfDay()->gt(today())
            && ReferralProgram::where('is_active', true)->when($id, fn ($q) => $q->whereKeyNot($id))->exists()) {
            throw ValidationException::withMessages(['start_date' => 'Save the future program as inactive, then activate it at handover. The current program remains active.']);
        }
    }

    private function validateMilestoneValues(array $milestones): void
    {
        foreach ($milestones as $index => $milestone) {
            $value = $milestone['reward_value'] ?? '';

            if ($milestone['reward_type'] === 'product' && ! Product::whereKey($value)->where('is_active', true)->whereIn('status', ['active', 'published'])->exists()) {
                throw ValidationException::withMessages([
                    "milestones.{$index}.reward_value" => 'Select a valid reward product.',
                ]);
            }

            if (in_array($milestone['reward_type'], ['wallet', 'coupon'], true)
                && (! preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', (string) $value) || (float) $value < 0)) {
                throw ValidationException::withMessages([
                    "milestones.{$index}.reward_value" => 'Enter a valid non-negative reward amount.',
                ]);
            }
        }
    }
}
