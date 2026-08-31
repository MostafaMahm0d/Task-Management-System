<?php

namespace App\Filament\Tenant\Livewire;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Livewire\DatabaseNotifications as BaseDatabaseNotifications;
use Livewire\Attributes\On;

class DatabaseNotifications extends BaseDatabaseNotifications
{
    public int $lastSeenUnreadCount = 0;

    public ?Carbon $lastSoundPlayedAt = null;

    public function mount(): void
    {
        $this->lastSeenUnreadCount = parent::getUnreadNotificationsCount();
        $this->lastSoundPlayedAt = now();
    }

    public function getUnreadNotificationsCount(): int
    {
        $count = parent::getUnreadNotificationsCount();

        if ($count > $this->lastSeenUnreadCount) {
            $this->playSound();
        } elseif ($count > 0 && $this->lastSoundPlayedAt?->diffInMinutes(now()) >= 60) {
            // Still unread an hour after the last chime — remind again.
            $this->playSound();
        }

        $this->lastSeenUnreadCount = $count;

        return $count;
    }

    private function playSound(): void
    {
        $this->dispatch('notification-received');
        $this->lastSoundPlayedAt = now();
    }

    public function clearNotificationsAction(): Action
    {
        return parent::clearNotificationsAction()->hidden();
    }

    public function clearNotifications(): void
    {
        // Notifications are an audit trail of task activity — users may not delete them.
    }

    #[On('notificationClosed')]
    public function removeNotification(string $id): void
    {
        // Notifications are an audit trail of task activity — users may not delete them,
        // individually or in bulk. The per-notification close button is hidden via CSS,
        // but this blocks the underlying delete even if it's invoked directly.
    }
}
