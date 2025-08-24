<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $organizations = Organization::all();

        if ($organizations->isEmpty()) {
            return;
        }
        $userData = [
            [
                'name' => 'John Smith',
                'email' => 'admin@techcorp.com',
                'role' => 'admin',
                'org_id' => 1,
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.manager@techcorp.com',
                'role' => 'manager',
                'org_id' => 1,
            ],
            [
                'name' => 'Mike Wilson',
                'email' => 'mike.member@techcorp.com',
                'role' => 'member',
                'org_id' => 1,
            ],
            [
                'name' => 'Lisa Chen',
                'email' => 'lisa.member@techcorp.com',
                'role' => 'member',
                'org_id' => 1,
            ],
            [
                'name' => 'David Brown',
                'email' => 'admin@digitalinnovations.com',
                'role' => 'admin',
                'org_id' => 2,
            ],
            [
                'name' => 'Emma Davis',
                'email' => 'emma.manager@digitalinnovations.com',
                'role' => 'manager',
                'org_id' => 2,
            ],
            [
                'name' => 'Tom Anderson',
                'email' => 'tom.member@digitalinnovations.com',
                'role' => 'member',
                'org_id' => 2,
            ],
            [
                'name' => 'Rachel Green',
                'email' => 'admin@startuphub.com',
                'role' => 'admin',
                'org_id' => 3,
            ],
            [
                'name' => 'Alex Rodriguez',
                'email' => 'alex.manager@startuphub.com',
                'role' => 'manager',
                'org_id' => 3,
            ],
            [
                'name' => 'Jessica Taylor',
                'email' => 'jessica.member@startuphub.com',
                'role' => 'member',
                'org_id' => 3,
            ],
            [
                'name' => 'Robert Miller',
                'email' => 'admin@enterprisesystems.com',
                'role' => 'admin',
                'org_id' => 4,
            ],
            [
                'name' => 'Amanda White',
                'email' => 'amanda.manager@enterprisesystems.com',
                'role' => 'manager',
                'org_id' => 4,
            ],
            [
                'name' => 'Kevin Jones',
                'email' => 'admin@cloudfirst.com',
                'role' => 'admin',
                'org_id' => 5,
            ],
        ];

        foreach ($userData as $user) {
            User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => Hash::make('password123'),
                'role' => $user['role'],
                'org_id' => $user['org_id'],
                'email_verified_at' => now(),
            ]);
        }
    }
}
