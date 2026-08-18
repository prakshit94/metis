<?php

namespace App\Http\Controllers\Api\Chat;

use App\Modules\Core\Controllers\Controller;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'include_self' => ['nullable', 'boolean'],
        ]);

        return response()->json([
            'data' => $chat->listUsersWithPresence(
                $request->user(),
                $data['q'] ?? null,
                (bool) ($data['include_self'] ?? false)
            ),
        ]);
    }
}
