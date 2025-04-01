<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default roles as specified in the requirements
        $roles = [
            [
                'name' => 'candidate',
                'description' => 'Applicants to YouCode training'
            ],
            [
                'name' => 'instructor',
                'description' => 'Teaching staff for evaluation'
            ],
            [
                'name' => 'administrator',
                'description' => 'Platform management personnel'
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
