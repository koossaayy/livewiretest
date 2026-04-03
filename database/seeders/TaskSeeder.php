<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $user = User::create([
                'name' => 'Demo User',
                'email' => 'demo@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        $tasks = [
            [
                'title' => 'Set up project CI/CD pipeline',
                'description' => 'Configure GitHub Actions for automated testing and deployment.',
                'status' => 'completed',
                'priority' => 3,
                'due_date' => now()->subDays(2),
            ],
            [
                'title' => 'Design the landing page',
                'description' => 'Create wireframes and high-fidelity mockups for the marketing site.',
                'status' => 'in_progress',
                'priority' => 2,
                'due_date' => now()->addDays(3),
            ],
            [
                'title' => 'Write API documentation',
                'description' => 'Document all REST endpoints with request/response examples.',
                'status' => 'pending',
                'priority' => 2,
                'due_date' => now()->addDays(5),
            ],
            [
                'title' => 'Fix authentication bug on mobile',
                'description' => 'Users on iOS Safari are getting logged out unexpectedly.',
                'status' => 'pending',
                'priority' => 3,
                'due_date' => now()->subDay(),
            ],
            [
                'title' => 'Update dependencies to latest versions',
                'description' => 'Run composer update and npm update, then verify nothing breaks.',
                'status' => 'pending',
                'priority' => 1,
                'due_date' => now()->addWeek(),
            ],
            [
                'title' => 'Add dark mode support',
                'description' => 'Implement dark mode toggle across all pages.',
                'status' => 'in_progress',
                'priority' => 1,
                'due_date' => now()->addDays(10),
            ],
            [
                'title' => 'Review pull request #47',
                'description' => 'Code review for the new notification system feature branch.',
                'status' => 'completed',
                'priority' => 2,
                'due_date' => now()->subDays(3),
            ],
            [
                'title' => 'Prepare quarterly report',
                'description' => 'Compile project metrics and progress summary for stakeholders.',
                'status' => 'pending',
                'priority' => 3,
                'due_date' => now()->subDays(1),
            ],
            [
                'title' => 'Optimize database queries',
                'description' => 'Profile slow queries and add missing indexes.',
                'status' => 'pending',
                'priority' => 2,
                'due_date' => now()->addDays(4),
            ],
            [
                'title' => 'Set up error monitoring',
                'description' => 'Integrate Sentry for real-time error tracking in production.',
                'status' => 'completed',
                'priority' => 1,
                'due_date' => now()->subWeek(),
            ],
            [
                'title' => 'Migrate legacy user data',
                'description' => 'Transfer data from the old system into the new schema.',
                'status' => 'pending',
                'priority' => 3,
                'due_date' => now()->addDays(2),
            ],
            [
                'title' => 'Create onboarding tutorial',
                'description' => 'Build an interactive walkthrough for new users.',
                'status' => 'pending',
                'priority' => 1,
                'due_date' => now()->addDays(14),
            ],
        ];

        foreach ($tasks as $task) {
            Task::create([
                'user_id' => $user->id,
                ...$task,
            ]);
        }
    }
}
