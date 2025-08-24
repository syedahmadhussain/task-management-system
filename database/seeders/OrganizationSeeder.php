<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $organizations = [
            ['name' => 'TechCorp Solutions'],
            ['name' => 'Digital Innovations Ltd'],
            ['name' => 'StartupHub Inc'],
            ['name' => 'Enterprise Systems Co'],
            ['name' => 'CloudFirst Technologies'],
        ];

        foreach ($organizations as $org) {
            Organization::create($org);
        }
    }
}
