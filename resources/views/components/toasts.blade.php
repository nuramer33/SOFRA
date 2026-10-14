{{-- Toast stack. Server flash messages are pushed in on page load. --}}
<div x-data x-init="@if (session('status')) $store.toast.push(@js(session('status'))) @endif"
     class="pointer-events-none fixed inset-x-0 top-3 z-50 flex flex-col items-center gap-2 px-4 lg:top-6"
     aria-live="polite">
    <template x-for="toast in $store.toast.items" :key="toast.id">
        <div x-transition:enter="transition duration-500 ease-(--ease-spring)"
             x-transition:enter-start="-translate-y-4 scale-95 opacity-0"
             x-transition:leave="transition duration-200"
             x-transition:leave-end="-translate-y-2 opacity-0"
             class="pointer-events-auto flex max-w-md items-center gap-3 rounded-full py-2.5 ps-3 pe-5 text-sm font-medium shadow-lg ring-1"
             :class="toast.tone === 'error' ? 'bg-danger text-white ring-danger' : 'bg-ink text-paper ring-ink'"
             role="status">
            <span class="grid size-6 place-items-center rounded-full"
                  :class="toast.tone === 'error' ? 'bg-white/20' : 'bg-olive'">
                <x-icon name="check" class="size-3.5" x-show="toast.tone !== 'error'" />
                <span x-show="toast.tone === 'error'" x-cloak>!</span>
            </span>
            <span x-text="toast.message"></span>
        </div>
    </template>
</div>
