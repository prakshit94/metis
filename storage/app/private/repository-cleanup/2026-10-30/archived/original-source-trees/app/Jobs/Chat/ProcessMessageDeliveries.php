<?php

namespace App\Jobs\Chat;

use App\Models\Chat\ChatNotification;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\Chat\MessageDelivery;
use App\Models\Chat\MessageRead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessMessageDeliveries implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $messageId;

    public $conversationId;

    public $senderId;

    public $senderName;

    public $preview;

    public function __construct(int $messageId, int $conversationId, int $senderId, string $senderName, string $preview)
    {
        $this->messageId = $messageId;
        $this->conversationId = $conversationId;
        $this->senderId = $senderId;
        $this->senderName = $senderName;
        $this->preview = $preview;
    }

    public function handle()
    {
        DB::transaction(function () {
            $message = Message::where('conversation_id', $this->conversationId)->lockForUpdate()->find($this->messageId);
            if (! $message) {
                return;
            }
            $conversation = Conversation::find($this->conversationId);
            if (! $conversation) {
                return;
            }

            $userIds = $conversation->activeMembers()->where('user_id', '!=', $this->senderId)->pluck('user_id');

            $existing = ChatNotification::where('message_id', $this->messageId)->where('type', 'new_message')->pluck('user_id')->all();
            $reads = MessageRead::where('message_id', $this->messageId)->pluck('read_at', 'user_id');
            $now = now();
            $deliveries = [];
            $notifications = [];

            $notificationPayload = json_encode([
                'sender_id' => $this->senderId,
                'sender_name' => $this->senderName,
                'preview' => $this->preview,
            ]);

            foreach ($userIds as $userId) {
                $deliveries[] = [
                    'message_id' => $this->messageId,
                    'user_id' => $userId,
                    'delivered_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (in_array($userId, $existing)) {
                    continue;
                }
                $notifications[] = [
                    'read_at' => $reads->get($userId),
                    'user_id' => $userId,
                    'conversation_id' => $this->conversationId,
                    'message_id' => $this->messageId,
                    'type' => 'new_message',
                    'payload' => $notificationPayload,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($deliveries, 1000) as $chunk) {
                MessageDelivery::insertOrIgnore($chunk);
            }

            foreach (array_chunk($notifications, 1000) as $chunk) {
                ChatNotification::insert($chunk);
            }
        }, 3);
    }
}
