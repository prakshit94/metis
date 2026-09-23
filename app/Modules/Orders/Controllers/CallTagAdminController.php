<?php

namespace App\Modules\Orders\Controllers;

use App\Models\CallTag;
use App\Models\CallTagFormField;
use App\Modules\Core\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallTagAdminController extends Controller
{
    public function index()
    {
        // Load tags hierarchically
        $tags = CallTag::with(['children' => function ($q) {
            $q->orderBy('sort_order')->with(['children' => function ($q2) {
                $q2->orderBy('sort_order');
            }, 'formFields']);
        }])->whereNull('parent_id')->orderBy('sort_order')->get();

        $stats = [
            'total' => CallTag::count(),
            'active' => CallTag::where('is_active', true)->count(),
            'inactive' => CallTag::where('is_active', false)->count(),
            'level_1' => CallTag::where('level', 1)->count(),
        ];

        return view('call-tags.index', compact('tags', 'stats'));
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
            'form_fields.*.name' => 'required|string',
            'form_fields.*.label' => 'required|string',
            'form_fields.*.type' => 'required|string',
            'form_fields.*.options' => 'nullable|json',
            'form_fields.*.placeholder' => 'nullable|string|max:255',
            'form_fields.*.validation_rules' => 'nullable|string|max:255',
            'form_fields.*.is_required' => 'boolean',
        ]);

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
            'form_fields.*.name' => 'required|string',
            'form_fields.*.label' => 'required|string',
            'form_fields.*.type' => 'required|string',
            'form_fields.*.options' => 'nullable|json',
            'form_fields.*.placeholder' => 'nullable|string|max:255',
            'form_fields.*.validation_rules' => 'nullable|string|max:255',
            'form_fields.*.is_required' => 'boolean',
        ]);

        try {
            DB::beginTransaction();
            $callTag->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'color' => $validated['color'] ?? null,
                'is_active' => filter_var($validated['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ]);

            if ($callTag->level == 2) {
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
            $callTag->delete();

            return response()->json(['message' => 'Tag deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete tag', 'error' => $e->getMessage()], 500);
        }
    }

    public function bulkAction(Request $request)
    {
        $data = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:call_tags,id',
        ]);

        $ids = $data['ids'];
        $action = $data['action'];

        if ($action === 'delete') {
            CallTag::whereIn('id', $ids)->get()->each->delete();
        } elseif ($action === 'activate') {
            CallTag::whereIn('id', $ids)->get()->each->update(['is_active' => true]);
        } elseif ($action === 'deactivate') {
            CallTag::whereIn('id', $ids)->get()->each->update(['is_active' => false]);
        }

        return response()->json(['message' => 'Bulk action completed successfully.']);
    }
}
