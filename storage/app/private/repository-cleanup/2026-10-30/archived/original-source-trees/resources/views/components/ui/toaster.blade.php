@props(['position' => 'top-center'])

@php
    $classes = [
        'top-left'      => 'top-4 left-4',
        'top-center'    => 'top-4 left-1/2 -translate-x-1/2',
        'top-right'     => 'top-4 right-4',
        'bottom-left'   => 'bottom-4 left-4',
        'bottom-center' => 'bottom-4 left-1/2 -translate-x-1/2',
        'bottom-right'  => 'bottom-4 right-4',
    ][$position] ?? 'bottom-4 right-4';
@endphp

<div x-data="{ 
        notifications: [],
        add(type, message) {
            const id = Date.now();
            this.notifications.push({ id, type, message, show: false });
            setTimeout(() => { 
                const note = this.notifications.find(n => n.id === id);
                if(note) note.show = true; 
            }, 50);
            const timeout = type === 'error' ? 15000 : 5000;
            setTimeout(() => { this.dismiss(id) }, timeout);
        },
        dismiss(id) {
            const note = this.notifications.find(n => n.id === id);
            if (note) {
                note.show = false;
                setTimeout(() => {
                    this.notifications = this.notifications.filter(n => n.id !== id);
                }, 300);
            }
        }
    }" x-init="
        $nextTick(() => {
            const params = new URLSearchParams(window.location.search);
            if (params.has('success')) {
                add('success', params.get('success'));
                params.delete('success');
            }
            if (params.has('error')) {
                add('error', params.get('error'));
                params.delete('error');
            }
            if (params.has('warning')) {
                add('warning', params.get('warning'));
                params.delete('warning');
            }
            if (params.has('info')) {
                add('info', params.get('info'));
                params.delete('info');
            }
            if (params.toString().length > 0) {
                const newUrl = window.location.pathname + '?' + params.toString();
                window.history.replaceState({}, document.title, newUrl);
            } else {
                window.history.replaceState({}, document.title, window.location.pathname);
            }

            @if(session('success')) add('success', {!! \Illuminate\Support\Js::from(session('success')) !!}); @endif
            @if(session('error')) add('error', {!! \Illuminate\Support\Js::from(session('error')) !!}); @endif
            @if(session('warning')) add('warning', {!! \Illuminate\Support\Js::from(session('warning')) !!}); @endif
            @if(session('info')) add('info', {!! \Illuminate\Support\Js::from(session('info')) !!}); @endif
            @if($errors->any()) add('error', {!! \Illuminate\Support\Js::from($errors->first()) !!}); @endif
        });
        
        window.addEventListener('notify', event => {
            add(event.detail.type || 'info', event.detail.message);
        });
    " class="fixed {{ $classes }} z-[10000] flex flex-col gap-2 w-full max-w-sm pointer-events-none">
    <template x-for="note in notifications" :key="note.id">
        <div x-show="note.show" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="pointer-events-auto relative w-full overflow-hidden rounded-xl border p-4 shadow-lg backdrop-blur-xl"
            :class="{
                'bg-background/80 border-border': true,
                'border-emerald-500/50 bg-emerald-500/10': note.type === 'success',
                'border-red-500/50 bg-red-500/10': note.type === 'error',
                'border-amber-500/50 bg-amber-500/10': note.type === 'warning',
                'border-blue-500/50 bg-blue-500/10': note.type === 'info',
            }">
            <div class="flex items-start gap-4">
                <div class="shrink-0">
                    <template x-if="note.type === 'success'">
                        <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                    <template x-if="note.type === 'error'">
                        <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                    <template x-if="note.type === 'warning'">
                        <svg class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </template>
                    <template x-if="note.type === 'info'">
                        <svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-foreground" x-text="note.message"></p>
                </div>
                <button @click="dismiss(note.id)"
                    class="shrink-0 rounded-lg p-1 text-muted-foreground/50 hover:bg-muted hover:text-foreground transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </template>
</div>
