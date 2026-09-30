<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_task_list_includes_status_counts_for_all_tasks(): void
    {
        $creator = User::factory()->create();
        $project = Project::create([
            'name' => 'Project Alpha',
            'description' => 'Project description',
            'status' => 'active',
            'created_by' => $creator->id,
        ]);

        foreach (['todo', 'todo', 'todo', 'todo', 'todo', 'todo', 'in_progress', 'in_progress', 'review', 'completed', 'completed', 'completed'] as $status) {
            Task::create([
                'project_id' => $project->id,
                'created_by' => $creator->id,
                'title' => 'Task '.$status,
                'status' => $status,
                'priority' => 'medium',
            ]);
        }

        $response = $this->actingAs($creator, 'sanctum')->getJson('/api/tasks');

        $response
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('summary.total', 12)
            ->assertJsonPath('summary.todo', 6)
            ->assertJsonPath('summary.in_progress', 2)
            ->assertJsonPath('summary.review', 1)
            ->assertJsonPath('summary.completed', 3);
    }
}
