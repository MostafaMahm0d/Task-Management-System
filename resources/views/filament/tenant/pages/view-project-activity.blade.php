<x-filament-panels::page>
    @if ($record instanceof \App\Models\Project)
        <div class="mb-6">
            <h2 class="mb-3 text-base font-semibold text-gray-950 dark:text-white">
                Tasks in this project
            </h2>

            @php $tasks = $record->tasks()->latest()->get(); @endphp

            @if ($tasks->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">No tasks yet.</p>
            @else
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($tasks as $task)
                        <a
                            href="{{ \App\Filament\Tenant\Resources\Activities\ActivityResource::getUrl('view-task', ['record' => $task]) }}"
                            class="block rounded-xl border border-gray-200 p-4 transition hover:border-primary-400 dark:border-white/10 dark:hover:border-primary-400"
                        >
                            <p class="font-semibold text-gray-950 dark:text-white">{{ $task->title }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $task->status?->name ?? 'No status' }} · {{ $task->assignee?->name ?? 'Unassigned' }}
                            </p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <h2 class="mb-3 text-base font-semibold text-gray-950 dark:text-white">
            Timeline
        </h2>
    @endif

    <livewire:activity-timeline :subject="$record" :key="'activity-timeline-'.$record->getKey()" />
</x-filament-panels::page>
