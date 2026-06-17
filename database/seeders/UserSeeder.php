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
        $admin = User::firstOrCreate(
            ['email' => 'admin@blog.test'],
            ['nickname' => 'admin', 'password' => Hash::make('password')]
        );
        $admin->roles()->syncWithoutDetaching(Role::where('name', Role::ADMIN)->first());

        $moderators = User::factory(2)->create();
        $moderatorRole = Role::where('name', Role::MODERATOR)->first();
        $moderators->each(fn(User $u) => $u->roles()->syncWithoutDetaching($moderatorRole));

        $users = User::factory(3)->create();
        $userRole = Role::where('name', Role::USER)->first();
        $users->each(fn(User $u) => $u->roles()->syncWithoutDetaching($userRole));
    }
}
