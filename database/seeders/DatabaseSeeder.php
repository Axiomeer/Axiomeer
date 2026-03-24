<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@axiomeer.test',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Analyst User',
            'email' => 'analyst@axiomeer.test',
            'role' => 'analyst',
        ]);

        User::factory()->create([
            'name' => 'Viewer User',
            'email' => 'viewer@axiomeer.test',
            'role' => 'viewer',
        ]);

        $this->call([
            DomainSeeder::class,
        ]);
    }
}
