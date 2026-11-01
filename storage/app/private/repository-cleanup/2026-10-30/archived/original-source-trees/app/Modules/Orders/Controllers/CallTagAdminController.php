<?php

namespace App\Modules\Orders\Controllers;

use App\Models\CallTag;
use App\Models\CallTagFormField;
use App\Modules\Core\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CallTagAdminController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:calltag-view', only: ['index']),
            new Middleware('permission:calltag-create', only: ['store']),
            new Middleware('permission:calltag-edit', only: ['update']),
            new Middleware('permission:calltag-delete', only: ['destroy']),
            new Middleware('permission:calltag-edit|calltag-delete', only: ['bulkAction']),
        ];
    }

    private function validateFields(array $data): void
    {
        $errors = [];
        $names = [];
        foreach ($data['form_fields'] ?? [] as $index => $field) {
            $key = "form_fields.$index";
            if (! preg_match('/^[a-z][a-z0-9_]{0,63}$/', $field['name']) || in_array($field['name'], ['__proto__', 'constructor', 'prototype'])) {
                $errors[$key.'.name'] = 'Use a letter followed by lowercase letters, numbers or underscores for field keys.';
            }
            if (in_array($field['name'], $names, true)) {
                $errors[$key.'.name'] = 'Field keys must be unique within a subject.';
            }
            $names[] = $field['name'];
            if (! in_array($field['type'], ['text', 'textarea', 'date', 'datetime-local', 'number', 'select', 'multi_select', 'product_search', 'agent_search'], true)) {
                $errors[$key.'.type'] = 'Unsupported field type.';
            }
            if (in_array($field['type'], ['select', 'multi_select'], true) && strtolower($field['name']) !== 'crop') {
                $options = json_decode($field['options'] ?? 'null', true);
                if (! is_array($options) || ! array_is_list($options) || ! $options || count(array_filter($options, fn ($value) => ! is_string($value) || trim($value) === '')) || count(array_unique($options, SORT_REGULAR)) !== count($options)) {
                    $errors[$key.'.options'] = 'Provide a JSON array of unique, non-empty text options.';
                }
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function index()
    {
        // Load tags hierarchically
        $tags = CallTag::with(['children' => function ($q) {
            $q->orderBy('sort_order')->with(['children' => function ($q2) {
                $q2->orderBy('sort_order');
            }, 'formFields']);
        }])->whereNull('parent_id')->orderBy('sort_order')->get();

        $totals = CallTag::query()->toBase()->selectRaw('
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) as active,
            COALESCE(SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END), 0) as inactive,
            COALESCE(SUM(CASE WHEN level = 1 THEN 1 ELSE 0 END), 0) as level_1
        ')->first();
        $stats = [
            'total' => (int) $totals->total,
            'active' => (int) $totals->active,
            'inactive' => (int) $totals->inactive,
            'level_1' => (int) $totals->level_1,
        ];

        $tagRows = [];
        foreach ($tags as $l1) {
            $tagRows[] = ['id' => $l1->id, 'name' => $l1->name, 'active' => (bool) $l1->is_active, 'ancestors' => [], 'path' => $l1->name];
            foreach ($l1->children as $l2) {
                $tagRows[] = ['id' => $l2->id, 'name' => $l2->name, 'active' => (bool) $l2->is_active, 'ancestors' => [$l1->id], 'path' => $l1->name.' '.$l2->name];
                foreach ($l2->children as $l3) {
                    $tagRows[] = ['id' => $l3->id, 'name' => $l3->name, 'active' => (bool) $l3->is_active, 'ancestors' => [$l1->id, $l2->id], 'path' => $l1->name.' '.$l2->name.' '.$l3->name];
                }
            }
        }

        if (request()->is('api/*')) {
            return response()->json(compact('tags', 'stats', 'tagRows'));
        }

        return view('call-tags.index', compact('tags', 'stats', 'tagRows'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:20',
            'parent_id' => 'nullable|exists:call_tags,id',
            'level' => 'required|integer|in:1,2,3',
            'is_active' => 'boolean',
            'form_fields' => 'nullable|array',
            'form_fields.*.name' => 'required|string|max:64',
            'form_fields.*.label' => 'required|string|max:255',
            'form_fields.*.type' => 'required|string',
            'form_fields.*.options' => 'nullable|json',
            'form_fields.*.placeholder' => 'nullable|string|max:255',
            'form_fields.*.validation_rules' => 'nullable|string|max:255',
            'form_fields.*.is_required' => 'boolean',
        ]);

        $this->validateFields($validated);
        $parent = empty($validated['parent_id']) ? null : CallTag::find($validated['parent_id']);
        if (((int) $validated['level'] === 1 && ! empty($validated['parent_id'])) || ((int) $validated['level'] > 1 && (! $parent || (int) $parent->level !== (int) $validated['level'] - 1))) {
            throw ValidationException::withMessages(['parent_id' => 'Select a parent from the preceding tag level.']);
        }

        try {
            DB::beginTransaction();
            $tag = CallTag::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'color' => $validated['color'] ?? null,
                'parent_id' => $validated['parent_id'] ?? null,
                'level' => $validated['level'],
                'is_active' => filter_var($validated['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'sort_order' => CallTag::where('parent_id', $validated['parent_id'] ?? null)->max('sort_order') + 1,
            ]);

            if (! empty($validated['form_fields']) && $tag->level == 2) {
                foreach ($validated['form_fields'] as $index => $field) {
                    CallTagFormField::create([
                        'call_tag_id' => $tag->id,
                        'name' => $field['name'],
                        'label' => $field['label'],
                        'type' => $field['type'],
                        'options' => empty($field['options']) ? null : $field['options'],
                        'placeholder' => $field['placeholder'] ?? null,
                        'validation_rules' => $field['validation_rules'] ?? null,
                        'is_required' => filter_var($field['is_required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'sort_order' => $index + 1,
                    ]);
                }
            }
            DB::commit();

            return response()->json(['message' => 'Tag created successfully', 'tag' => $tag->load('formFields')]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to create tag', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, CallTag $callTag)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'form_fields' => 'nullable|array',
            'form_fields.*.name' => 'required|string|max:64',
            'form_fields.*.label' => 'required|string|max:255',
            'form_fields.*.type' => 'required|string',
            'form_fields.*.options' => 'nullable|json',
            'form_fields.*.placeholder' => 'nullable|string|max:255',
            'form_fields.*.validation_rules' => 'nullable|string|max:255',
            'form_fields.*.is_required' => 'boolean',
        ]);

        $this->validateFields($validated);

        try {
            DB::beginTransaction();
            $callTag->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'color' => $validated['color'] ?? null,
                'is_active' => filter_var($validated['is_active'] ?? $callTag->is_active, FILTER_VALIDATE_BOOLEAN),
            ]);

            if ($callTag->level == 2 && array_key_exists('form_fields', $validated)) {
                $callTag->formFields()->delete();
                if (! empty($validated['form_fields'])) {
                    foreach ($validated['form_fields'] as $index => $field) {
                        CallTagFormField::create([
                            'call_tag_id' => $callTag->id,
                            'name' => $field['name'],
                            'label' => $field['label'],
                            'type' => $field['type'],
                            'options' => empty($field['options']) ? null : $field['options'],
                            'placeholder' => $field['placeholder'] ?? null,
                            'validation_rules' => $field['validation_rules'] ?? null,
                            'is_required' => filter_var($field['is_required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'sort_order' => $index + 1,
                        ]);
                    }
                }
            }
            DB::commit();

            return response()->json(['message' => 'Tag updated successfully', 'tag' => $callTag->load('formFields')]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to update tag', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(CallTag $callTag)
    {
        try {
            DB::transaction(fn () => $this->deleteTree([$callTag->id]));

            return response()->json(['message' => 'Tag deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete tag', 'error' => $e->getMessage()], 500);
        }
    }

    private function deleteTree(array $ids): void
    {
        $all = [];
        $pending = $ids;
        while ($pending) {
            $all = array_unique([...$all, ...$pending]);
            $pending = CallTag::whereIn('parent_id', $pending)->whereNotIn('id', $all)->pluck('id')->all();
        }
        CallTag::whereIn('id', $all)->get()->each->delete();
    }

    public function bulkAction(Request $request)
    {
        $data = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:call_tags,id',
        ]);
        abort_unless($request->user()?->can($request->input('action') === 'delete' ? 'calltag-delete' : 'calltag-edit'), 403);

        $ids = $data['ids'];
        $action = $data['action'];

        DB::transaction(function () use ($action, $ids) {
            if ($action === 'delete') {
                $this->deleteTree($ids);
            } elseif ($action === 'activate') {
                CallTag::whereIn('id', $ids)->get()->each->update(['is_active' => true]);
            } elseif ($action === 'deactivate') {
                CallTag::whereIn('id', $ids)->get()->each->update(['is_active' => false]);
            }

        });

        return response()->json(['message' => 'Bulk action completed successfully.']);
    }
}
