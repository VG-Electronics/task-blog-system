<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([Role::USER, Role::MODERATOR, Role::ADMIN] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }

    }
}
