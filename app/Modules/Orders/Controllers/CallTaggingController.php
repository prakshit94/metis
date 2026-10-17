<?php

namespace App\Modules\Orders\Controllers;

use App\Models\CallLog;
use App\Models\CallLogMeta;
use App\Models\CallTag;
use App\Modules\Core\Controllers\Controller;
use App\Services\CallTagFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CallTaggingController extends Controller
{
    private function activeTag(int $id): CallTag
    {
        $tag = CallTag::where('is_active', true)->findOrFail($id);
        $parent = $tag;
        for ($level = (int) $tag->level; $level > 1; $level--) {
            $parent = CallTag::where('is_active', true)->where('level', $level - 1)->findOrFail($parent->parent_id);
        }

        return $tag;
    }

    public function getTags(Request $request)
    {
        $request->validate(['parent_id' => 'nullable|integer', 'level' => 'nullable|integer|in:1,2,3']);
        $parentId = $request->query('parent_id');
        if ($parentId) {
            $this->activeTag((int) $parentId);
        }
        $level = $request->query('level', 1);

        $query = CallTag::where('is_active', true)->orderBy('sort_order')->orderBy('name');

        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->where('level', $level);
        }

        return response()->json($query->get());
    }

    public function getFormFields($tagId)
    {
        $tag = $this->activeTag((int) $tagId);
        abort_unless((int) $tag->level === 2, 404);
        $fields = CallTagFields::forTag($tag->id);

        return response()->json($fields);
    }

    public function storeCallLog(Request $request)
    {
        $validated = $request->validate([
            'call_sid' => 'nullable|string|max:255',
            'customer_id' => 'nullable|integer|exists:parties,id',
            'tag_l1_id' => 'required|exists:call_tags,id',
            'tag_l2_id' => 'required|exists:call_tags,id',
            'tag_l3_id' => 'nullable|exists:call_tags,id',
            'duration_seconds' => 'nullable|integer|min:0',
            'direction' => 'nullable|string|in:inbound,outbound',
            'status' => 'nullable|string|max:30',
            'recording_url' => 'nullable|url|max:255',
            'notes' => 'nullable|string',
            'meta' => 'nullable|array',
        ]);

        abort_unless($request->user(), 401);
        $l1 = $this->activeTag((int) $validated['tag_l1_id']);
        $l2 = $this->activeTag((int) $validated['tag_l2_id']);
        $errors = [];
        if ((int) $l1->level !== 1 || (int) $l2->level !== 2 || (int) $l2->parent_id !== (int) $l1->id) {
            $errors['tag_l2_id'] = 'Select a subject belonging to the selected primary category.';
        }
        if (! empty($validated['tag_l3_id'])) {
            $l3 = $this->activeTag((int) $validated['tag_l3_id']);
            if ((int) $l3->level !== 3 || (int) $l3->parent_id !== (int) $l2->id) {
                $errors['tag_l3_id'] = 'Select an outcome belonging to the selected subject.';
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
        $meta = $validated['meta'] ?? [];
        $fields = CallTagFields::forTag($l2->id);
        foreach ($fields as $field) {
            $value = $meta[$field->name] ?? null;
            $rules = $field->validation_rules ? explode('|', $field->validation_rules) : [];
            $multiple = in_array($field->type, ['multi_select', 'product_search', 'agent_search'], true);
            // Seeded search fields historically declared string although the widget stores arrays of names.
            if ($multiple) {
                $rules = array_values(array_filter($rules, fn ($rule) => $rule !== 'string'));
            }
            $rules[] = $multiple ? 'array' : ($field->type === 'number' ? 'numeric' : 'string');
            $rules[] = $field->is_required ? 'required' : 'nullable';
            if (in_array($field->type, ['date', 'datetime-local'])) {
                $rules[] = 'date';
            }
            $validator = Validator::make(['value' => $value], ['value' => $rules], [], ['value' => $field->label]);
            if ($validator->fails()) {
                $errors['meta.'.$field->name] = $validator->errors()->first();
            }
            if ($multiple && is_array($value) && count(array_filter($value, fn ($v) => ! is_string($v))) > 0) {
                $errors['meta.'.$field->name] = $field->label.' must contain text values.';
            }
            if (! isset($errors['meta.'.$field->name]) && in_array($field->type, ['select', 'multi_select']) && $value !== null && $value !== '' && $value !== []) {
                $options = is_array($field->options) ? $field->options : json_decode($field->options ?? '[]', true);
                if (! is_array($options) || array_diff($multiple && is_array($value) ? $value : [$value], $options)) {
                    $errors['meta.'.$field->name] = 'Choose a valid option for '.$field->label.'.';
                }
            }
        }
        if (array_diff(array_keys($meta), $fields->pluck('name')->all())) {
            $errors['meta'] = 'Call details changed. Reload the selected subject and try again.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        try {
            DB::beginTransaction();

            $callLog = CallLog::create([
                'call_sid' => $validated['call_sid'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'agent_id' => $request->user()->id,
                'tag_l1_id' => $validated['tag_l1_id'],
                'tag_l2_id' => $validated['tag_l2_id'],
                'tag_l3_id' => $validated['tag_l3_id'] ?? null,
                'duration_seconds' => $validated['duration_seconds'] ?? 0,
                'direction' => $validated['direction'] ?? 'inbound',
                'status' => $validated['status'] ?? 'completed',
                'recording_url' => $validated['recording_url'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (! empty($validated['meta'])) {
                foreach ($validated['meta'] as $key => $value) {
                    // Convert arrays to JSON strings for storage if needed
                    $val = is_array($value) ? json_encode($value) : $value;
                    CallLogMeta::create([
                        'call_log_id' => $callLog->id,
                        'key' => $key,
                        'value' => $val,
                    ]);
                }
            }

            $callLog->load(['agent', 'tagL1', 'tagL2', 'tagL3', 'metas']);
            DB::commit();

            return response()->json(['message' => 'Call log saved successfully', 'call_log' => $callLog]);
        } catch (\Exception $e) {
            DB::rollBack();

            report($e);

            return response()->json(['message' => 'Failed to save call log. Your profile remains open; please retry.'], 500);
        }
    }
}
