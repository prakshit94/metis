<?php

namespace App\Http\Controllers\Api\Chat;

use App\Modules\Core\Controllers\Controller;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'type' => ['nullable', 'in:text,image,video,file,document,voice,link,emoji'],
            'conversation_id' => ['nullable', 'integer', 'exists:chat_conversations,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        return response()->json(['data' => $chat->search($request->user(), $filters)]);
    }
}
