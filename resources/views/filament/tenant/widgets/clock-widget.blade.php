<x-filament-widgets::widget>
    <x-filament::section>
        <div
            x-data="{ now: new Date() }"
            x-init="setInterval(() => now = new Date(), 1000)"
            class="flex h-full flex-col items-center justify-center gap-1 text-center"
        >
            <div
                class="font-mono text-2xl font-bold tabular-nums text-gray-950 dark:text-white"
                x-text="now.toLocaleTimeString()"
            ></div>

            <div
                class="text-sm font-medium text-gray-500 dark:text-gray-400"
                x-text="now.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })"
            ></div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
