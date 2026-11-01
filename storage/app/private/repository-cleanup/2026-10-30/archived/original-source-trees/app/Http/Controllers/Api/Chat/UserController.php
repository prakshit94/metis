<?php

namespace App\Http\Controllers\Api\Chat;

use App\Modules\Core\Controllers\Controller;
use App\Services\Chat\ChatService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:chat-view'),
            new Middleware('permission:chat-export', only: ['export']),
        ];
    }

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

    public function export(Request $request, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);
        abort_unless($request->user()?->can('chat-export'), 403);

        $users = $chat->listUsersWithPresence($request->user(), includeSelf: true, loggedInToday: true);
        return response()->streamDownload(function () use ($users) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Name', 'Email', 'Location', 'Status', 'Today Orders', 'Today Revenue', 'Last Seen']);
            $text = static fn ($value) => preg_match('/^[=+@\-\t\r]/', (string) $value) ? "'".$value : (string) $value;
            foreach ($users as $user) {
                fputcsv($file, [
                    $text($user['name'] ?? ''), $text($user['email'] ?? ''), $text($user['location'] ?? ''),
                    $user['is_online'] ? 'Online' : 'Offline', $user['today_orders'] ?? 0,
                    $user['today_revenue'] ?? 0, $text($user['last_seen_label'] ?? ''),
                ]);
            }
            fclose($file);
        }, 'users_logged_in_today_'.today()->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
