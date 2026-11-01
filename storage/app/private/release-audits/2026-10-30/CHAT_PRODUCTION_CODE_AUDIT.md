# Chat code audit — 30 October 2026

## Scope and deployment status

Audited the local code corresponding to `/chat`: workspace Blade/JavaScript, web controller, chat API controllers, ChatService, delivery job, models, routes, permissions and eight chat table migrations. The supplied production URL could not be inspected through the remote fetch tool. No authenticated production session, deployment, production database mutation or real message send was performed. These are local changes ready for review/deployment; live production behavior still needs deployment verification.

## Confirmed fixes

| Area | Issue | Change |
| --- | --- | --- |
| Direct chat | Simultaneous reversed requests could create duplicate conversations; inactive recipients were accepted at service level | Lock participating active users in stable ID order; reuse the active pair |
| Response privacy | Group/direct member responses loaded entire personnel records | Select only the profile fields consumed by chat |
| Permissions | Search/previews exposed message content without messages-view | Mask previews and suppress unauthorized message-search queries; harden the alternate history handler |
| Send retries | A lost response followed by retry created another message | Per-draft client IDs, conversation-serialized lookup, no resurrection of deleted retry targets |
| Uploads | Failed sends and duplicate retries left orphan files | Clean only new unused uploads, keep the original accepted attachment |
| Forwarding | An inaccessible later destination left earlier copies committed | Validate all visible destinations first; transactional forwarding in stable target order |
| Reads | Repeated reads rewrote first-read timestamps and cursors moved backward | Insert only missing receipts in batches; monotonic cursor with no unnecessary member updates |
| Notifications | Job retries duplicated notifications; a late job marked already-read content unread | Message-locked transactional job, notification deduplication and existing-receipt read state |
| Queue boundary | Delivery dispatch was registered before the surrounding transaction committed | Register delivery after commit; deleted messages/conversations are ignored by delivery |
| Group integrity | Self-transfer could leave a group without an owner; departed members could be promoted; concurrent updates used stale authority | Reject self-transfer; validate active targets and serialize membership/ownership mutations |
| Group/audit failures | Settings, deletion and member batches could partially persist when audit writes failed | Transactions include the mutations and audit writes |
| Deleted chats | Message mutations could dereference a missing soft-deleted conversation | Return not-found consistently |
| Presence | Arbitrary metadata and private/expired typing-conversation IDs were exposed | Restrict output and filter typing context against visible conversations and expiry |
| Feature switch | Unread endpoint ignored chat.enabled | Apply the existing feature switch consistently |
| History | Latest polling replaced loaded history; offsets shifted as messages arrived | Stable ID cursors, deduplicated merges and earlier-history control |
| UI races | Late pin/archive/group mutation responses could affect another selected conversation | Bind mutations to their original conversation and guard delayed responses |
| Role UI | Group controls used message-edit permission; nonowners saw owner-only deletion; moderators saw promotion actions | Match existing backend group permissions/roles; retain existing role hierarchy policy |
| Mobile | People directory was hidden below the desktop breakpoint | Reuse it through a narrow-screen People/Back control |
| Transport | Chat relied on an external unpinned Axios CDN and manually specified multipart content type | Same-origin built fetch adapter with CSRF/session credentials, browser multipart boundaries and compatible response shape |
| Browser/session failures | Blocked storage crashed chat; expired sessions and throttling kept polling | Safe storage wrappers, stopped-session guard and bounded Retry-After backoff |

## Performance changes

- One directory request/query set refreshes colleagues and the current user, replacing two directory reads per page/poll. Self is always included without reducing the existing colleague cap. Logged-in-today export behavior remains intact.
- Directory session lookup joins each user's maximum activity timestamp rather than loading every historic session row into PHP. Ties retain the previous single-session selection behavior.
- Hidden/destroyed tabs stop polling; read-only UI does not offer a send action. Idle polls no longer repeatedly write every receipt.
- Message catch-up work is bounded to three delta pages per poll. For a larger backlog, the workspace falls back to the latest page and retains access to older history through the cursor control, avoiding an unbounded catch-up loop.
- Added a reversible nonunique `(conversation_id, deleted_at, id)` index for the conversation/soft-delete filter and stable history cursor order. Existing equivalent indexes are respected. No columns, record values, unique constraints or foreign keys were changed.
- Large production-volume load benchmarks were not performed; performance conclusions are based on query/data-flow inspection and focused regression checks.

## Preserved behavior and limits

No order/customer/stock logic, state or office visibility, pricing, directory revenue calculations, authentication, role grants or existing chat group hierarchy policy was changed. REST response envelopes and existing parameters remain compatible; cursors are optional. Existing files/messages/groups were not merged, renumbered or deleted during validation. The floating widget implementation was not replaced.

The configured attachment disk policy remains unchanged. With the default public disk, an existing public attachment URL remains accessible to anyone possessing that URL; API membership checks do not secure public storage URLs. Moving existing uploads behind authenticated downloads requires a separate storage-policy/client compatibility change and was not silently introduced here. Historical duplicates already present in a production database were not cleaned up without inspecting those records.

## Validation

- `METIS_TEST_MYSQL_SOCKET=/path/to/isolated/mysql.sock php tests/regression/chat_integrity.php`: **40 checks passed**, including real parallel workers, transactional failure paths and index forward/backward checks. Uses a random disposable schema, synthetic users/messages, fake upload storage and fake delivery dispatch.
- `node tests/regression/chat_frontend.mjs`: **18 checks passed**, including draft/nonce preservation, hidden polling, stale responses, bounded cursor history, original-chat pin updates, permission UI, throttle backoff, CSRF, multipart and invalid responses.
- Route contracts: 622 handlers; permission contracts: 425 names and 591 gates; response contracts: 1,340 methods. All passed.
- PHP/JavaScript syntax, Blade compilation, targeted formatting, `git diff --check` and Vite production build passed.
- No real messages were sent, and no production records or external delivery systems were modified by these tests.

## Deployment

Deploy the reviewed chat code and built assets through the normal release process. Back up production first. Apply only the new chat index migration if other migrations are pending and not part of the release:

```sh
php artisan migrate --path=database/migrations/2026_10_30_020000_index_chat_history_cursor.php --force
php artisan view:clear
```

Index creation can take time on a large message table; schedule it within the normal deployment window. It does not remove message records. Refresh the browser after deployment so the new built transport and permission flags load together. Verify two authorized users can create/reuse a direct chat, send/reply/edit/upload, read new messages, and perform their existing group actions. Existing role grants must still provide the actions being tested; no permission repair is automatically run by this module change.
