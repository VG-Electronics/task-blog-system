<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = 'admin@blog.test';
        $adminPassword = 'password';

        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            ['nickname' => 'admin', 'password' => Hash::make($adminPassword)]
        );
        $admin->roles()->syncWithoutDetaching(Role::where('name', Role::ADMIN)->first());

        $this->command->info("Admin credentials — email: {$adminEmail} | password: {$adminPassword}");

        $moderators = User::factory(2)->create();
        $moderatorRole = Role::where('name', Role::MODERATOR)->first();
        $moderators->each(fn(User $u) => $u->roles()->syncWithoutDetaching($moderatorRole));

        $this->command->info('Moderator credentials (password: password):');
        $moderators->each(fn(User $u) => $this->command->line("  email: {$u->email} | nickname: {$u->nickname}"));

        $users = User::factory(3)->create();
        $userRole = Role::where('name', Role::USER)->first();
        $users->each(fn(User $u) => $u->roles()->syncWithoutDetaching($userRole));

        $this->command->info('User credentials (password: password):');
        $users->each(fn(User $u) => $this->command->line("  email: {$u->email} | nickname: {$u->nickname}"));
    }
}
