<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Models\CallTag;
use App\Models\CallTagFormField;
use App\Models\CallLog;
use App\Models\CallLogMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallTaggingController extends Controller
{
    public function getTags(Request $request)
    {
        $parentId = $request->query('parent_id');
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
        $fields = CallTagFormField::where('call_tag_id', $tagId)->orderBy('sort_order')->get();
        
        foreach ($fields as $field) {
            if (strtolower($field->name) === 'crop') {
                $categories = \App\Modules\Catalog\Models\Category::where('is_active', true)->pluck('name')->toArray();
                $field->options = json_encode($categories);
                $field->type = 'multi_select';
            }
        }

        return response()->json($fields);
    }

    public function storeCallLog(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|integer',
            'tag_l1_id' => 'required|exists:call_tags,id',
            'tag_l2_id' => 'required|exists:call_tags,id',
            'tag_l3_id' => 'nullable|exists:call_tags,id',
            'notes' => 'nullable|string',
            'meta' => 'nullable|array'
        ]);

        try {
            DB::beginTransaction();

            $callLog = CallLog::create([
                'customer_id' => $validated['customer_id'] ?? null,
                'agent_id' => auth()->id() ?? 1, // Fallback if no auth
                'tag_l1_id' => $validated['tag_l1_id'],
                'tag_l2_id' => $validated['tag_l2_id'],
                'tag_l3_id' => $validated['tag_l3_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (!empty($validated['meta'])) {
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

            DB::commit();

            $callLog->load(['agent', 'tagL1', 'tagL2', 'tagL3', 'metas']);

            return response()->json(['message' => 'Call log saved successfully', 'call_log' => $callLog]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to save call log', 'details' => $e->getMessage()], 500);
        }
    }
}
