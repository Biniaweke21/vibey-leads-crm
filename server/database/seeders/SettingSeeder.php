<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::put('company', [
            'name' => 'Vibey World',
            'industry' => 'Software & Digital Services',
            'website' => 'https://vibeyworlds.com',
            'address' => 'Addis Ababa, Ethiopia',
            'phone' => '+251 91 000 0000',
            'email' => 'info@vibeyworlds.com',
            'logo_url' => null,
            'primary_color' => '#5ba7ff',
        ]);

        Setting::put('notifications', [
            'new_leads' => true,
            'invoice_paid' => true,
            'meeting_reminders' => true,
            'weekly_digest' => true,
            'product_updates' => false,
        ]);

        Setting::put('security', [
            'two_factor_enabled' => false,
            'session_timeout_minutes' => 60,
        ]);
    }
}
