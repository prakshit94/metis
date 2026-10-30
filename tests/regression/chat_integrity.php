<?php

require __DIR__.'/bootstrap.php';
use App\Http\Controllers\Api\Chat\ConversationController;
use App\Http\Controllers\Api\Chat\GroupController;
use App\Http\Controllers\Api\Chat\MessageController;
use App\Http\Controllers\Api\Chat\PresenceController;
use App\Jobs\Chat\ProcessMessageDeliveries;
use App\Models\Chat\ChatNotification;
use App\Models\Chat\Conversation;
use App\Models\Chat\Member;
use App\Models\Chat\Message;
use App\Models\Chat\Presence;
use App\Modules\Users\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

DB::statement('SET FOREIGN_KEY_CHECKS=0');
foreach (json_decode(file_get_contents(database_path('transitions/metis-20261029/audited-target-schema.json')), true) as $ddl) {
    DB::unprepared($ddl);
}DB::statement('SET FOREIGN_KEY_CHECKS=1');
Bus::fake();
Gate::before(fn ($user) => $user->email === 'restricted@chat.test' ? false : true);
function chatUser(string $name): User
{
    return User::create(['name' => $name, 'email' => $name.'@chat.test', 'password' => 'unused-test-password', 'is_active' => true]);
}
function chatRequest(User $user, array $data = []): Request
{
    $r = Request::create('/api/chat/test', 'POST', $data);
    $r->setUserResolver(fn () => $user);

    return $r;
}
function deniedChat(Closure $fn, int $status, string $label): void
{
    try {
        $fn();
    } catch (ModelNotFoundException) {
        check($status === 404, $label);

        return;
    } catch (HttpExceptionInterface $e) {
        check($e->getStatusCode() === $status, $label);

        return;
    }throw new RuntimeException('Expected rejection: '.$label);
}
$a = chatUser('owner');
$b = chatUser('recipient');
$c = chatUser('third');
$outside = chatUser('outsider');
$restricted = chatUser('restricted');
$chat = new ChatService;
$api = new MessageController;
$groups = new GroupController;
$direct = $chat->createDirectConversation($a, $b->id);
$again = $chat->createDirectConversation($b, $a->id);
check($direct->id === $again->id && Conversation::where('type', 'direct')->count() === 1, 'direct chat creation reuses the existing active pair');
invalid(fn () => $chat->createDirectConversation($a, $a->id), 'direct chat rejects self');
$outside->is_active = false;
$outside->save();
invalid(fn () => $chat->createDirectConversation($a, $outside->id), 'direct chat rejects inactive recipients');
$outside->is_active = true;
$outside->save();
check(! array_key_exists('phone', $direct->toArray()['active_members'][0]['user']), 'member responses exclude unrelated personnel fields');
$m = $chat->sendMessage($a, $direct, ['content' => 'hello', 'client_id' => 'retry-1']);
$retry = $chat->sendMessage($a, $direct, ['content' => 'hello', 'client_id' => 'retry-1']);
check($m->id === $retry->id && Message::where('conversation_id', $direct->id)->count() === 1, 'send retries create only one message');
deniedChat(fn () => $chat->messages($outside, $direct), 404, 'non-members cannot read history');
deniedChat(fn () => $chat->updateMessage($b, $m, 'forbidden'), 403, 'only sender can edit');
check($chat->updateMessage($a, $m, 'edited')->content === 'edited', 'sender edits still work');
$job = new ProcessMessageDeliveries($m->id, $direct->id, $a->id, $a->name, 'preview');
$job->handle();
$job->handle();
check(ChatNotification::where('message_id', $m->id)->count() === 1, 'delivery retries do not duplicate notifications');
$chat->markRead($b, $direct, $m->id);
$first = DB::table('chat_message_reads')->where('message_id', $m->id)->where('user_id', $b->id)->value('read_at');
Carbon::setTestNow(now()->addSeconds(10));
$chat->markRead($b, $direct, $m->id);
check(DB::table('chat_message_reads')->where('message_id', $m->id)->where('user_id', $b->id)->value('read_at') === $first, 'repeated reads preserve first receipt timestamp');
check(ChatNotification::where('message_id', $m->id)->whereNull('read_at')->count() === 0, 'reading clears message notifications');
$n = $chat->sendMessage($a, $direct, ['content' => 'next']);
$chat->markRead($b, $direct, $n->id);
$chat->markRead($b, $direct, $m->id);
check(Member::where('conversation_id', $direct->id)->where('user_id', $b->id)->value('last_read_message_id') === $n->id, 'read cursor never moves backward');
$late = new ProcessMessageDeliveries($n->id, $direct->id, $a->id, $a->name, 'next');
$late->handle();
check(ChatNotification::where('message_id', $n->id)->whereNull('read_at')->count() === 0, 'late delivery job does not resurrect unread notifications');
Carbon::setTestNow();
$other = $chat->createDirectConversation($a, $c->id);
$private = $chat->createDirectConversation($b, $c->id);
$before = Message::count();
deniedChat(fn () => $api->forward(chatRequest($a, ['conversation_ids' => [$other->id, $private->id]]), $m, $chat), 404, 'forward checks every destination before sending');
check(Message::count() === $before, 'invalid multi-target forward leaves no partial copies');
$api->forward(chatRequest($a, ['conversation_ids' => [$other->id]]), $m, $chat);
check(Message::where('conversation_id', $other->id)->count() === 1, 'authorized forwarding remains available');
$group = $chat->createGroup($a, ['name' => 'Synthetic group', 'member_ids' => [$b->id, $c->id]]);
deniedChat(fn () => $groups->transferOwner(chatRequest($a, ['user_id' => $a->id]), $group, $chat), 422, 'ownership cannot be transferred to oneself');
$groups->transferOwner(chatRequest($a, ['user_id' => $b->id]), $group, $chat);
check($group->fresh()->owner_id === $b->id && Member::where('conversation_id', $group->id)->where('role', 'owner')->count() === 1, 'ownership transfer keeps exactly one owner');
$chat->removeMember($b, $group, $c->id);
deniedChat(fn () => $chat->updateRole($b, $group, $c->id, 'admin'), 404, 'departed members cannot be promoted');
deniedChat(fn () => $chat->messages($c, $group), 404, 'departed members cannot read history');
$rgroup = $chat->createGroup($a, ['name' => 'Restricted member', 'member_ids' => [$restricted->id]]);
$chat->sendMessage($a, $rgroup, ['content' => 'secret']);
check(! array_key_exists('latest_message', $chat->listConversations($restricted)->firstWhere('id', $rgroup->id)->toArray()), 'conversation previews respect message permission');
check($chat->search($restricted, ['q' => 'secret'])['messages']->isEmpty(), 'search cannot bypass message permission');
Presence::create(['user_id' => $a->id, 'status' => 'online', 'last_seen_at' => now(), 'typing_conversation_id' => $private->id, 'typing_expires_at' => now()->addMinute(), 'metadata' => ['private' => 'hidden']]);
$presence = (new PresenceController)->index(chatRequest($outside))->getData(true)['data'][0];
check(! array_key_exists('metadata', $presence) && $presence['typing_conversation_id'] === null, 'presence hides metadata and inaccessible typing context');
for ($i = 0; $i < 35; $i++) {
    $chat->sendMessage($a, $direct, ['content' => 'history '.$i]);
}$page = $chat->messages($b, $direct, 10);
$cursor = $page->last()->id;
$older = $chat->messages($b, $direct, 10, $cursor);
check($older->max('id') < $cursor, 'history cursor loads stable earlier pages');
$delta = $chat->messages($b, $direct, 100, null, $m->id);
$ordered = true;
$previous = null;
foreach ($delta as $entry) {
    if ($previous && ($previous->created_at > $entry->created_at || ($previous->created_at == $entry->created_at && $previous->id > $entry->id))) {
        $ordered = false;
    }
    $previous = $entry;
}
check($delta->contains('id', $n->id) && $ordered, 'new-message cursor returns ascending chronological deltas');
$chat->deleteMessage($a, $m);
deniedChat(fn () => $chat->sendMessage($a, $direct, ['content' => 'hello', 'client_id' => 'retry-1']), 409, 'retrying a deleted message cannot resurrect it');
$doomed = $chat->sendMessage($a, $group, ['content' => 'deleted group']);
$group->delete();
deniedChat(fn () => $chat->updateMessage($a, $doomed, 'bad'), 404, 'deleted conversations return not-found rather than server errors');
(new ProcessMessageDeliveries($doomed->id, $group->id, $a->id, $a->name, 'gone'))->handle();
check(ChatNotification::where('message_id', $doomed->id)->count() === 0, 'deleted conversations produce no new delivery notifications');
$directory = $chat->listUsersWithPresence($a, null, true);
check($directory->contains('id', $a->id) && $directory->contains('id', $b->id), 'optimized directory retains self and colleagues');
Storage::fake('public');
$r = chatRequest($a, ['content' => 'attachment']);
$r->files->set('files', [UploadedFile::fake()->createWithContent('sample.pdf', "%PDF-1.4\nsynthetic")]);
$before = Message::count();
$fail = new class extends ChatService
{
    public function audit(?User $actor, string $action, ?Conversation $conversation = null, ?Message $message = null, ?array $before = null, ?array $after = null, ?Request $request = null): void
    {
        throw new RuntimeException('forced rollback');
    }
};
try {
    $api->store($r, $direct, $fail);
    throw new LogicException('No failure');
} catch (RuntimeException $error) {
    check($error->getMessage() === 'forced rollback', 'failed-send rollback path exercised');
}
check(Message::count() === $before && Storage::disk('public')->allFiles() === [], 'failed send rolls back message and removes uploaded files');
config(['chat.enabled' => false]);
deniedChat(fn () => (new ConversationController)->unreadCount(chatRequest($a)), 404, 'disabled chat also disables unread endpoint');
config(['chat.enabled' => true]);
$atomic = $chat->createGroup($a, ['name' => 'Atomic group', 'member_ids' => [$b->id]]);
try {
    $groups->update(chatRequest($a, ['name' => 'Should rollback']), $atomic, $fail);
} catch (RuntimeException) {
}
check($atomic->fresh()->name === 'Atomic group', 'failed group audit rolls back settings changes');
try {
    $groups->destroy(chatRequest($a), $atomic, $fail);
} catch (RuntimeException) {
}
check(Conversation::find($atomic->id) !== null, 'failed group delete audit preserves the group');
try {
    $groups->addMember(chatRequest($a, ['user_ids' => [$c->id, $outside->id]]), $atomic, $fail);
} catch (RuntimeException) {
}
check(! $atomic->activeMembers()->whereIn('user_id', [$c->id, $outside->id])->exists(), 'failed batch member audit leaves no partial memberships');
$index = require database_path('migrations/2026_10_30_020000_index_chat_history_cursor.php');
$index->up();
$index->up();
check(Schema::hasIndex('chat_messages', ['conversation_id', 'deleted_at', 'created_at', 'id']), 'history cursor index migration is additive and repeatable');
$index->down();
check(! Schema::hasIndex('chat_messages', 'chat_messages_history_cursor_index'), 'history index rollback removes only its named index');
$index->up();
$upload = fn () => UploadedFile::fake()->createWithContent('retry.pdf', "%PDF-1.4\nsynthetic");
$r1 = chatRequest($a, ['content' => 'pdf retry', 'client_id' => 'upload-once']);
$r1->files->set('files', [$upload()]);
$one = $api->store($r1, $direct, $chat)->getData(true)['data'];
$r2 = chatRequest($a, ['content' => 'pdf retry', 'client_id' => 'upload-once']);
$r2->files->set('files', [$upload()]);
$two = $api->store($r2, $direct, $chat)->getData(true)['data'];
check($one['id'] === $two['id'] && count(Storage::disk('public')->allFiles()) === 1, 'upload retry preserves original file and deletes only the duplicate upload');
Storage::disk('public')->delete(Storage::disk('public')->allFiles());

