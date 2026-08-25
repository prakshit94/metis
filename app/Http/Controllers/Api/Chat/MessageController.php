<?php

namespace App\Http\Controllers\Api\Chat;

use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Modules\Core\Controllers\Controller;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    public function index(Request $request, Conversation $conversation, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        return response()->json([
            'data' => $chat->messages($request->user(), $conversation, (int) $request->integer('per_page', 30)),
        ]);
    }

    public function store(Request $request, Conversation $conversation, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        $data = $request->validate([
            'type' => ['nullable', 'in:text,image,video,file,document,voice,link,emoji'],
            'content' => ['nullable', 'string', 'max:10000'],
            'parent_id' => ['nullable', 'integer', Rule::exists('chat_messages', 'id')->where('conversation_id', $conversation->id)],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:'.config('chat.uploads.max_size_kb'), 'mimes:'.implode(',', config('chat.uploads.allowed_mimes'))],
            'client_id' => ['nullable', 'string', 'max:120'],
        ]);

        $uploadedAttachments = [];
        if ($request->hasFile('files')) {
            $disk = config('chat.uploads.disk', 'public');

            foreach ($request->file('files') as $file) {
                $path = $file->store('chat/attachments/'.now()->format('Y/m'), $disk);
                $mime = $file->getMimeType() ?: $file->getClientMimeType();

                $uploadedAttachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => Storage::disk($disk)->url($path),
                    'storage_path' => $path,
                    'disk' => $disk,
                    'mime' => $mime,
                    'size' => $file->getSize(),
                ];
            }
        }

        $data['attachments'] = array_values(array_filter($uploadedAttachments));
        if (! empty($uploadedAttachments) && empty($data['type'])) {
            $data['type'] = collect($uploadedAttachments)->every(fn ($attachment) => str_starts_with((string) $attachment['mime'], 'image/'))
                ? 'image'
                : 'file';
        }

        abort_if(blank($data['content'] ?? null) && empty($data['attachments']), 422, 'Message content or attachment is required.');

        return response()->json(['data' => $chat->sendMessage($request->user(), $conversation, $data)], 201);
    }

    public function update(Request $request, Message $message, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        $data = $request->validate(['content' => ['required', 'string', 'max:10000']]);

        return response()->json(['data' => $chat->updateMessage($request->user(), $message, $data['content'])]);
    }

    public function destroy(Request $request, Message $message, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        $chat->deleteMessage($request->user(), $message);

        return response()->json(['message' => 'Message deleted.']);
    }

    public function markRead(Request $request, Conversation $conversation, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        $data = $request->validate([
            'message_id' => ['nullable', 'integer', Rule::exists('chat_messages', 'id')->where('conversation_id', $conversation->id)],
        ]);
        $chat->markRead($request->user(), $conversation, $data['message_id'] ?? null);

        return response()->json(['message' => 'Messages marked read.']);
    }

    public function forward(Request $request, Message $message, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        $chat->ensureMember($message->conversation, $request->user());

        $data = $request->validate(['conversation_ids' => ['required', 'array'], 'conversation_ids.*' => ['integer', 'exists:chat_conversations,id']]);
        $forwarded = [];

        foreach ($data['conversation_ids'] as $conversationId) {
            $conversation = Conversation::findOrFail($conversationId);
            $forwarded[] = $chat->sendMessage($request->user(), $conversation, [
                'type' => $message->type,
                'content' => $message->content,
                'attachments' => $message->attachments,
                'forwarded_from_id' => $message->id,
            ]);
        }

        return response()->json(['data' => $forwarded], 201);
    }
}
