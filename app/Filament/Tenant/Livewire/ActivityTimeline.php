<?php

namespace App\Filament\Tenant\Livewire;

use App\Models\Activity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityTimeline extends Component
{
    use WithPagination;

    public Model $subject;

    public function mount(Model $subject): void
    {
        $this->subject = $subject;
    }

    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        return Activity::query()
            ->forSubject($this->subject)
            ->with('causer')
            ->latest('created_at')
            ->paginate(10);
    }

    public function render(): View
    {
        return view('filament.tenant.livewire.activity-timeline');
    }
}
