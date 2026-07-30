@extends('layouts.app')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
    <script>
        // Ensure Axios sends XMLHttpRequest header for Laravel
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        // Add CSRF token
        document.addEventListener('DOMContentLoaded', () => {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (token) axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        });
    </script>
@endpush

@section('title', 'Chat')

@section('content')
    <div
        class="bg-body d-flex flex-column"
        style="height: calc(100vh - 80px); min-height: 720px;"
        x-data="chatWorkspace({
            conversations: @js($conversations),
            users: @js($users),
            pollInterval: @js($pollInterval),
        })"
        x-init="init()"
    >
        <div class="row g-0 h-100">
            <!-- Left Sidebar: Conversations -->
            <aside class="col-12 col-lg-3 flex-column border-end bg-body-tertiary h-100"
                   :class="activeConversation ? 'd-none d-lg-flex' : 'd-flex'">
                <div class="p-3 border-bottom">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="mb-0 fw-bold">Messages</h5>
                            <small class="text-muted" x-text="onlineLabel"></small>
                        </div>
                        @can('chat-create')
                        <button type="button" @click="showGroupModal = true; groupError = ''" class="btn btn-primary btn-sm d-flex align-items-center gap-1" title="Create group">
                            <i class="bi bi-people-fill"></i>
                            <span class="d-none d-xl-inline">New Group</span>
                        </button>
                        @endcan
                    </div>
                    <div class="position-relative">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" style="pointer-events:none;"></i>
                        <input type="search" x-model.debounce.250ms="search" placeholder="Search chats or people..." class="form-control ps-5 rounded-pill">
                    </div>
                </div>

                <div class="px-3 py-2 border-bottom">
                    <div class="btn-group w-100" role="group">
                        <button type="button" @click="filter = 'all'" :class="filter === 'all' ? 'btn-primary' : 'btn-outline-secondary'" class="btn btn-sm fw-semibold">All</button>
                        <button type="button" @click="filter = 'unread'" :class="filter === 'unread' ? 'btn-primary' : 'btn-outline-secondary'" class="btn btn-sm fw-semibold">Unread</button>
                        <button type="button" @click="filter = 'pinned'" :class="filter === 'pinned' ? 'btn-primary' : 'btn-outline-secondary'" class="btn btn-sm fw-semibold">Pinned</button>
                    </div>
                </div>

                <div class="flex-grow-1 overflow-y-auto">
                    <!-- Search Results -->
                    <template x-if="searchResults.users?.length">
                        <div class="p-2 border-bottom">
                            <small class="text-muted fw-bold text-uppercase px-2">People</small>
                            <template x-for="user in searchResults.users" :key="user.id">
                                <button type="button" @click="startDirect(user.id)" class="btn btn-light text-start w-100 d-flex align-items-center gap-2 mb-1 p-2 border-0">
                                    <div class="position-relative flex-shrink-0">
                                        <template x-if="user.photo">
                                            <img :src="formatAttachmentUrl(user.photo)" class="rounded-circle object-fit-cover shadow-sm" style="width: 36px; height: 36px;">
                                        </template>
                                        <template x-if="!user.photo">
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 36px; height: 36px; font-size: 12px;" x-text="initials(user.name)"></div>
                                        </template>
                                        <span class="position-absolute bottom-0 end-0 rounded-circle border border-white" style="width: 10px; height: 10px;" :class="user.is_online ? 'bg-success' : 'bg-secondary'"></span>
                                    </div>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="d-flex align-items-center mb-1 gap-1">
                                            <span class="text-truncate fw-bold small mb-0" x-text="user.name"></span>
                                        </div>
                                        <div class="text-truncate text-muted mb-1" style="font-size: 10px;">
                                            <template x-if="user.location">
                                                <span><i class="bi bi-geo-alt me-1 opacity-75"></i><span x-text="user.location"></span></span>
                                            </template>
                                        </div>
                                        <div class="text-truncate text-muted small" style="font-size: 11px;" x-text="user.last_seen_label || user.email"></div>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </template>

                    <!-- Empty state -->
                    <div x-show="filteredConversations.length === 0 && !search" class="text-center p-5 text-muted">
                        <i class="bi bi-chat-dots fs-1 d-block mb-2 opacity-50"></i>
                        <p class="small mb-0">No conversations yet</p>
                        <p class="small">Start a chat from the users panel</p>
                    </div>

                    <!-- Conversation List -->
                    <template x-for="conversation in filteredConversations" :key="conversation.id">
                        <button type="button" @click="selectConversation(conversation)"
                            class="btn text-start w-100 d-flex align-items-start gap-3 px-3 py-3 border-0 rounded-0"
                            style="border-bottom: 1px solid var(--bs-border-color) !important; transition: background 0.15s;"
                            :class="activeConversation?.id === conversation.id ? 'bg-primary bg-opacity-10' : 'bg-transparent'">
                            <div class="position-relative flex-shrink-0">
                                <template x-if="conversationPhoto(conversation)">
                                    <img :src="formatAttachmentUrl(conversationPhoto(conversation))" class="rounded-circle object-fit-cover shadow-sm border border-2 border-white" style="width: 42px; height: 42px; background: var(--bs-light);">
                                </template>
                                <template x-if="!conversationPhoto(conversation)">
                                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold border border-2 border-white"
                                         style="width:42px;height:42px;font-size:14px;background:var(--bs-primary);"
                                         x-text="conversationInitials(conversation)"></div>
                                </template>
                                <span x-show="isOnline(conversation)"
                                      class="position-absolute bottom-0 end-0 rounded-circle border-2 border-white bg-success"
                                      style="width:11px;height:11px;"></span>
                            </div>
                            <div class="min-w-0 flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1 gap-2">
                                    <span class="text-truncate fw-semibold flex-grow-1" style="font-size:.875rem;" x-text="conversationTitle(conversation)"></span>
                                    <small class="text-muted flex-shrink-0 ms-2" style="font-size:10px;" x-text="lastTime(conversation)"></small>
                                </div>
                                <div class="text-truncate text-muted mb-1" style="font-size: 10px;" x-show="conversationLocation(conversation)">
                                    <i class="bi bi-geo-alt me-1 opacity-75"></i><span x-text="conversationLocation(conversation)"></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center gap-1">
                                    <div class="d-flex align-items-center gap-1 text-truncate flex-grow-1 text-muted" style="font-size:.78rem;">
                                        <template x-if="getPreviewImageUrl(lastMessageObj(conversation))">
                                            <img :src="getPreviewImageUrl(lastMessageObj(conversation))" class="rounded flex-shrink-0" style="width:16px; height:16px; object-fit:cover;">
                                        </template>
                                        <span class="text-truncate" x-text="lastPreview(conversation)"></span>
                                    </div>
                                    <span x-show="conversation.unread_count > 0"
                                          class="badge rounded-pill bg-primary flex-shrink-0"
                                          style="font-size:10px;" x-text="conversation.unread_count"></span>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>
            </aside>

            <!-- Center: Main Chat Area -->
            <section class="col-12 col-lg-6 flex-column h-100 position-relative bg-body"
                     :class="activeConversation ? 'd-flex' : 'd-none d-lg-flex'">
                <template x-if="activeConversation">
                    <div class="d-flex flex-column h-100">
                        <header class="d-flex align-items-center justify-content-between p-3 border-bottom shadow-sm z-1">
                            <div class="d-flex align-items-center gap-3 min-w-0">
                                <button type="button" class="btn btn-light d-lg-none p-2 rounded-circle" @click="activeConversation = null; replyTo = null; errorMessage = ''">
                                    <i class="bi bi-arrow-left"></i>
                                </button>
                                <template x-if="conversationPhoto(activeConversation)">
                                    <img :src="formatAttachmentUrl(conversationPhoto(activeConversation))" class="rounded-circle object-fit-cover shadow-sm border border-2 border-white" style="width: 42px; height: 42px; background: var(--bs-light);">
                                </template>
                                <template x-if="!conversationPhoto(activeConversation)">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px; font-size: 14px;" x-text="conversationInitials(activeConversation)"></div>
                                </template>
                                <div class="min-w-0">
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="mb-0 fw-bold text-truncate flex-grow-1" x-text="conversationTitle(activeConversation)"></h6>
                                        <small class="text-muted flex-shrink-0" style="font-size:12px;" x-show="conversationLocation(activeConversation)">
                                            <i class="bi bi-geo-alt"></i> <span x-text="conversationLocation(activeConversation)"></span>
                                        </small>
                                    </div>
                                    <small class="text-muted text-truncate d-block" x-text="conversationSubtitle(activeConversation)"></small>
                                </div>
                            </div>
                            <div class="d-flex gap-2 shrink-0">
                                <button type="button" @click="togglePin" class="btn btn-outline-secondary btn-sm" title="Pin chat">
                                    <i class="bi bi-star-fill"></i>
                                </button>
                                <button type="button" @click="toggleArchive" class="btn btn-outline-secondary btn-sm" title="Archive chat">
                                    <i class="bi bi-archive-fill"></i>
                                </button>
                                <button type="button" x-show="activeConversation?.type === 'group'" @click="openGroupSettings" class="btn btn-outline-secondary btn-sm" title="Group settings">
                                    <i class="bi bi-gear-fill"></i>
                                </button>
                            </div>
                        </header>

                        <!-- Message List -->
                        <div class="flex-grow-1 overflow-y-auto p-4 bg-body-tertiary d-flex flex-column gap-3" x-ref="messageScroller">
                            <template x-for="message in messages" :key="message.id">
                                <div class="d-flex" :class="message.sender_id === currentUserId ? 'justify-content-end' : 'justify-content-start'">
                                    <div style="max-width: 75%;">
                                        <div class="d-flex align-items-center gap-2 mb-1 text-muted" style="font-size: 10px;" :class="message.sender_id === currentUserId ? 'justify-content-end' : 'justify-content-start'">
                                            <template x-if="message.sender?.photo">
                                                <img :src="formatAttachmentUrl(message.sender.photo)" class="rounded-circle object-fit-cover shadow-sm" style="width:16px; height:16px;">
                                            </template>
                                            <strong class="text-uppercase" x-text="message.sender?.name || 'User'"></strong>
                                            <span x-show="senderLocation(message)" class="opacity-75">
                                                <i class="bi bi-geo-alt"></i> <span x-text="senderLocation(message)"></span>
                                            </span>
                                            <span x-text="lastTime(message)"></span>
                                            <span x-show="message.edited_at" class="fst-italic">Edited</span>
                                        </div>
                                        
                                        <div class="p-3 shadow-sm" 
                                             :class="message.sender_id === currentUserId ? 'bg-primary text-white' : 'bg-body border text-body'"
                                             :style="message.sender_id === currentUserId ? 'border-radius: 1rem 1rem 0 1rem;' : 'border-radius: 1rem 1rem 1rem 0;'">
                                            
                                            <template x-if="message.parent">
                                                <button type="button" @click="scrollToMessage(message.parent_id)" class="btn btn-sm w-100 text-start border-start border-3 border-light rounded-0 ps-2 mb-2 p-0 opacity-75 d-flex align-items-center gap-2 overflow-hidden">
                                                    <template x-if="getPreviewImageUrl(message.parent)">
                                                        <img :src="getPreviewImageUrl(message.parent)" class="rounded flex-shrink-0" style="width: 36px; height: 36px; object-fit: cover;">
                                                    </template>
                                                    <div class="overflow-hidden w-100">
                                                        <strong class="d-block text-truncate small" x-text="message.parent.sender?.name || 'Reply'"></strong>
                                                        <span class="d-block text-truncate small" x-text="message.parent.content || (getPreviewImageUrl(message.parent) ? 'Photo' : 'Attachment')"></span>
                                                    </div>
                                                </button>
                                            </template>

                                            <template x-if="editingMessageId !== message.id">
                                                <p class="mb-0 text-break" style="white-space: pre-wrap;" x-text="message.content"></p>
                                            </template>

                                            <template x-if="editingMessageId === message.id">
                                                <div class="d-flex flex-column gap-2">
                                                    <textarea x-model="editingDraft" rows="2" class="form-control form-control-sm"></textarea>
                                                    <div class="d-flex justify-content-end gap-1">
                                                        <button type="button" @click="cancelEdit" class="btn btn-sm btn-light py-0" style="font-size: 10px;">Cancel</button>
                                                        <button type="button" @click="saveEdit(message)" class="btn btn-sm btn-success py-0" style="font-size: 10px;">Save</button>
                                                    </div>
                                                </div>
                                            </template>

                                            <template x-if="message.attachments?.length">
                                                <div class="mt-2 d-flex flex-column gap-1">
                                                    <template x-for="attachment in message.attachments" :key="attachment.path || attachment.name">
                                                        <div>
                                                            <template x-if="isImageAttachment(attachment)">
                                                                <a :href="formatAttachmentUrl(attachment.path)" target="_blank" class="d-block rounded overflow-hidden position-relative">
                                                                    <img :src="formatAttachmentUrl(attachment.path)" class="img-fluid max-h-100" style="max-height: 200px; object-fit: cover;">
                                                                </a>
                                                            </template>
                                                            <template x-if="!isImageAttachment(attachment)">
                                                                <a :href="formatAttachmentUrl(attachment.path)" target="_blank" class="btn btn-sm btn-light d-flex align-items-center gap-2 text-start">
                                                                    <i class="bi bi-file-earmark-text"></i>
                                                                    <span class="text-truncate flex-grow-1" x-text="attachment.name || attachment.path"></span>
                                                                    <i class="bi bi-download text-muted"></i>
                                                                </a>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                        
                                        <div class="mt-1 d-flex gap-2" :class="message.sender_id === currentUserId ? 'justify-content-end' : 'justify-content-start'">
                                            <button type="button" @click="setReply(message)" class="btn btn-link btn-sm p-0 text-muted text-decoration-none" style="font-size: 10px;">REPLY</button>
                                            @can('chat-edit')
                                            <button type="button" @click="startEdit(message)" x-show="message.sender_id === currentUserId" class="btn btn-link btn-sm p-0 text-muted text-decoration-none" style="font-size: 10px;">EDIT</button>
                                            @endcan
                                            @can('chat-delete')
                                            <button type="button" @click="deleteMessage(message)" :disabled="busyMessageId === message.id" class="btn btn-link btn-sm p-0 text-danger text-decoration-none" style="font-size: 10px;">DELETE</button>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div><!-- end message list -->

                        <!-- Composer -->
                        <form @submit.prevent="sendMessage()" class="p-3 border-top bg-body">
                            <div :class="{'d-flex': errorMessage, 'd-none': !errorMessage}" x-cloak class="alert alert-danger py-2 px-3 mb-2 small justify-content-between align-items-center">
                                <span x-text="errorMessage"></span>
                                <button type="button" @click="errorMessage = ''" class="btn-close" style="font-size:10px;"></button>
                            </div>
                            <div :class="{'d-flex': replyTo, 'd-none': !replyTo}" x-cloak class="rounded mb-2 px-3 py-2 align-items-center justify-content-between small" style="background:var(--bs-secondary-bg);border-left:3px solid var(--bs-primary);">
                                <div class="d-flex align-items-center gap-2 overflow-hidden">
                                    <template x-if="replyTo && getPreviewImageUrl(replyTo)">
                                        <img :src="getPreviewImageUrl(replyTo)" class="rounded flex-shrink-0" style="width: 32px; height: 32px; object-fit: cover;">
                                    </template>
                                    <div class="text-truncate text-muted">
                                        <strong x-text="replyTo ? '↩ ' + (replyTo.sender?.name || 'Unknown') : ''"></strong>
                                        <span x-text="replyTo?.content ? ': ' + replyTo.content : (getPreviewImageUrl(replyTo) ? ' Photo' : ' Attachment')"></span>
                                    </div>
                                </div>
                                <button type="button" @click="replyTo = null" class="btn-close ms-2 flex-shrink-0" style="font-size:10px;"></button>
                            </div>
                            <div :class="{'d-flex': pendingAttachments.length, 'd-none': !pendingAttachments.length}" x-cloak class="flex-wrap gap-2 mb-3">
                                <template x-for="(file, index) in pendingAttachments" :key="`${file.name}-${file.size}-${index}`">
                                    <div class="position-relative border rounded-3 overflow-hidden bg-body-secondary" style="width:90px;">
                                        <template x-if="file.previewUrl">
                                            <img :src="file.previewUrl" class="w-100" style="height:70px;object-fit:cover;" alt="">
                                        </template>
                                        <template x-if="!file.previewUrl">
                                            <div class="d-flex align-items-center justify-content-center" style="height:70px;">
                                                <i class="bi bi-file-earmark-text fs-3 text-muted"></i>
                                            </div>
                                        </template>
                                        <div class="px-1 pb-1" style="font-size:9px;" class="text-truncate text-muted" x-text="formatFileSize(file.size)"></div>
                                        <button type="button" @click="removePendingAttachment(index)" class="btn-close position-absolute top-0 end-0 m-1" style="font-size:8px;background:white;"></button>
                                    </div>
                                </template>
                            </div>
                            <div class="d-flex align-items-end gap-2">
                                <input x-ref="attachmentInput" type="file" multiple class="d-none" @change="handleAttachmentSelection">
                                <button type="button" @click="$refs.attachmentInput.click()" class="btn btn-outline-secondary rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width:40px;height:40px;" title="Attach">
                                    <i class="bi bi-paperclip"></i>
                                </button>
                                <textarea x-model="draft" @keydown.enter.prevent="!$event.shiftKey && sendMessage()" x-ref="composer" rows="1"
                                    placeholder="Type a message... (Enter to send, Shift+Enter for newline)"
                                    class="form-control flex-grow-1"
                                    style="resize:none;max-height:120px;border-radius:1.25rem;padding:.625rem 1rem;"></textarea>
                                <button type="submit" :disabled="sendingMessage || (!(draft || '').trim() && !pendingAttachments.length)"
                                    class="btn btn-primary rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center shadow-sm"
                                    style="width:40px;height:40px;">
                                    <span x-show="sendingMessage" class="spinner-border spinner-border-sm"></span>
                                    <i x-show="!sendingMessage" class="bi bi-send"></i>
                                </button>
                            </div>
                        </form>
                    </div><!-- end d-flex flex-column h-100 -->
                </template><!-- end x-if activeConversation -->

                <template x-if="!activeConversation">
                    <div class="d-flex flex-grow-1 align-items-center justify-content-center">
                        <div class="text-center">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-4" style="width:96px;height:96px;">
                                <i class="bi bi-chat-dots-fill text-primary" style="font-size:2.5rem;"></i>
                            </div>
                            <h5 class="fw-bold mb-1">Your Messages</h5>
                            <p class="text-muted small mb-3">Select a conversation or start a new one</p>
                            @can('chat-create')
                            <button type="button" @click="showGroupModal = true" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-plus-circle me-1"></i>New Group
                            </button>
                            @endcan
                        </div>
                    </div>
                </template>
            </section>

            <!-- Right Sidebar: Live Users -->
            <aside class="d-none d-lg-flex col-lg-3 flex-column border-start bg-body-tertiary h-100">
                <div class="p-3 border-bottom">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h6 class="mb-0 fw-bold text-uppercase" style="font-size:0.75rem; letter-spacing:0.5px;">Live Users</h6>
                            <p class="mb-0 text-muted" style="font-size: 11px;" x-text="`${onlineUsers.length} active, ${users.length} available`"></p>
                        </div>
                        <div class="d-flex gap-1">
                            <button type="button" @click="exportUsersToCSV" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center border shadow-sm" style="width:28px;height:28px;" title="Export Data">
                                <i class="bi bi-download"></i>
                            </button>
                            <button type="button" @click="fetchUsers" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center border shadow-sm" style="width:28px;height:28px;" title="Refresh users">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                    <div class="position-relative mb-3">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" style="pointer-events:none;"></i>
                        <input type="search" x-model.debounce.300ms="userSearch" placeholder="Find users..." class="form-control form-control-sm ps-5 rounded-pill">
                    </div>
                    <div class="d-flex gap-2 mb-2">
                        <button type="button" @click="userFilter = 'all'" :class="userFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary bg-body'" class="btn btn-sm flex-fill fw-semibold py-1 border" style="font-size: 11px;">All</button>
                        <button type="button" @click="userFilter = 'online'" :class="userFilter === 'online' ? 'btn-primary' : 'btn-outline-secondary bg-body'" class="btn btn-sm flex-fill fw-semibold py-1 border" style="font-size: 11px;">Online</button>
                    </div>
                    <div class="d-flex gap-2">
                        <select x-model="userSort" class="form-select form-select-sm" style="font-size: 11px;">
                            <option value="default">Default Sort</option>
                            <option value="revenue_desc">Revenue: High to Low</option>
                            <option value="revenue_asc">Revenue: Low to High</option>
                        </select>
                    </div>
                </div>
                <div class="flex-grow-1 overflow-y-auto">
                    <template x-for="(user, index) in visibleUsers" :key="user.id">
                        <button type="button" @click="startDirect(user.id)" class="btn bg-transparent border-0 text-start w-100 d-flex align-items-start gap-3 mb-0 p-3" 
                                style="border-bottom: 1px solid var(--bs-border-color) !important; transition: background 0.15s;"
                                onmouseover="this.style.backgroundColor='var(--bs-secondary-bg)'" 
                                onmouseout="this.style.backgroundColor='transparent'"
                                :disabled="startingUserId === user.id">
                            <div class="position-relative flex-shrink-0 mt-1">
                                <template x-if="user.photo">
                                    <img :src="formatAttachmentUrl(user.photo)" class="rounded-circle object-fit-cover shadow-sm" style="width: 36px; height: 36px; background: var(--bs-light);">
                                </template>
                                <template x-if="!user.photo">
                                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" 
                                         style="width:36px;height:36px;font-size:12px;background:var(--bs-secondary);" 
                                         x-text="initials(user.name)"></div>
                                </template>
                                <span class="position-absolute bottom-0 end-0 rounded-circle border-2 border-white" style="width: 12px; height: 12px;" :class="user.is_online ? 'bg-success' : 'bg-secondary'"></span>
                            </div>
                            <div class="min-w-0 flex-grow-1">
                                <!-- First Row: Name & Badge -->
                                <div class="d-flex justify-content-between align-items-center mb-1 gap-2">
                                    <div class="d-flex align-items-center gap-1 flex-grow-1 min-w-0">
                                        <template x-if="userSort === 'revenue_desc' && index === 0"><span title="Top Earner" class="fs-5">🥇</span></template>
                                        <template x-if="userSort === 'revenue_desc' && index === 1"><span title="2nd Earner" class="fs-5">🥈</span></template>
                                        <template x-if="userSort === 'revenue_desc' && index === 2"><span title="3rd Earner" class="fs-5">🥉</span></template>
                                        <span class="text-truncate fw-bold text-body" style="font-size: .875rem;" x-text="user.name"></span>
                                    </div>
                                    <span class="badge rounded-pill fw-semibold flex-shrink-0" style="font-size: 9px; padding: 0.25em 0.5em;" :class="user.is_online ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25'" x-text="user.is_online ? 'Live' : 'Away'"></span>
                                </div>
                                
                                <!-- Second Row: Location or Email -->
                                <div class="text-truncate text-muted mb-1" style="font-size: 11px;">
                                    <template x-if="user.location">
                                        <span><i class="bi bi-geo-alt me-1 opacity-75"></i><span x-text="user.location"></span></span>
                                    </template>
                                    <template x-if="!user.location">
                                        <span><i class="bi bi-envelope me-1 opacity-75"></i><span x-text="user.email"></span></span>
                                    </template>
                                </div>
                                
                                <!-- Third Row: Last Seen & Device -->
                                <div class="d-flex justify-content-between align-items-center text-muted opacity-75" style="font-size: 10px;">
                                    <span class="text-truncate" x-text="user.last_seen_label"></span>
                                    <span class="text-truncate ms-2 text-end" x-show="user.active_device" x-text="user.active_device"></span>
                                </div>
                                <!-- Fourth Row: Today's Stats -->
                                <div class="d-flex gap-2 mt-2">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 9px;">
                                        <i class="bi bi-box-seam me-1"></i> <span x-text="user.today_orders || 0"></span> Orders
                                    </span>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 9px;">
                                        <i class="bi bi-currency-dollar me-1"></i> <span x-text="'Rs. ' + (user.today_revenue || 0).toLocaleString()"></span>
                                    </span>
                                </div>
                            </div>
                            <div x-show="startingUserId === user.id" class="spinner-border spinner-border-sm text-primary ms-auto mt-1" role="status"></div>
                        </button>
                    </template>
                    <div x-show="visibleUsers.length === 0" class="text-center p-5 text-muted">
                        <i class="bi bi-people fs-2 d-block mb-2 opacity-50"></i>
                        <p class="small mb-0">No users found.</p>
                    </div>
                </div>
            </aside>
        </div>

        <div x-show="showGroupModal"
             x-cloak
             style="position: fixed; inset: 0; z-index: 1055; background: rgba(0,0,0,0.55); display: flex; align-items: center; justify-content: center; padding: 1rem;"
             @keydown.escape.window="showGroupModal = false; groupError = ''">
            <form @submit.prevent="createGroup()" class="bg-body rounded-4 shadow-lg w-100" style="max-width: 500px; max-height: 90vh; overflow-y: auto;">
                <div class="d-flex align-items-center justify-content-between p-4 border-bottom">
                    <h5 class="mb-0 fw-bold">New Group</h5>
                    <button type="button" @click="showGroupModal = false; groupError = ''" class="btn-close"></button>
                </div>
                <div class="p-4">
                    <div :class="{'d-flex': groupError, 'd-none': !groupError}" x-cloak class="alert alert-danger py-2 px-3 mb-3 small align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span x-text="groupError"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Group Name <span class="text-danger">*</span></label>
                        <input x-model="groupForm.name" class="form-control" placeholder="e.g. Sales Team" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Description</label>
                        <textarea x-model="groupForm.description" class="form-control" placeholder="What's this group about?" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Privacy</label>
                        <select x-model="groupForm.privacy" class="form-select">
                            <option value="private">Private — invite only</option>
                            <option value="public">Public — anyone can join</option>
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-bold small text-uppercase text-muted">Add Members</label>
                        <input x-model="groupCreateSearch" type="search" class="form-control form-control-sm mb-2" placeholder="Search users...">
                        <div class="border rounded p-2 overflow-y-auto" style="max-height: 180px;">
                            <template x-for="user in filteredGroupUsers" :key="user.id">
                                <div class="d-flex align-items-center p-2 rounded mb-0" 
                                     onmouseover="this.style.backgroundColor='var(--bs-secondary-bg)'" 
                                     onmouseout="this.style.backgroundColor='transparent'">
                                    
                                    <input class="ms-0 mt-0 flex-shrink-0" 
                                           type="checkbox" 
                                           :value="user.id" 
                                           x-model="groupForm.member_ids" 
                                           :id="'newUserCheck_' + user.id"
                                           style="width: 1.25em; height: 1.25em; cursor: pointer; margin-right: 0.75rem;">
                                           
                                    <label class="d-flex align-items-center gap-2 flex-grow-1 mb-0" 
                                           :for="'newUserCheck_' + user.id" 
                                           style="cursor: pointer;">
                                        
                                        <template x-if="user.photo">
                                            <img :src="formatAttachmentUrl(user.photo)" class="rounded-circle object-fit-cover shadow-sm flex-shrink-0" style="width: 28px; height: 28px;">
                                        </template>
                                        <template x-if="!user.photo">
                                            <span class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 28px; height: 28px; font-size: 11px;" x-text="initials(user.name)"></span>
                                        </template>
                                        
                                        <div class="min-w-0 flex-grow-1 text-truncate">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="small fw-semibold text-truncate flex-grow-1" x-text="user.name"></span>
                                                <span class="badge rounded-pill flex-shrink-0" style="font-size: 9px; padding: 0.25em 0.5em;" :class="user.is_online ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25'" x-text="user.is_online ? 'Live' : 'Away'"></span>
                                            </div>
                                            <div class="text-muted text-truncate" style="font-size:10px;" x-show="user.location">
                                                <i class="bi bi-geo-alt me-1 opacity-75"></i><span x-text="user.location"></span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </template>
                            <div x-show="filteredGroupUsers.length === 0" class="text-center text-muted small p-3">No users found.</div>
                        </div>
                        <div class="small text-muted mt-1"><span x-text="groupForm.member_ids.length"></span> selected</div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 p-4 pt-0">
                    <button type="button" @click="showGroupModal = false; groupError = ''" class="btn btn-light fw-bold" :disabled="groupBusy">Cancel</button>
                    @can('chat-create')
                    <button type="submit" class="btn btn-primary fw-bold px-4" :disabled="groupBusy || !groupForm.name.trim()">
                        <span x-show="groupBusy" class="spinner-border spinner-border-sm me-2"></span>
                        <span x-text="groupBusy ? 'Creating...' : 'Create Group'"></span>
                    </button>
                    @endcan
                </div>
            </form>
        </div>

        <!-- Group Settings Modal -->
        <div x-show="showGroupSettingsModal && activeConversation?.type === 'group'" 
             x-cloak 
             style="position:fixed;inset:0;z-index:1055;background:rgba(0,0,0,0.55);display:flex;align-items:center;justify-content:center;padding:1rem;"
             @keydown.escape.window="closeGroupSettings">
            <div class="bg-body rounded-4 shadow-lg w-100 d-flex flex-column" style="max-width:800px; max-height:90vh;">
                <div class="p-4 border-bottom d-flex align-items-center justify-content-between bg-primary text-white" style="border-top-left-radius:1rem; border-top-right-radius:1rem;">
                    <div>
                        <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                            <h5 class="mb-0 fw-bold" x-text="conversationTitle(activeConversation)"></h5>
                            <small class="text-muted" x-show="conversationLocation(activeConversation)">
                                <i class="bi bi-geo-alt"></i> <span x-text="conversationLocation(activeConversation)"></span>
                            </small>
                        </div>
                        <small class="opacity-75" x-text="conversationSubtitle(activeConversation)"></small>
                    </div>
                    <button type="button" @click="closeGroupSettings" class="btn-close btn-close-white"></button>
                </div>
                
                <div class="p-4 overflow-y-auto">
                    <div x-show="errorMessage" x-cloak class="alert alert-danger py-2 px-3 mb-4 small" x-text="errorMessage" style="display:none;"></div>
                    
                    <div class="row g-4">
                        <!-- Settings -->
                        <div class="col-md-6 border-end-md">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-muted text-uppercase mb-0" style="font-size:0.75rem; letter-spacing:0.5px;">Group Details</h6>
                                @can('chat-edit')
                                <button type="button" x-show="canManageSettings" @click="updateGroup" :disabled="groupBusy" class="btn btn-primary btn-sm fw-semibold px-3 shadow-sm">Save</button>
                                @endcan
                            </div>
                            <template x-if="canManageSettings">
                                <div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-muted">Group Name</label>
                                        <input x-model="groupSettingsForm.name" class="form-control" placeholder="Group name">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-muted">Description</label>
                                        <textarea x-model="groupSettingsForm.description" rows="3" class="form-control" placeholder="Description"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-muted">Privacy</label>
                                        <select x-model="groupSettingsForm.privacy" class="form-select">
                                            <option value="private">Private — invite only</option>
                                            <option value="public">Public — anyone can join</option>
                                        </select>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!canManageSettings">
                                <div class="bg-body-secondary rounded-3 p-4 mb-4 border">
                                    <h6 class="fw-bold mb-2" x-text="activeConversation?.name"></h6>
                                    <p class="small text-muted mb-3" x-text="activeConversation?.description || 'No description'"></p>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 text-uppercase" x-text="activeConversation?.privacy"></span>
                                </div>
                            </template>

                            <hr class="my-4">
                            
                            <h6 class="fw-bold text-danger mb-3" style="font-size:0.75rem; letter-spacing:0.5px; text-transform:uppercase;">Danger Zone</h6>
                            <button type="button" x-show="!canManageSettings" @click="leaveGroup" :disabled="groupBusy" class="btn btn-outline-danger w-100 fw-semibold">Leave Group</button>
                            @can('chat-delete')
                            <button type="button" x-show="canManageSettings" @click="deleteGroup" :disabled="groupBusy" class="btn btn-danger w-100 fw-semibold shadow-sm">Delete Group</button>
                            @endcan
                        </div>

                        <!-- Members -->
                        <div class="col-md-6 d-flex flex-column" style="max-height: 500px;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-muted text-uppercase mb-0" style="font-size:0.75rem; letter-spacing:0.5px;">Members (<span x-text="activeConversation?.active_members?.length"></span>)</h6>
                                @can('chat-edit')
                                <button type="button" x-show="canManageSettings" @click="showAddMember = !showAddMember" class="btn btn-outline-primary btn-sm fw-semibold px-3 border shadow-sm">
                                    <i class="bi bi-person-plus-fill me-1"></i> Add
                                </button>
                                @endcan
                            </div>

                            <div x-show="showAddMember" x-cloak class="mb-3 bg-body-tertiary p-3 rounded-3 border">
                                <input x-model="groupAddMemberSearch" type="search" class="form-control form-control-sm mb-3 rounded-pill" placeholder="Search to add...">
                                <div class="overflow-y-auto rounded border bg-body" style="max-height: 160px;">
                                    <template x-for="user in filteredAddMembers" :key="user.id">
                                        <div class="d-flex align-items-center justify-content-between p-2 border-bottom"
                                             style="transition: background 0.15s;"
                                             onmouseover="this.style.backgroundColor='var(--bs-secondary-bg)'" 
                                             onmouseout="this.style.backgroundColor='transparent'">
                                            <div class="d-flex align-items-center gap-2">
                                                <template x-if="user.photo">
                                                    <img :src="formatAttachmentUrl(user.photo)" class="rounded-circle object-fit-cover shadow-sm" style="width: 28px; height: 28px;">
                                                </template>
                                                <template x-if="!user.photo">
                                                    <span class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 11px;" x-text="initials(user.name)"></span>
                                                </template>
                                                <div class="min-w-0 flex-grow-1 text-truncate" style="max-width: 140px;">
                                                    <div class="d-flex align-items-center gap-1 text-truncate mb-1">
                                                        <span class="small fw-semibold text-truncate flex-grow-1" x-text="user.name"></span>
                                                    </div>
                                                    <div class="text-muted text-truncate" style="font-size:9px;" x-show="user.location">
                                                        <i class="bi bi-geo-alt me-1 opacity-75"></i><span x-text="user.location"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" @click="addGroupMember(user.id)" class="btn btn-primary btn-sm py-1 px-3 rounded-pill" style="font-size: 10px;" :disabled="groupBusy">Add</button>
                                        </div>
                                    </template>
                                    <div x-show="filteredAddMembers.length === 0" class="text-muted small text-center p-3">No users found.</div>
                                </div>
                            </div>

                            <div class="flex-grow-1 overflow-y-auto rounded-3 border bg-body-tertiary p-1">
                                <template x-for="member in activeConversation?.active_members" :key="member.id">
                                    <div class="d-flex align-items-center justify-content-between p-2 border-bottom last-border-0"
                                         style="transition: background 0.15s;"
                                         onmouseover="this.style.backgroundColor='var(--bs-body-bg)'" 
                                         onmouseout="this.style.backgroundColor='transparent'">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="position-relative">
                                                <template x-if="member.user?.photo">
                                                    <img :src="formatAttachmentUrl(member.user.photo)" class="rounded-circle object-fit-cover shadow-sm" style="width: 36px; height: 36px;">
                                                </template>
                                                <template x-if="!member.user?.photo">
                                                    <span class="rounded-circle bg-primary bg-opacity-75 text-white d-inline-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 36px; height: 36px; font-size: 13px;" x-text="initials(member.user?.name)"></span>
                                                </template>
                                                <span class="position-absolute bottom-0 end-0 rounded-circle border-2 border-white" style="width: 12px; height: 12px;" :class="member.user?.is_online ? 'bg-success' : 'bg-secondary'"></span>
                                            </div>
                                            <div class="min-w-0 flex-grow-1 text-truncate">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <div class="small fw-semibold text-truncate flex-grow-1" x-text="member.user?.name"></div>
                                                    <span class="badge flex-shrink-0" 
                                                          style="font-size: 9px; padding: 0.25em 0.5em;"
                                                          :class="member.role === 'owner' ? 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' : (member.role === 'admin' ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25')" 
                                                          x-text="member.role"></span>
                                                </div>
                                                <div class="text-muted text-truncate" style="font-size:10px;" x-show="userLocation(member.user_id)">
                                                    <i class="bi bi-geo-alt me-1 opacity-75"></i><span x-text="userLocation(member.user_id)"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div x-show="canManageSettings && member.user_id !== currentUserId" class="dropdown">
                                            <button class="btn btn-light btn-sm px-2 rounded-circle border shadow-sm d-flex align-items-center justify-content-center" style="width:28px;height:28px;" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical" style="font-size:12px;"></i></button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 text-sm py-2">
                                                <li><button type="button" @click="updateGroupMemberRole(member, member.role === 'admin' ? 'member' : 'admin')" class="dropdown-item small" x-text="member.role === 'admin' ? 'Demote to Member' : 'Make Admin'"></button></li>
                                                <li><hr class="dropdown-divider my-2"></li>
                                                <li><button type="button" @click="removeGroupMember(member)" class="dropdown-item small text-danger fw-semibold">Remove from Group</button></li>
                                            </ul>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Modal -->
        <div x-show="confirmModal.show" x-cloak
             style="position:fixed;inset:0;z-index:1065;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;padding:1rem;"
             @keydown.escape.window="closeConfirm()">
            <div class="bg-body rounded-4 shadow-lg text-center p-4" style="max-width:360px;width:100%;">
                <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-4"></i>
                </div>
                <h5 class="fw-bold mb-2" x-text="confirmModal.title"></h5>
                <p class="text-muted small mb-4" x-text="confirmModal.message"></p>
                <div class="d-flex gap-2">
                    <button type="button" @click="closeConfirm()" class="btn btn-light fw-semibold flex-grow-1" x-text="confirmModal.cancelText"></button>
                    <button type="button" @click="confirmModal.onConfirm()" class="btn btn-danger fw-semibold flex-grow-1" x-text="confirmModal.confirmText"></button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function chatWorkspace(initial) {
        return {
            currentUserId: @js(auth()->id()),
            conversations: initial.conversations || [],
            users: initial.users || [],
            pollInterval: initial.pollInterval || 15000,
            activeConversation: null,
            messages: [],
            
            search: '',
            userSearch: '',
            filter: 'all',
            userFilter: 'all',
            userSort: 'default',
            
            draft: '',
            replyTo: null,
            editingMessageId: null,
            editingDraft: '',
            errorMessage: '',
            
            pendingAttachments: [],
            sendingMessage: false,
            busyMessageId: null,
            startingUserId: null,
            
            showGroupModal: false,
            groupForm: { name: '', description: '', privacy: 'private', member_ids: [] },
            groupCreateSearch: '',
            groupBusy: false,
            groupError: '',

            showGroupSettingsModal: false,
            groupSettingsForm: { name: '', description: '', privacy: 'private' },
            groupAddMemberSearch: '',
            showAddMember: false,
            
            confirmModal: {
                show: false, title: '', message: '', cancelText: 'Cancel', confirmText: 'Confirm', onConfirm: () => {}
            },
            
            pollTimer: null,
            
            init() {
                this.poll();
                this.pollTimer = setInterval(() => this.poll(), this.pollInterval);
            },
            
            get onlineUsers() {
                return this.users.filter(u => u.is_online);
            },
            
            get onlineLabel() {
                const count = this.onlineUsers.length;
                return count + (count === 1 ? ' online now' : ' online now');
            },
            
            get filteredConversations() {
                let filtered = Array.isArray(this.conversations) ? this.conversations : [];

                if (this.filter === 'unread') {
                    filtered = filtered.filter(c => (c.unread_count || 0) > 0);
                } else if (this.filter === 'pinned') {
                    // pinned_at is on the member pivot, returned as is_pinned on conversation
                    filtered = filtered.filter(c => c.is_pinned ||
                        c.active_members?.some(m => m.user_id === this.currentUserId && m.pinned_at));
                }

                if (this.search) {
                    const q = this.search.toLowerCase();
                    filtered = filtered.filter(c => (this.conversationTitle(c) || '').toLowerCase().includes(q));
                }

                return filtered.sort((a, b) => {
                    const aPinned = a.is_pinned || a.active_members?.some(m => m.user_id === this.currentUserId && m.pinned_at);
                    const bPinned = b.is_pinned || b.active_members?.some(m => m.user_id === this.currentUserId && m.pinned_at);
                    if (aPinned !== bPinned) return bPinned ? 1 : -1;
                    return new Date(b.updated_at || 0) - new Date(a.updated_at || 0);
                });
            },

            get searchResults() {
                if (!this.search) return { users: [] };
                const q = this.search.toLowerCase();
                return {
                    users: this.users.filter(u => (u.name || '').toLowerCase().includes(q)).slice(0, 5)
                };
            },
            
            get visibleUsers() {
                let filtered = Array.isArray(this.users) ? [...this.users] : [];
                if (this.userFilter === 'online') {
                    filtered = filtered.filter(u => u.is_online);
                }
                if (this.userSearch) {
                    const q = this.userSearch.toLowerCase();
                    filtered = filtered.filter(u => (u.name || '').toLowerCase().includes(q));
                }
                if (this.userSort === 'revenue_desc') {
                    filtered.sort((a, b) => (b.today_revenue || 0) - (a.today_revenue || 0));
                } else if (this.userSort === 'revenue_asc') {
                    filtered.sort((a, b) => (a.today_revenue || 0) - (b.today_revenue || 0));
                }
                return filtered;
            },
            
            get filteredGroupUsers() {
                let filtered = this.users;
                if (this.groupCreateSearch) {
                    const q = this.groupCreateSearch.toLowerCase();
                    filtered = filtered.filter(u => u.name.toLowerCase().includes(q));
                }
                return filtered;
            },
            
            get filteredAddMembers() {
                if (!this.activeConversation) return [];
                const existingIds = this.activeConversation.active_members?.map(m => m.user_id) || [];
                let filtered = this.users.filter(u => !existingIds.includes(u.id));
                
                if (this.groupAddMemberSearch) {
                    const q = this.groupAddMemberSearch.toLowerCase();
                    filtered = filtered.filter(u => u.name.toLowerCase().includes(q));
                }
                return filtered;
            },
            
            get canManageSettings() {
                if (!this.activeConversation || this.activeConversation.type !== 'group') return false;
                const member = this.activeConversation.active_members?.find(m => Number(m.user_id) === Number(this.currentUserId));
                return member && ['owner', 'admin'].includes(member.role);
            },

            get isOwner() {
                if (!this.activeConversation) return false;
                const member = this.activeConversation.active_members?.find(m => Number(m.user_id) === Number(this.currentUserId));
                return member?.role === 'owner';
            },
            
            async poll() {
                try {
                    const [convRes, userRes] = await Promise.all([
                        axios.get('/api/chat/conversations'),
                        axios.get('/api/chat/users'),
                    ]);

                    this.conversations = convRes.data.data || convRes.data.conversations || convRes.data || [];
                    const usersRaw = userRes.data.data || userRes.data.users || userRes.data || [];
                    this.users = Array.isArray(usersRaw) ? usersRaw : [];

                    if (this.activeConversation) {
                        const updated = this.conversations.find(c => c.id === this.activeConversation.id);
                        if (updated) this.activeConversation = updated;

                        const msgRes = await axios.get(`/api/chat/conversations/${this.activeConversation.id}/messages`);
                        // Backend returns paginated: { data: { data: [...], current_page, ... } }
                        const paginatedData = msgRes.data.data;
                        const newMessages = (Array.isArray(paginatedData)
                            ? paginatedData
                            : (paginatedData?.data || [])).slice().reverse();
                        const lastOldId = this.messages.length ? this.messages[this.messages.length - 1]?.id : null;
                        const lastNewId = newMessages.length ? newMessages[newMessages.length - 1]?.id : null;
                        if (lastOldId !== lastNewId) {
                            this.messages = newMessages;
                            this.scrollToBottom();
                        }
                    }

                    // Mark ourselves online
                    axios.post('/api/chat/active-status', { status: 'online' }).catch(() => {});
                } catch (e) {
                    console.error('Poll failed:', e);
                }
            },
            
            async fetchUsers() {
                try {
                    const res = await axios.get('/api/chat/users');
                    const raw = res.data.data || res.data.users || res.data;
                    this.users = Array.isArray(raw) ? raw : [];
                } catch (e) {
                    console.error('Failed to fetch users:', e);
                }
            },
            
            async selectConversation(conversation) {
                this.activeConversation = conversation;
                this.messages = [];
                this.errorMessage = '';
                this.replyTo = null;
                this.cancelEdit();

                try {
                    const res = await axios.get(`/api/chat/conversations/${conversation.id}/messages`);
                    // Backend returns paginated LengthAwarePaginator inside data key
                    const paginatedData = res.data.data;
                    this.messages = (Array.isArray(paginatedData)
                        ? paginatedData
                        : (paginatedData?.data || [])).slice().reverse();
                    this.scrollToBottom();

                    if ((conversation.unread_count || 0) > 0) {
                        const lastMsg = this.messages[this.messages.length - 1];
                        await axios.post(`/api/chat/conversations/${conversation.id}/read`,
                            lastMsg ? { message_id: lastMsg.id } : {});
                        conversation.unread_count = 0;
                    }
                } catch (e) {
                    this.errorMessage = 'Failed to load messages. Please try again.';
                    console.error(e);
                }
            },
            
            async startDirect(userId) {
                this.startingUserId = userId;
                this.errorMessage = '';
                try {
                    // Backend expects 'user_id' for direct conversations
                    const res = await axios.post('/api/chat/conversations', {
                        type: 'direct',
                        user_id: userId
                    });
                    const conv = res.data.data || res.data.conversation || res.data;

                    const existing = this.conversations.find(c => c.id === conv.id);
                    if (!existing) {
                        this.conversations.unshift(conv);
                    }
                    this.selectConversation(existing || conv);
                } catch (e) {
                    this.errorMessage = e.response?.data?.message || 'Failed to start conversation';
                    console.error(e);
                } finally {
                    this.startingUserId = null;
                }
            },
            
            handleAttachmentSelection(event) {
                const files = Array.from(event.target.files || []);
                const processed = files.map(file => {
                    let previewUrl = null;
                    if (file.type.startsWith('image/')) {
                        previewUrl = URL.createObjectURL(file);
                    }
                    return {
                        file: file,
                        name: file.name,
                        size: file.size,
                        type: file.type,
                        previewUrl: previewUrl
                    };
                });
                this.pendingAttachments = [...this.pendingAttachments, ...processed].slice(0, 10);
                event.target.value = '';
            },

            removePendingAttachment(index) {
                const item = this.pendingAttachments[index];
                if (item?.previewUrl) URL.revokeObjectURL(item.previewUrl);
                this.pendingAttachments.splice(index, 1);
            },

            formatFileSize(bytes) {
                if (!bytes) return '';
                const units = ['B', 'KB', 'MB', 'GB'];
                let i = 0;
                while (bytes >= 1024 && i < units.length - 1) { bytes /= 1024; i++; }
                return bytes.toFixed(1) + ' ' + units[i];
            },
            
            async sendMessage() {
                const draftContent = (this.draft || '').trim();
                if (!draftContent && !this.pendingAttachments.length) return;
                if (!this.activeConversation) return;

                this.sendingMessage = true;
                this.errorMessage = '';

                const formData = new FormData();
                // Backend validates: content, parent_id, files[]
                if (draftContent) formData.append('content', draftContent);
                if (this.replyTo?.id) formData.append('parent_id', this.replyTo.id);
                this.pendingAttachments.forEach(item => {
                    if (item && item.file) {
                        formData.append('files[]', item.file);
                    }
                });

                try {
                    const res = await axios.post(
                        `/api/chat/conversations/${this.activeConversation.id}/messages`,
                        formData,
                        { headers: { 'Content-Type': 'multipart/form-data' } }
                    );
                    const newMessage = res.data.data || res.data.message || res.data;
                    this.messages.push(newMessage);

                    // Cleanup
                    this.draft = '';
                    this.pendingAttachments.forEach(item => { if (item.previewUrl) URL.revokeObjectURL(item.previewUrl); });
                    this.pendingAttachments = [];
                    if (this.$refs.attachmentInput) this.$refs.attachmentInput.value = '';
                    this.replyTo = null;
                    this.scrollToBottom();

                    // Update conversation's last message in sidebar
                    const conv = this.conversations.find(c => c.id === this.activeConversation.id);
                    if (conv) { conv.updated_at = newMessage.created_at; conv.last_message = newMessage; }
                } catch (e) {
                    this.errorMessage = e.response?.data?.message || 'Failed to send message.';
                    console.error(e);
                } finally {
                    this.sendingMessage = false;
                }
            },
            
            sendTyping() {
                // Implement typing indicator if backend supports it
            },
            
            startEdit(message) {
                this.editingMessageId = message.id;
                this.editingDraft = message.content;
            },
            
            cancelEdit() {
                this.editingMessageId = null;
                this.editingDraft = '';
            },
            
            async saveEdit(message) {
                if (!this.editingDraft.trim()) return;
                
                try {
                    const res = await axios.put(`/api/chat/messages/${message.id}`, { content: this.editingDraft });
                    message.content = this.editingDraft;
                    message.edited_at = new Date().toISOString();
                    this.cancelEdit();
                } catch (e) {
                    this.errorMessage = 'Failed to edit message';
                }
            },
            
            async deleteMessage(message) {
                this.busyMessageId = message.id;
                try {
                    await axios.delete(`/api/chat/messages/${message.id}`);
                    this.messages = this.messages.filter(m => m.id !== message.id);
                } catch (e) {
                    this.errorMessage = 'Failed to delete message';
                } finally {
                    this.busyMessageId = null;
                }
            },
            
            setReply(message) {
                this.replyTo = message;
                this.$refs.composer.focus();
            },
            
            scrollToMessage(id) {
                // Logic to scroll to message
            },
            
            async togglePin() {
                try {
                    await axios.post(`/api/chat/conversations/${this.activeConversation.id}/pin`);
                    this.activeConversation.is_pinned = !this.activeConversation.is_pinned;
                } catch (e) {}
            },
            
            async toggleArchive() {
                try {
                    await axios.post(`/api/chat/conversations/${this.activeConversation.id}/archive`);
                    this.activeConversation = null;
                    this.poll();
                } catch (e) {}
            },
            
            openGroupSettings() {
                if (!this.activeConversation || this.activeConversation.type !== 'group') return;
                this.groupSettingsForm = {
                    name: this.activeConversation.name || '',
                    description: this.activeConversation.description || '',
                    privacy: this.activeConversation.privacy || 'private'
                };
                this.showGroupSettingsModal = true;
            },
            
            closeGroupSettings() {
                this.showGroupSettingsModal = false;
                this.showAddMember = false;
                this.errorMessage = '';
            },
            
            async createGroup() {
                if (!this.groupForm.name.trim()) {
                    this.groupError = 'Group name is required.';
                    return;
                }
                this.groupBusy = true;
                this.groupError = '';
                try {
                    const res = await axios.post('/api/chat/conversations', {
                        type: 'group',
                        name: this.groupForm.name,
                        description: this.groupForm.description || null,
                        privacy: this.groupForm.privacy,
                        member_ids: this.groupForm.member_ids,
                    });
                    const conv = res.data.data || res.data.conversation || res.data;
                    this.conversations.unshift(conv);
                    this.showGroupModal = false;
                    this.groupForm = { name: '', description: '', privacy: 'private', member_ids: [] };
                    this.groupCreateSearch = '';
                    this.selectConversation(conv);
                } catch (e) {
                    const msg = e.response?.data?.message || e.response?.data?.errors?.name?.[0] || 'Failed to create group.';
                    this.groupError = msg;
                    console.error(e);
                } finally {
                    this.groupBusy = false;
                }
            },

            async updateGroup() {
                this.groupBusy = true;
                this.errorMessage = '';
                try {
                    const res = await axios.put(`/api/chat/groups/${this.activeConversation.id}`, this.groupSettingsForm);
                    const updated = res.data.data || res.data;
                    this.activeConversation.name = updated.name || this.groupSettingsForm.name;
                    this.activeConversation.description = updated.description || this.groupSettingsForm.description;
                    this.activeConversation.privacy = updated.privacy || this.groupSettingsForm.privacy;
                    // Sync in sidebar list
                    const idx = this.conversations.findIndex(c => c.id === this.activeConversation.id);
                    if (idx !== -1) this.conversations[idx] = { ...this.conversations[idx], ...this.groupSettingsForm };
                } catch (e) {
                    this.errorMessage = e.response?.data?.message || 'Failed to update group.';
                } finally {
                    this.groupBusy = false;
                }
            },

            async deleteGroup() {
                this.confirm('Delete Group', 'Are you sure you want to delete this group? All messages will be lost.', async () => {
                    this.groupBusy = true;
                    try {
                        await axios.delete(`/api/chat/groups/${this.activeConversation.id}`);
                        this.conversations = this.conversations.filter(c => c.id !== this.activeConversation.id);
                        this.activeConversation = null;
                        this.closeGroupSettings();
                    } catch (e) {
                        this.errorMessage = e.response?.data?.message || 'Failed to delete group.';
                    } finally {
                        this.groupBusy = false;
                    }
                });
            },
            
            async leaveGroup() {
                this.confirm('Leave Group', 'Are you sure you want to leave this group?', async () => {
                    this.groupBusy = true;
                    try {
                        await axios.post(`/api/chat/groups/${this.activeConversation.id}/leave`);
                        this.conversations = this.conversations.filter(c => c.id !== this.activeConversation.id);
                        this.activeConversation = null;
                        this.closeGroupSettings();
                    } catch (e) {
                        this.errorMessage = 'Failed to leave group';
                    } finally {
                        this.groupBusy = false;
                    }
                });
            },
            
            async addGroupMember(userId) {
                this.groupBusy = true;
                try {
                    // Backend expects user_ids[] array
                    await axios.post(`/api/chat/groups/${this.activeConversation.id}/members`, { user_ids: [userId] });
                    // Refresh the conversation to get updated members
                    await this.poll();
                } catch (e) {
                    this.errorMessage = e.response?.data?.message || 'Failed to add member.';
                } finally {
                    this.groupBusy = false;
                }
            },

            async removeGroupMember(member) {
                // Backend POST /members/remove expects user_id (not member_id)
                const userId = member.user_id ?? member.id;
                try {
                    await axios.post(`/api/chat/groups/${this.activeConversation.id}/members/remove`, { user_id: userId });
                    this.activeConversation.active_members = this.activeConversation.active_members.filter(m => m.id !== member.id);
                } catch (e) {
                    this.errorMessage = e.response?.data?.message || 'Failed to remove member.';
                }
            },

            async updateGroupMemberRole(member, role) {
                // Backend PUT/POST members/role expects user_id (not member_id)
                const userId = member.user_id ?? member.id;
                try {
                    await axios.put(`/api/chat/groups/${this.activeConversation.id}/members/role`, { user_id: userId, role });
                    member.role = role;
                } catch (e) {
                    this.errorMessage = e.response?.data?.message || 'Failed to update role.';
                }
            },
            
            confirm(title, message, onConfirm) {
                this.confirmModal = { show: true, title, message, cancelText: 'Cancel', confirmText: 'Confirm', onConfirm: () => {
                    this.closeConfirm();
                    onConfirm();
                }};
            },
            
            closeConfirm() {
                this.confirmModal.show = false;
            },
            
            scrollToBottom() {
                setTimeout(() => {
                    if (this.$refs.messageScroller) {
                        this.$refs.messageScroller.scrollTop = this.$refs.messageScroller.scrollHeight;
                    }
                }, 100);
            },
            
            formatAttachmentUrl(path) {
                if (!path) return '';
                if (path.startsWith('http')) {
                    try {
                        const url = new URL(path);
                        if (url.hostname === window.location.hostname || url.hostname === 'localhost' || url.hostname === '127.0.0.1') {
                            return url.pathname + url.search;
                        }
                    } catch(e) {}
                    return path;
                }
                if (path.startsWith('/storage/')) return path;
                return `/storage/${path}`;
            },
            
            isImageAttachment(file) {
                if (file.mime) return file.mime.startsWith('image/');
                if (file.type) return file.type.startsWith('image/');
                const path = file.path || file.name || '';
                return /\.(jpg|jpeg|png|gif|webp)$/i.test(path);
            },
            
            getPreviewImageUrl(msg) {
                if (!msg || !msg.attachments || !msg.attachments.length) return null;
                const img = msg.attachments.find(a => this.isImageAttachment(a));
                return img ? this.formatAttachmentUrl(img.storage_path || img.path) : null;
            },
            
            initials(name) {
                if (!name) return '?';
                return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            },
            
            conversationInitials(conv) {
                if (!conv) return '?';
                if (conv.type === 'group') return this.initials(conv.name);
                // Backend returns activeMembers, not participants
                const other = conv.active_members?.find(m => Number(m.user_id) !== Number(this.currentUserId));
                return this.initials(other?.user?.name);
            },
            
            conversationPhoto(conv) {
                if (!conv) return null;
                if (conv.type === 'group') return conv.image_path || null;
                const other = conv.active_members?.find(m => Number(m.user_id) !== Number(this.currentUserId));
                return other?.user?.photo || null;
            },

            conversationTitle(conv) {
                if (!conv) return '';
                if (conv.type === 'group') return conv.name || 'Group';
                // Backend returns activeMembers, not participants
                const other = conv.active_members?.find(m => Number(m.user_id) !== Number(this.currentUserId));
                return other?.user?.name || 'Unknown User';
            },

            conversationSubtitle(conv) {
                if (!conv) return '';
                if (conv.type === 'group') return `${conv.active_members?.length || 0} members`;
                const other = conv.active_members?.find(m => Number(m.user_id) !== Number(this.currentUserId));
                const onlineUser = this.users.find(u => Number(u.id) === Number(other?.user_id));
                return onlineUser?.is_online ? 'Active now' : (onlineUser?.last_seen_label || 'Direct Message');
            },

            conversationLocation(conv) {
                if (!conv) return null;
                if (conv.type === 'group') return null;
                const other = conv.active_members?.find(m => Number(m.user_id) !== Number(this.currentUserId));
                const user = this.users.find(u => Number(u.id) === Number(other?.user_id));
                return user?.location || null;
            },

            senderLocation(message) {
                if (!message || !message.sender_id) return null;
                const user = this.users.find(u => Number(u.id) === Number(message.sender_id));
                return user?.location || null;
            },

            userLocation(userId) {
                if (!userId) return null;
                const user = this.users.find(u => Number(u.id) === Number(userId));
                return user?.location || null;
            },

            lastTime(item) {
                if (!item) return '';
                const dateStr = item.last_message_at || item.updated_at || item.created_at;
                if (!dateStr) return '';
                const date = new Date(dateStr);
                if (isNaN(date)) return '';
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            },

            lastMessageObj(conv) {
                if (!conv) return null;
                return conv.last_message || (Array.isArray(conv.messages) ? conv.messages[0] : null);
            },

            lastPreview(conv) {
                const last = this.lastMessageObj(conv);
                if (!last) return 'No messages yet';
                if (last.content) return last.content;
                if (this.getPreviewImageUrl(last)) return 'Photo';
                if (last.attachments?.length) return 'Attachment';
                return 'Message';
            },

            isOnline(conv) {
                if (!conv || conv.type === 'group') return false;
                const other = conv.active_members?.find(m => Number(m.user_id) !== Number(this.currentUserId));
                if (!other) return false;
                const liveUser = this.users.find(u => Number(u.id) === Number(other.user_id));
                return liveUser?.is_online === true;
            },
            
            exportUsersToCSV() {
                const rows = [
                    ['Name', 'Email', 'Location', 'Status', 'Today Orders', 'Today Revenue', 'Last Seen']
                ];
                
                this.visibleUsers.forEach(u => {
                    rows.push([
                        u.name || '',
                        u.email || '',
                        u.location || '',
                        u.is_online ? 'Online' : 'Offline',
                        u.today_orders || 0,
                        u.today_revenue || 0,
                        u.last_seen_label || ''
                    ].map(v => `"${String(v).replace(/"/g, '""')}"`).join(','));
                });
                
                const csvContent = "data:text/csv;charset=utf-8," + rows.join("\n");
                const encodedUri = encodeURI(csvContent);
                const link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", `live_users_${new Date().toISOString().split('T')[0]}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        };
    }
</script>
@endsection
