<div x-data="{ open: false }" class="position-fixed bottom-0 end-0 m-4" style="z-index: 9999;">
    <!-- Toggle Button -->
    <button @click="open = !open" 
        class="btn btn-primary rounded-circle shadow-lg d-flex align-items-center justify-content-center border-0 text-white"
        style="width: 56px; height: 56px; transition: transform 0.2s;"
        onmouseover="this.style.transform='scale(1.05)'"
        onmouseout="this.style.transform='scale(1)'">
        <i :class="open ? 'bi bi-x-lg fs-4' : 'bi bi-chat-dots-fill fs-4'"></i>
    </button>

    <!-- Chat Window -->
    <div x-show="open" x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-8"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-8"
        class="position-absolute bottom-100 end-0 mb-3"
        style="width: 350px; height: 500px; max-width: calc(100vw - 2rem); transform-origin: bottom right;">
        
        <div class="bg-body border shadow-lg rounded-4 overflow-hidden d-flex flex-column w-100 h-100">
            <!-- Header -->
            <div class="p-3 bg-primary text-white">
                <h3 class="fw-bold fs-5 mb-0">Team Chat</h3>
                <p class="small opacity-75 mt-1 mb-0">Open conversations, groups, and real-time updates.</p>
            </div>

            <!-- Messages -->
            <div class="flex-grow-1 overflow-y-auto p-3 bg-body-tertiary d-flex flex-column gap-3">
                <div class="d-flex align-items-start gap-2">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0" style="width: 32px; height: 32px; font-size: 10px;">AI</div>
                    <div class="bg-body p-2 px-3 shadow-sm border small" style="border-radius: 1rem; border-top-left-radius: 0;">
                        Your messaging module is ready. Open the full chat workspace to start a private chat or create a group.
                    </div>
                </div>
            </div>

            <!-- Input -->
            <div class="p-3 bg-body border-top">
                <a href="<?php echo e(route('chat.index')); ?>"
                    class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 rounded-pill py-2 fw-bold shadow-sm">
                    Open Team Chat
                    <i class="bi bi-arrow-right-short fs-5"></i>
                </a>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/user/metis/resources/views/partials/chat_widget.blade.php ENDPATH**/ ?>