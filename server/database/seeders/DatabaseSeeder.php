<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Passwords come from .env (SEED_PASSWORD); never commit real ones.
        $password = env('SEED_PASSWORD', 'change-me');
        $accounts = [
            ['Admin', 'admin@example.com', $password, UserRole::Admin],
            ['Team Member', 'team@example.com', $password, UserRole::TeamMember],
        ];

        foreach ($accounts as [$name, $email, $password, $role]) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make($password), 'role' => $role],
            );
        }

        // System configuration only — no demo/mock business records (leads,
        // clients, projects, documents, meetings) are seeded.
        $this->call([
            SettingSeeder::class,
        ]);
    }
}
