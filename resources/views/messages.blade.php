@extends('layouts.app')

@section('title', 'Messages & Communication')
@section('page', 'messages')

@section('content')
<div class="messages-page" x-data="messagesComponent" x-init="init()">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold"><i class="bi bi-chat-dots-fill text-primary me-2"></i>Messages</h1>
            <p class="text-muted mb-0 small">Real-time communication center</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-secondary d-lg-none" @click="toggleSidebar()">
                <i class="bi bi-list me-2"></i>Conversations
            </button>
            <button type="button" class="btn btn-outline-secondary" @click="markAllRead()">
                <i class="bi bi-check-all me-2"></i>Mark All Read
            </button>
            <button type="button" class="btn btn-outline-primary" @click="createGroup()">
                <i class="bi bi-people-fill me-2"></i>New Group
            </button>
            <button type="button" class="btn btn-primary" @click="newConversation()">
                <i class="bi bi-plus-lg me-2"></i>New Message
            </button>
        </div>
    </div>

    {{-- Messages Container --}}
    <div class="messages-container">
        <div class="messages-layout">

            {{-- Conversations Sidebar --}}
            <div class="messages-sidebar" :class="{ 'mobile-show': sidebarVisible }">

                {{-- Sidebar Header --}}
                <div class="messages-header">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="header-title mb-0">Conversations</h5>
                        <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill" x-text="conversations.length + ' chats'"></span>
                    </div>
                    <div class="search-container mb-3">
                        <input type="search"
                               class="form-control"
                               placeholder="Search conversations..."
                               x-model="searchQuery"
                               @input="filterConversations()">
                        <i class="bi bi-search search-icon"></i>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary btn-sm flex-fill" @click="newConversation()">
                            <i class="bi bi-plus-lg me-1"></i>New Chat
                        </button>
                        <button class="btn btn-outline-primary btn-sm flex-fill" @click="createGroup()">
                            <i class="bi bi-people-fill me-1"></i>New Group
                        </button>
                    </div>
                </div>
                                
                                <!-- Conversations List -->
                                <div class="conversations-list">
                                    <template x-for="conversation in filteredConversations" :key="conversation.id">
                                        <a href="#" class="conversation-item" 
                                           :class="{ 
                                               'active': selectedConversation?.id === conversation.id,
                                               'unread': conversation.unread > 0 
                                           }"
                                           @click.prevent="selectConversation(conversation)">
                                            <div class="conversation-avatar">
                                                <img :src="conversation.avatar" 
                                                     :alt="conversation.name"
                                                     :class="{ 'online': conversation.online }">
                                                <div class="online-indicator" x-show="conversation.online"></div>
                                            </div>
                                            <div class="conversation-info">
                                                <div class="conversation-header mb-1">
                                                    <div class="d-flex align-items-center flex-wrap gap-1">
                                                        <h6 class="conversation-name mb-0" x-text="conversation.name"></h6>
                                                        <small class="text-muted" style="font-size: 0.75rem;" x-show="conversation.location">
                                                            <i class="bi bi-geo-alt"></i> <span x-text="conversation.location"></span>
                                                        </small>
                                                    </div>
                                                    <span class="conversation-time" x-text="conversation.lastMessageTime"></span>
                                                </div>
                                                <p class="conversation-preview" x-text="conversation.lastMessage"></p>
                                                <div class="conversation-footer">
                                                    <span class="conversation-type" x-text="conversation.type"></span>
                                                    <span class="unread-badge" 
                                                          x-show="conversation.unread > 0" 
                                                          x-text="conversation.unread"></span>
                                                </div>
                                            </div>
                                        </a>
                                    </template>
                                    
                                    <!-- Empty state for conversations -->
                                    <div x-show="filteredConversations.length === 0" class="empty-conversations">
                                        <i class="bi bi-chat-dots"></i>
                                        <p>No conversations found</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Chat Area -->
                            <div class="chat-area">
                                <!-- Active Chat -->
                                <div class="active-chat" x-show="selectedConversation">
                                    <!-- Chat Header -->
                                    <div class="chat-header">
                                        <div class="chat-user-info">
                                            <button class="btn btn-link d-lg-none me-2 p-0" @click="sidebarVisible = !sidebarVisible">
                                                <i class="bi bi-arrow-left fs-5"></i>
                                            </button>
                                            <div class="chat-avatar-container">
                                                <img :src="selectedConversation?.avatar" 
                                                     class="chat-avatar"
                                                     :alt="selectedConversation?.name">
                                                <div class="online-indicator" x-show="selectedConversation?.online"></div>
                                            </div>
                                            <div class="chat-details">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <h6 class="chat-name mb-0" x-text="selectedConversation?.name"></h6>
                                                    <small class="text-muted fw-normal" style="font-size: 0.8rem;" x-show="selectedConversation?.location">
                                                        <i class="bi bi-geo-alt"></i> <span x-text="selectedConversation?.location"></span>
                                                    </small>
                                                </div>
                                                <p class="chat-status mb-0" x-show="selectedConversation?.online">● Online</p>
                                                <p class="chat-status mb-0" x-show="!selectedConversation?.online" x-text="`Last seen ${selectedConversation?.lastSeen}`"></p>
                                            </div>
                                        </div>
                                        <div class="chat-actions">
                                            <button class="btn" @click="videoCall()" title="Video Call">
                                                <i class="bi bi-camera-video"></i>
                                            </button>
                                            <button class="btn" @click="voiceCall()" title="Voice Call">
                                                <i class="bi bi-telephone"></i>
                                            </button>
                                            <div class="dropdown">
                                                <button class="btn dropdown-toggle" data-bs-toggle="dropdown" title="More Options">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="#" @click.prevent="muteConversation()">
                                                        <i class="bi bi-bell-slash me-2"></i>Mute notifications
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="#" @click.prevent="archiveConversation()">
                                                        <i class="bi bi-archive me-2"></i>Archive chat
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#" @click.prevent="deleteConversation()">
                                                        <i class="bi bi-trash me-2"></i>Delete chat
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Messages Area -->
                                    <div class="chat-messages" id="chatMessages">
                                        <!-- Date Separator -->
                                        <div class="date-separator">
                                            <span class="date-label">Today</span>
                                        </div>
                                        
                                        <!-- Messages -->
                                        <div class="message-group">
                                            <template x-for="message in currentMessages" :key="message.id">
                                                <div class="message" :class="{ 'own-message': message.sent }">
                                                    <img x-show="!message.sent" 
                                                         :src="selectedConversation?.avatar" 
                                                         class="message-avatar" 
                                                         :alt="selectedConversation?.name">
                                                    <div class="message-bubble">
                                                        <div class="message-content">
                                                            <p x-text="message.text"></p>
                                                        </div>
                                                        <div class="message-info">
                                                            <span class="message-time" x-text="message.time"></span>
                                                            <span x-show="message.sent" class="message-status">
                                                                <i class="bi bi-check-all" x-show="message.read"></i>
                                                                <i class="bi bi-check" x-show="!message.read"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        
                                        <!-- Typing Indicator -->
                                        <div class="typing-indicator" x-show="isTyping">
                                            <img :src="selectedConversation?.avatar" 
                                                 class="typing-avatar" 
                                                 :alt="selectedConversation?.name">
                                            <div class="typing-content">
                                                <div class="typing-dots">
                                                    <div class="dot"></div>
                                                    <div class="dot"></div>
                                                    <div class="dot"></div>
                                                </div>
                                                <span class="typing-text">typing...</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Message Input -->
                                    <div class="chat-input">
                                        <div class="input-container">
                                            <div class="input-actions">
                                                <button class="btn" @click="toggleAttachment()" title="Attach file">
                                                    <i class="bi bi-paperclip"></i>
                                                </button>
                                            </div>
                                            <div class="message-input">
                                                <textarea class="form-control" 
                                                          placeholder="Type a message..." 
                                                          rows="1"
                                                          x-model="newMessage"
                                                          @keydown.enter.prevent="sendMessage()"
                                                          @input="handleTyping(); autoResize($event)"
                                                          style="resize: none;"></textarea>
                                            </div>
                                            <div class="input-actions">
                                                <button class="btn" @click="toggleEmojiPicker()" title="Add emoji">
                                                    <i class="bi bi-emoji-smile"></i>
                                                </button>
                                                <button class="btn btn-primary" @click="sendMessage()" :disabled="!newMessage.trim()" title="Send message">
                                                    <i class="bi bi-send"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Emoji Picker -->
                                        <div class="emoji-picker" x-show="showEmojiPicker" x-transition>
                                            <div class="emoji-grid">
                                                <template x-for="emoji in emojis" :key="emoji">
                                                    <button class="emoji-btn" @click="addEmoji(emoji)" x-text="emoji"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty Chat State -->
                                <div class="empty-chat" x-show="!selectedConversation">
                                    <div class="empty-icon">
                                        <i class="bi bi-chat-dots"></i>
                                    </div>
                                    <h5 class="empty-text">Select a conversation to start messaging</h5>
                                    <p class="text-muted mb-4">Choose from your existing conversations or start a new one</p>
                                    <button class="btn btn-primary" @click="newConversation()">
                                        <i class="bi bi-plus-lg me-2"></i>Start New Conversation
                                    </button>
                                </div>
                            </div>

                        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="./scripts/components/messages.js"></script>

<script type="module" src="./scripts/main.js"></script>
@endpush
