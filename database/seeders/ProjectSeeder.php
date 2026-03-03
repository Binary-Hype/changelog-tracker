<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Release;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $projects = [
            ['name' => 'Laravel Framework', 'owner' => 'laravel', 'repo' => 'framework', 'description' => 'The Laravel Framework'],
            ['name' => 'Filament', 'owner' => 'filamentphp', 'repo' => 'filament', 'description' => 'A collection of beautiful full-stack components for Laravel'],
            ['name' => 'Pest', 'owner' => 'pestphp', 'repo' => 'pest', 'description' => 'An elegant PHP testing Framework'],
        ];

        foreach ($projects as $projectData) {
            $project = Project::factory()->create($projectData);

            Release::factory()
                ->count(3)
                ->for($project)
                ->create();
        }
    }
}
