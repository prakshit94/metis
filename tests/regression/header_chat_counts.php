<?php

require __DIR__.'/bootstrap.php';

use App\Http\Controllers\Api\Chat\ConversationController;
use App\Modules\Users\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

Schema::create('chat_conversations', function (Blueprint $t) {
    $t->id();
    $t->softDeletes();
});
Schema::create('chat_members', function (Blueprint $t) {
    $t->id();
    $t->unsignedBigInteger('conversation_id');
    $t->unsignedBigInteger('user_id');
    $t->string('status');
    $t->timestamp('left_at')->nullable();
});
Schema::create('chat_messages', function (Blueprint $t) {
    $t->id();
    $t->unsignedBigInteger('conversation_id');
    $t->unsignedBigInteger('sender_id');
    $t->softDeletes();
});
Schema::create('chat_message_reads', function (Blueprint $t) {
    $t->id();
    $t->unsignedBigInteger('message_id');
    $t->unsignedBigInteger('user_id');
});
for ($i = 1; $i <= 7; $i++) {
    DB::table('chat_conversations')->insert(['id' => $i, 'deleted_at' => $i === 3 ? now() : null]);
}
for ($i = 1; $i <= 7; $i++) {
    DB::table('chat_members')->insert(['conversation_id' => $i, 'user_id' => $i === 7 ? 9 : 1, 'status' => $i === 4 ? 'left' : ($i === 5 ? 'inactive' : 'active'), 'left_at' => in_array($i, [4, 6]) ? now() : null]);
}
$messages = [[1, 1, 2], [2, 1, 2], [3, 2, 3], [4, 1, 1], [5, 1, 2], [6, 3, 2], [7, 4, 2], [8, 5, 2], [9, 6, 2], [10, 1, 2], [11, 7, 2]];
foreach ($messages as [$id,$conversation,$sender]) {
    DB::table('chat_messages')->insert(['id' => $id, 'conversation_id' => $conversation, 'sender_id' => $sender, 'deleted_at' => $id === 10 ? now() : null]);
}
DB::table('chat_message_reads')->insert(['message_id' => 5, 'user_id' => 1]);
$user = new User;
$user->forceFill(['id' => 1]);
$chat = new ChatService;
DB::enableQueryLog();
$counts = $chat->unreadCounts($user);
$queries = DB::getQueryLog();
DB::disableQueryLog();
check($counts === ['unread' => 2, 'unread_messages' => 3], 'legacy conversation count and new message count are both correct');
check(count($queries) === 1, 'both unread totals use one aggregate query');
check($counts['unread_messages'] === 3, 'own messages, read messages, deleted chats/messages and departed memberships are excluded');
$other = new User;
$other->forceFill(['id' => 9]);
check($chat->unreadCounts($other) === ['unread' => 1, 'unread_messages' => 1], 'another user gets only their own visible chat count');
$empty = new User;
$empty->forceFill(['id' => 50]);
check($chat->unreadCounts($empty) === ['unread' => 0, 'unread_messages' => 0], 'users with no memberships get zero counts');
$request = Request::create('/api/chat/unread-count', 'GET');
$request->setUserResolver(fn () => $user);
config(['chat.enabled' => true]);
check((new ConversationController)->unreadCount($request)->getData(true) === $counts, 'unread API preserves the original field and adds the message count');
check(DB::table('chat_message_reads')->count() === 1, 'fetching unread count never marks messages as read');
config(['chat.enabled' => false]);
$rejected = false;
try {
    (new ConversationController)->unreadCount($request);
} catch (HttpExceptionInterface $error) {
    $rejected = $error->getStatusCode() === 404;
}
check($rejected,'disabled chat still blocks the unread endpoint');
echo "Header chat counter checks complete.\n";