// Preserve the committed timestamp ordering, including legacy nullable timestamps.
$latestBeforeBackdate = $chat->messages($b, $direct, 100)->first()->id;
$backdated = $chat->sendMessage($a, $direct, ['content' => 'Imported older message']);
DB::table('chat_messages')->where('id', $backdated->id)->update(['created_at' => now()->subDays(10)]);
check($chat->messages($b, $direct, 100)->first()->id === $latestBeforeBackdate, 'backdated high-ID messages do not replace newer chronological history');
check(! $chat->messages($b, $direct, 100, null, $n->id)->contains('id', $backdated->id), 'after cursor uses chronological order rather than ID alone');
DB::table('chat_messages')->where('id', $backdated->id)->update(['created_at' => null]);
check($chat->messages($b, $direct, 100, $backdated->id)->isEmpty(), 'before a null-date oldest cursor stays empty');
check(! $chat->messages($b, $direct, 100, null, $backdated->id)->isEmpty(), 'after a null-date cursor includes dated history');
$parallelChat = function (string $mode, array $payloads) use ($testDatabase): array {
    $barrier = sys_get_temp_dir().'/metis-chat-'.bin2hex(random_bytes(8));
    $workers = [];
    try {
        foreach ($payloads as $data) {
            $data['barrier'] = $barrier;
            $process = proc_open([PHP_BINARY, __DIR__.'/chat_worker.php', $mode, json_encode($data)], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, array_merge(getenv(), ['METIS_EXISTING_TEST_DB' => $testDatabase]));
            fclose($pipes[0]);
            $workers[] = [$process, $pipes];
        }
        $deadline = microtime(true) + 10;
        while (count(glob($barrier.'.ready.*')) < count($workers) && microtime(true) < $deadline) {
            usleep(10000);
        }file_put_contents($barrier.'.go', 'go');
        $result = [];
        foreach ($workers as [$process,$pipes]) {
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            if (proc_close($process) !== 0) {
                throw new RuntimeException('Chat worker failed: '.$error);
            }$result[] = json_decode($output, true, 512, JSON_THROW_ON_ERROR)['id'];
        }

        return $result;
    } finally {
        foreach (glob($barrier.'.*') as $file) {
            unlink($file);
        }
    }
};
$pair = $parallelChat('direct', [['user' => $outside->id, 'recipient' => $restricted->id], ['user' => $restricted->id, 'recipient' => $outside->id]]);
check(count(array_unique($pair)) === 1, 'simultaneous reversed direct-chat requests create one conversation');
$before = Message::count();
$sent = $parallelChat('send', array_fill(0, 2, ['user' => $a->id, 'conversation' => $direct->id]));
check(count(array_unique($sent)) === 1 && Message::count() === $before + 1, 'simultaneous identical send retries create one message');
echo "Chat integrity checks complete.\n";
