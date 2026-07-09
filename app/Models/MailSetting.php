<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

#[Fillable(['mailer', 'host', 'port', 'username', 'password', 'encryption', 'from_address', 'from_name'])]
class MailSetting extends Model
{
    use CentralConnection;

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
        ];
    }

    public static function current(): self
    {
        return static::query()->first() ?? new self;
    }

    public function applyToConfig(): void
    {
        config([
            'mail.default' => $this->mailer,
            'mail.mailers.smtp.host' => $this->host,
            'mail.mailers.smtp.port' => $this->port,
            'mail.mailers.smtp.username' => $this->username,
            'mail.mailers.smtp.password' => $this->password,
            'mail.mailers.smtp.encryption' => $this->encryption ?: null,
            'mail.from.address' => $this->from_address ?: config('mail.from.address'),
            'mail.from.name' => $this->from_name ?: config('mail.from.name'),
        ]);
    }
}
