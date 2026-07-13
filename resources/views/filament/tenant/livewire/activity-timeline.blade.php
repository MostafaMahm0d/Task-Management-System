<div class="fi-ac space-y-1">
    @forelse ($this->activities as $activity)
        <div class="flex items-start gap-3 rounded-lg px-3 py-2 hover:bg-gray-50 dark:hover:bg-white/5">
            <x-dynamic-component
                :component="$activity->eventIcon()"
                @class([
                    'h-6 w-6 shrink-0 mt-0.5',
                    match ($activity->eventColor()) {
                        'success' => 'text-success-500',
                        'warning' => 'text-warning-500',
                        'danger' => 'text-danger-500',
                        'info' => 'text-info-500',
                        default => 'text-gray-400',
                    },
                ])
            />

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-gray-950 dark:text-white">
                    {{ $activity->feedSentence() }}
                </p>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $activity->created_at->diffForHumans() }}
                </p>

                @if ($lines = $activity->changeSummary())
                    <ul class="mt-1 space-y-0.5 text-xs text-gray-500 dark:text-gray-400">
                        @foreach ($lines as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @empty
        <p class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
            No activity recorded yet.
        </p>
    @endforelse

    <div class="pt-2">
        {{ $this->activities->links() }}
    </div>
</div>
