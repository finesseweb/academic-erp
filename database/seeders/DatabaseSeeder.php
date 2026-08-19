<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $role = Role::query()->where('code', 'SUPER_ADMIN')->first();
        if ($role && ! $user->roles()->whereKey($role->id)->exists()) {
            $user->roles()->attach($role->id, [
                'scope_type' => 'UNIVERSITY',
                'scope_reference' => 'university',
                'status' => 'ACTIVE',
            ]);
        }
    }
}
