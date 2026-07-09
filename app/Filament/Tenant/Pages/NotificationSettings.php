<?php

namespace App\Filament\Tenant\Pages;

use App\Models\NotificationSetting;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class NotificationSettings extends Page
{
    use HasPageShield;

    protected string $view = 'filament.tenant.pages.notification-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $title = 'Notifications';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(): void
    {
        $state = [];

        foreach (NotificationSetting::all() as $setting) {
            $state["{$setting->event}__{$setting->channel}"] = $setting->enabled;
        }

        $this->form->fill($state);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->submit('save'),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $sections = [];

        foreach (NotificationSetting::events() as $eventKey => $eventLabel) {
            $fields = [];

            foreach (NotificationSetting::channels() as $channelKey => $channelLabel) {
                $fields[] = Toggle::make("{$eventKey}__{$channelKey}")
                    ->label($channelLabel);
            }

            $sections[] = Section::make($eventLabel)
                ->schema($fields)
                ->columns(count($fields));
        }

        $sections[] = Actions::make($this->getFormActions());

        return $schema
            ->components($sections)
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach (NotificationSetting::events() as $eventKey => $eventLabel) {
            foreach (NotificationSetting::channels() as $channelKey => $channelLabel) {
                NotificationSetting::updateOrCreate(
                    ['event' => $eventKey, 'channel' => $channelKey],
                    ['enabled' => $state["{$eventKey}__{$channelKey}"] ?? false],
                );
            }
        }

        Notification::make()
            ->title('Notification settings saved')
            ->success()
            ->send();
    }
}
