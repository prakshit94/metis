<?php

require __DIR__.'/bootstrap.php';

use App\Models\Chat\Conversation;
use App\Modules\Users\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Support\Facades\Bus;

Bus::fake();
$data = json_decode($argv[2], true, 512, JSON_THROW_ON_ERROR);
file_put_contents($data['barrier'].'.ready.'.getmypid(), 'ready');
$deadline = microtime(true) + 10;
while (! is_file($data['barrier'].'.go') && microtime(true) < $deadline) {
    usleep(10000);
}
if (! is_file($data['barrier'].'.go')) {
    throw new RuntimeException('Concurrent chat start timed out');
}
$user = User::findOrFail($data['user']);
$chat = new ChatService;
$result = match ($argv[1]) {
    'direct' => $chat->createDirectConversation($user, $data['recipient'])->id,
    'send' => $chat->sendMessage($user, Conversation::findOrFail($data['conversation']), ['content' => 'Concurrent retry', 'client_id' => 'parallel-retry'])->id,
};
echo json_encode(['id' => $result]);
