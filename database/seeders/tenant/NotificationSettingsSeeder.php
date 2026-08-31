<?php

namespace Database\Seeders\tenant;

use App\Models\NotificationSetting;
use Illuminate\Database\Seeder;

class NotificationSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (array_keys(NotificationSetting::events()) as $event) {
            foreach (array_keys(NotificationSetting::channels()) as $channel) {
                NotificationSetting::firstOrCreate([
                    'event' => $event,
                    'channel' => $channel,
                ], [
                    'enabled' => true,
                ]);
            }
        }
    }
}
