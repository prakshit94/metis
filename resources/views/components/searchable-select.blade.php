@props(['model', 'label', 'small' => false, 'width' => null])
{{-- Keep the native select as the source of values, validation and change events. --}}
<div class="position-relative w-100" @if($width) style="max-width: {{ $width }};" @endif
     data-searchable-select
     x-data="{
        open: false, search: '', options: [], selectedText: '', disabled: false,
        validationMessage: '', stopObserving: null,
        init() {
            this.$nextTick(() => {
                this.sync();
                const observer = new MutationObserver(() => this.sync());
                observer.observe(this.$refs.native, { childList: true, subtree: true, attributes: true, characterData: true });
                this.stopObserving = () => observer.disconnect();
            });
        },
        destroy() { if (this.stopObserving) this.stopObserving(); },
        sync() {
            const select = this.$refs.native;
            if (!select) return;
            this.disabled = select.disabled;
            this.options = Array.from(select.options).map(option => ({
                value: option.value, text: option.textContent, disabled: option.disabled,
                selected: option.selected
            }));
            this.selectedText = select.selectedOptions[0]?.textContent || 'Select an option';
            if (this.disabled) this.open = false;
        },
        choose(option) {
            if (this.disabled || option.disabled) return;
            const select = this.$refs.native;
            select.value = option.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            this.sync();
            this.validationMessage = '';
            this.open = false;
            this.$refs.trigger.focus();
        },
        showOptions() {
            if (this.disabled) return;
            this.open = !this.open;
            if (this.open) { this.search = ''; this.$nextTick(() => this.$refs.search.focus()); }
        }
     }"
     x-effect="{{ $model }}; $nextTick(() => sync())"
     @click.outside="open = false"
     @focusout="if (!$el.contains($event.relatedTarget)) open = false"
     @keydown.escape="if (open) { $event.stopPropagation(); $event.preventDefault(); open = false; $refs.trigger.focus(); }">
    {{ $slot }}
    <button type="button" @class(['form-select text-start', 'form-select-sm' => $small])
            x-ref="trigger" aria-label="{{ $label }}" :title="selectedText"
            :aria-expanded="open" :disabled="disabled" @click="showOptions()"
            @keydown.arrow-down.stop.prevent="if (!open) showOptions()"
            x-text="selectedText"></button>
    <div class="dropdown-menu show w-100 shadow p-2" x-show="open" x-cloak style="z-index: 1056;"
         @keydown.arrow-down.stop.prevent="const choices = [...$el.querySelectorAll('button:not(:disabled)')].filter(button => button.offsetParent !== null); const index = choices.indexOf($event.target); choices[Math.min(index + 1, choices.length - 1)]?.focus()"
         @keydown.arrow-up.stop.prevent="const choices = [...$el.querySelectorAll('button:not(:disabled)')].filter(button => button.offsetParent !== null); const index = choices.indexOf($event.target); if (index <= 0) $refs.search.focus(); else choices[index - 1]?.focus()">
        <input type="search" class="form-control form-control-sm mb-2" x-ref="search" x-model="search"
               aria-label="Search {{ $label }}" placeholder="Search {{ strtolower($label) }}..."
               autocomplete="off" @keydown.enter.prevent>
        <div style="max-height: 220px; overflow-y: auto;">
            <template x-for="(option, index) in options.filter(option => option.text.toLowerCase().includes(search.trim().toLowerCase()))" :key="index">
                <button type="button" class="dropdown-item rounded text-wrap" :disabled="option.disabled"
                        :class="{ active: option.selected }" @click="choose(option)" x-text="option.text"></button>
            </template>
            <p class="small text-muted px-2 mb-0" role="status"
               x-show="!options.some(option => option.text.toLowerCase().includes(search.trim().toLowerCase()))">No matches found</p>
        </div>
    </div>
    <p class="small text-danger mt-1 mb-0" role="alert" x-show="validationMessage" x-text="validationMessage" x-cloak></p>
</div>
