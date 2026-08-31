<?php

namespace App\Filament\Central\Pages;

use App\Models\MailSetting;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Throwable;
use UnitEnum;

class NotificationConfiguration extends Page
{
    use HasPageShield;

    protected string $view = 'filament.central.pages.notification-configuration';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $title = 'Notifications Configuration';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(): void
    {
        $setting = MailSetting::current();

        $this->form->fill([
            'mailer' => $setting->mailer ?? 'log',
            'host' => $setting->host,
            'port' => $setting->port,
            'username' => $setting->username,
            'encryption' => $setting->encryption,
            'from_address' => $setting->from_address ?? config('mail.from.address'),
            'from_name' => $setting->from_name ?? config('mail.from.name'),
            'test_email' => auth()->user()?->email,
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->submit('save'),
            Action::make('sendTestEmail')
                ->label('Send Test Email')
                ->color('gray')
                ->action(fn () => $this->sendTestEmail()),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mail Driver')
                    ->schema([
                        Select::make('mailer')
                            ->label('Mailer')
                            ->options([
                                'log' => 'Log (does not send real emails)',
                                'smtp' => 'SMTP',
                            ])
                            ->live()
                            ->required(),
                    ]),
                Section::make('SMTP Settings')
                    ->schema([
                        TextInput::make('host')
                            ->label('Host')
                            ->required(fn (Get $get): bool => $get('mailer') === 'smtp'),
                        TextInput::make('port')
                            ->label('Port')
                            ->numeric()
                            ->required(fn (Get $get): bool => $get('mailer') === 'smtp'),
                        TextInput::make('username')
                            ->label('Username'),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->placeholder('Leave blank to keep the current password'),
                        Select::make('encryption')
                            ->label('Encryption')
                            ->options([
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                                '' => 'None',
                            ])
                            ->native(false),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('mailer') === 'smtp'),
                Section::make('From Address')
                    ->schema([
                        TextInput::make('from_address')
                            ->label('From Address')
                            ->email()
                            ->required(),
                        TextInput::make('from_name')
                            ->label('From Name')
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Test Configuration')
                    ->schema([
                        TextInput::make('test_email')
                            ->label('Send Test Email To')
                            ->email()
                            ->dehydrated(false),
                    ]),
                Actions::make($this->getFormActions()),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $setting = MailSetting::current();
        $setting->fill($state);
        $setting->save();
        $setting->applyToConfig();

        Notification::make()
            ->title('Notification configuration saved')
            ->success()
            ->send();
    }

    public function sendTestEmail(): void
    {
        $testEmail = $this->data['test_email'] ?? null;

        if (blank($testEmail)) {
            Notification::make()
                ->title('Enter an email address to send the test to')
                ->danger()
                ->send();

            return;
        }

        $state = $this->form->getState();

        $setting = MailSetting::current();
        $setting->fill($state);
        $setting->applyToConfig();

        try {
            Mail::raw(
                'This is a test email from your Task Management System notification configuration.',
                fn ($message) => $message->to($testEmail)->subject('Test Email')
            );

            Notification::make()
                ->title("Test email sent to {$testEmail}")
                ->body($setting->mailer === 'log' ? 'Mailer is set to "log" — check storage/logs/laravel.log, no real email was sent.' : null)
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Failed to send test email')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
