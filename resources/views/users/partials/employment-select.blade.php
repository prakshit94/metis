{{-- Presentation only: retain the existing form model and option values. --}}
<div class="position-relative" x-data="{ open: false, search: '' }"
     @click.outside="open = false"
     @keydown.escape.stop.prevent="open = false; $refs.trigger.focus()">
    <button id="{{ $fieldId }}" type="button" class="form-select form-select-sm text-start"
            x-ref="trigger" aria-controls="{{ $fieldId }}Options" :aria-expanded="open"
            @click="open = !open; if (open) { search = ''; $nextTick(() => $refs.search.focus()); }"
            @keydown.arrow-down.stop.prevent="open = true; search = ''; $nextTick(() => $refs.search.focus())"
            x-text="({{ $options }}).find(option => String(option.{{ $valueField }}) === String({{ $model }}))?.name || ({{ $model }} ? String({{ $model }}) : 'None')">
        None
    </button>
    <div id="{{ $fieldId }}Options" x-show="open" x-cloak
         class="dropdown-menu show w-100 shadow p-2" style="z-index: 1056;"
         @keydown.arrow-down.stop.prevent="const buttons = [...$el.querySelectorAll('button')].filter(button => button.offsetParent !== null); const index = buttons.indexOf($event.target); buttons[Math.min(index + 1, buttons.length - 1)]?.focus()"
         @keydown.arrow-up.stop.prevent="const buttons = [...$el.querySelectorAll('button')].filter(button => button.offsetParent !== null); const index = buttons.indexOf($event.target); if (index <= 0) $refs.search.focus(); else buttons[index - 1]?.focus()">
        <input type="search" class="form-control form-control-sm mb-2" x-ref="search" x-model="search"
               aria-label="Search {{ $label }}" placeholder="Search {{ strtolower($label) }}..."
               autocomplete="off" @keydown.enter.prevent>
        <div style="max-height: 220px; overflow-y: auto;" aria-label="{{ $label }} options">
            <button type="button" class="dropdown-item rounded"
                    x-show="!search || 'none'.includes(search.trim().toLowerCase())"
                    @click="{{ $model }} = ''; open = false; $refs.trigger.focus()">None</button>
            <template x-for="option in ({{ $options }}).filter(option => String(option.name || '').toLowerCase().includes(search.trim().toLowerCase()))" :key="option.{{ $keyField }}">
                <button type="button" class="dropdown-item rounded text-wrap"
                        :class="{ 'active': String(option.{{ $valueField }}) === String({{ $model }}) }"
                        @click="{{ $model }} = option.{{ $valueField }}; open = false; $refs.trigger.focus()"
                        x-text="option.name"></button>
            </template>
            <p class="small text-muted px-2 mb-0" role="status"
               x-show="search.trim() && !'none'.includes(search.trim().toLowerCase()) && !({{ $options }}).some(option => String(option.name || '').toLowerCase().includes(search.trim().toLowerCase()))">No matches found</p>
        </div>
    </div>
</div>
