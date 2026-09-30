<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_project_details_include_task_assignee_and_creator(): void
    {
        $creator = User::factory()->create(['name' => 'Project Creator']);
        $assignee = User::factory()->create(['name' => 'Task Assignee']);
        $project = Project::create([
            'name' => 'Project Alpha',
            'description' => 'Project description',
            'status' => 'active',
            'created_by' => $creator->id,
        ]);
        $project->users()->attach($assignee);
        Task::create([
            'project_id' => $project->id,
            'assigned_to' => $assignee->id,
            'created_by' => $creator->id,
            'title' => 'Prepare release',
            'status' => 'todo',
            'priority' => 'high',
        ]);

        $response = $this->actingAs($creator, 'sanctum')
            ->getJson("/api/projects/{$project->id}");

        $response
            ->assertOk()
            ->assertJsonPath('project.tasks.0.assignee.name', 'Task Assignee')
            ->assertJsonPath('project.tasks.0.creator.name', 'Project Creator');
    }
}
