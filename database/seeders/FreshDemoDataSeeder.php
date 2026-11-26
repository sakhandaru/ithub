<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FreshDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->clearApplicationData();
        $this->call(DemoDataSeeder::class);
    }

    private function clearApplicationData(): void
    {
        $tables = [
            'project_updates', 'notifications', 'saved_views', 'task_relations', 'attachments', 'activity_logs', 'audit_logs',
            'comments', 'label_task', 'tasks', 'labels', 'milestones', 'modules', 'releases', 'project_memberships', 'projects',
            'unit_memberships', 'units', 'workspace_memberships', 'workspaces', 'passkeys', 'sessions', 'password_reset_tokens',
            'failed_jobs', 'job_batches', 'jobs', 'cache_locks', 'cache',
        ];

        DB::transaction(function () use ($tables): void {
            User::query()->update(['active_workspace_id' => null, 'active_unit_id' => null]);

            foreach ($tables as $table) {
                DB::table($table)->delete();
            }
        });
    }
}
