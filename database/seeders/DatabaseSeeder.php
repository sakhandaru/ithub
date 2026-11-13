<?php

namespace Database\Seeders;

use App\Actions\Comments\CreateComment;
use App\Actions\Projects\ArchiveProject;
use App\Actions\Projects\CreateProject;
use App\Actions\Tasks\CreateTask;
use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\ProjectRole;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Label;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\TaskNotification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $owner = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $developer = User::factory()->create(['name' => 'Dina Developer', 'email' => 'dina@example.com']);
        $infra = User::factory()->create(['name' => 'Bima Infra', 'email' => 'bima@example.com']);
        $operations = User::factory()->create(['name' => 'Sari Operasional', 'email' => 'sari@example.com']);
        $reviewer = User::factory()->create(['name' => 'Raka Reviewer', 'email' => 'raka@example.com']);

        $workspace = app(CreateWorkspace::class)->handle($owner, 'sitop Demo Workspace', 'Asia/Jakarta');
        foreach ([$developer, $infra, $operations, $reviewer] as $member) {
            $workspace->memberships()->create(['user_id' => $member->id, 'role' => 'member', 'invited_by_id' => $owner->id, 'joined_at' => now()]);
        }
        $units = $workspace->units->keyBy('name');
        foreach ([[$developer, 'Development'], [$infra, 'Infra'], [$operations, 'Operasional']] as [$member, $unitName]) {
            $units[$unitName]->memberships()->create(['user_id' => $member->id, 'added_by_id' => $owner->id]);
        }

        $projects = [
            'development' => $this->createProject($owner, $workspace, $units['Development']->id, 'Portal Layanan Internal', 'PORTAL', 'Pengembangan portal layanan internal untuk seluruh unit.', $developer, 'active', '2026-12-20'),
            'infra' => $this->createProject($owner, $workspace, $units['Infra']->id, 'Modernisasi Infrastruktur', 'INFRA', 'Modernisasi monitoring, backup, dan kapasitas infrastruktur.', $infra, 'active', '2026-11-30'),
            'operations' => $this->createProject($owner, $workspace, $units['Operasional']->id, 'Digitalisasi Operasional TI', 'OPS', 'Merapikan alur permintaan dan dokumentasi operasional TI.', $operations, 'active', '2027-01-15'),
            'completed' => $this->createProject($owner, $workspace, $units['Development']->id, 'Pilot Knowledge Base', 'KNOW', 'Project contoh yang sudah selesai dan siap diarsipkan.', $developer, 'completed', '2026-09-30'),
        ];

        foreach ($projects as $project) {
            foreach ([[$developer, ProjectRole::Contributor], [$infra, ProjectRole::Contributor], [$operations, ProjectRole::Contributor], [$reviewer, ProjectRole::Commenter]] as [$member, $role]) {
                if (! $project->memberships()->where('user_id', $member->id)->exists()) {
                    $project->memberships()->create(['user_id' => $member->id, 'role' => $role, 'added_by_id' => $owner->id, 'joined_at' => now()]);
                }
            }
        }

        $this->seedDevelopment($owner, $developer, $reviewer, $projects['development']);
        $this->seedInfra($owner, $infra, $reviewer, $projects['infra']);
        $this->seedOperations($owner, $operations, $reviewer, $projects['operations']);
        $this->seedCompleted($owner, $developer, $projects['completed']);

        $owner->forceFill(['active_workspace_id' => $workspace->id])->save();
        $developer->notify(new TaskNotification('welcome', 'Selamat datang di sitop', 'Workspace demo sudah siap untuk dieksplorasi.', route('dashboard')));
    }

    private function createProject(User $owner, Workspace $workspace, string $unitId, string $name, string $identifier, string $description, User $lead, string $status, string $targetDate): Project
    {
        return app(CreateProject::class)->handle($owner, $workspace, [
            'name' => $name,
            'identifier' => $identifier,
            'unit_id' => $unitId,
            'description' => $description,
            'visibility' => 'workspace',
            'status' => $status,
            'project_lead_id' => $lead->id,
            'default_assignee_id' => $lead->id,
            'start_date' => now()->subDays(30)->toDateString(),
            'target_date' => $targetDate,
        ]);
    }

    private function seedDevelopment(User $owner, User $developer, User $reviewer, Project $project): void
    {
        $labels = $this->labels($project, [['Bug', 'red'], ['Feature', 'blue'], ['UX', 'purple'], ['Urgent', 'orange']]);
        $milestones = [
            'Discovery & Design' => $project->milestones()->create(['name' => 'Discovery & Design', 'target_date' => '2026-11-20']),
            'MVP Release' => $project->milestones()->create(['name' => 'MVP Release', 'target_date' => '2026-12-20']),
        ];
        $tasks = [
            ['Menyusun user flow login dan workspace', 'in_review', 'high', $developer, $milestones['Discovery & Design']],
            ['Implementasi navigasi dua tingkat', 'done', 'high', $developer, $milestones['Discovery & Design']],
            ['Review aksesibilitas halaman Task', 'todo', 'medium', $reviewer, $milestones['Discovery & Design']],
            ['Membuat filter Task lanjutan', 'in_progress', 'high', $developer, $milestones['MVP Release']],
            ['Integrasi notifikasi assignment', 'blocked', 'urgent', $developer, $milestones['MVP Release']],
            ['Menulis dokumentasi onboarding', 'backlog', 'low', $developer, $milestones['MVP Release']],
            ['Validasi upload bukti penyelesaian', 'done', 'medium', $developer, $milestones['MVP Release']],
            ['Regression test permission Project', 'canceled', 'medium', $reviewer, $milestones['MVP Release']],
        ];
        foreach ($tasks as [$title, $status, $priority, $assignee, $milestone]) {
            $task = $this->task($owner, $project, $title, $assignee, $priority, $status, $milestone, fake()->dateTimeBetween('-5 days', '+20 days')->format('Y-m-d'));
            $task->labels()->sync([$labels[array_rand($labels)]->id]);
            if ($status === 'done') {
                $task->update(['completion_note' => 'Sudah diverifikasi pada demo.', 'evidence_url' => 'https://example.com/evidence/'.$task->identifier, 'completed_at' => now()->subDay()]);
            }
            if ($status === 'blocked') {
                $task->update(['blocked_reason' => 'Menunggu keputusan arsitektur dari stakeholder.']);
            }
            if ($status === 'canceled') {
                $task->update(['canceled_reason' => 'Scope dipindahkan ke fase berikutnya.']);
            }
        }
        $parent = $this->task($owner, $project, 'Menyiapkan release checklist', $developer, 'high', 'in_progress', $milestones['MVP Release'], '2026-12-10');
        $this->task($owner, $project, 'Memeriksa backup database', $developer, 'medium', 'todo', $milestones['MVP Release'], '2026-12-08', $parent);
        $this->task($owner, $project, 'Memeriksa konfigurasi production', $developer, 'medium', 'done', $milestones['MVP Release'], '2026-12-09', $parent);
        $this->collaboration($owner, $reviewer, $parent);
    }

    private function seedInfra(User $owner, User $infra, User $reviewer, Project $project): void
    {
        $labels = $this->labels($project, [['Monitoring', 'blue'], ['Backup', 'green'], ['Risk', 'red']]);
        $milestone = $project->milestones()->create(['name' => 'Infra Readiness', 'target_date' => '2026-11-30']);
        foreach ([['Audit resource server', 'in_progress', 'high'], ['Menguji backup harian', 'done', 'urgent'], ['Membuat dashboard monitoring', 'todo', 'medium'], ['Review kapasitas storage', 'blocked', 'high'], ['Rotasi credential service', 'backlog', 'urgent'], ['Dokumentasi topology jaringan', 'in_review', 'low']] as [$title, $status, $priority]) {
            $task = $this->task($owner, $project, $title, $infra, $priority, $status, $milestone, fake()->dateTimeBetween('-3 days', '+25 days')->format('Y-m-d'));
            $task->labels()->sync([$labels[array_rand($labels)]->id]);
            if ($status === 'done') {
                $task->update(['completion_note' => 'Backup berhasil dipulihkan pada simulasi.', 'completed_at' => now()->subDays(2)]);
            }
            if ($status === 'blocked') {
                $task->update(['blocked_reason' => 'Menunggu akses vendor.']);
            }
        }
        $this->collaboration($owner, $reviewer, $project->tasks()->firstOrFail());
    }

    private function seedOperations(User $owner, User $operations, User $reviewer, Project $project): void
    {
        $labels = $this->labels($project, [['SOP', 'green'], ['Request', 'blue'], ['Audit', 'orange']]);
        foreach ([['Merapikan SOP onboarding user', 'done', 'medium'], ['Membuat template intake request', 'in_progress', 'high'], ['Inventaris kebutuhan unit', 'todo', 'medium'], ['Review SLA internal', 'backlog', 'low'], ['Mengumpulkan feedback pengguna', 'in_review', 'medium']] as [$title, $status, $priority]) {
            $task = $this->task($owner, $project, $title, $operations, $priority, $status, null, fake()->dateTimeBetween('-2 days', '+30 days')->format('Y-m-d'));
            $task->labels()->sync([$labels[array_rand($labels)]->id]);
            if ($status === 'done') {
                $task->update(['completion_note' => 'SOP disetujui oleh lead.', 'evidence_url' => 'https://example.com/sop/'.$task->identifier, 'completed_at' => now()->subDays(3)]);
            }
        }
        $this->collaboration($owner, $reviewer, $project->tasks()->firstOrFail());
    }

    private function seedCompleted(User $owner, User $developer, Project $project): void
    {
        $task = $this->task($owner, $project, 'Serah terima knowledge base', $developer, 'medium', 'done', null, '2026-09-20');
        $task->update(['completion_note' => 'Serah terima selesai.', 'completed_at' => now()->subMonths(2)]);
        app(ArchiveProject::class)->handle($owner, $project->fresh());
    }

    /** @param array<int, array{0: string, 1: string}> $definitions */
    /**
     * @param  array<int, array{0: string, 1: string}>  $definitions
     * @return list<Label>
     */
    private function labels(Project $project, array $definitions): array
    {
        return array_values(collect($definitions)->map(fn (array $definition): Label => $project->labels()->create(['name' => $definition[0], 'color' => $definition[1]]))->all());
    }

    private function task(User $owner, Project $project, string $title, User $assignee, string $priority, string $status, ?Milestone $milestone, string $dueDate, ?Task $parent = null): Task
    {
        $task = app(CreateTask::class)->handle($owner, $project, ['title' => $title, 'description' => 'Data demo untuk memperlihatkan konteks, progress, dan histori Task.', 'assignee_id' => $assignee->id, 'requester_id' => $owner->id, 'priority' => TaskPriority::from($priority), 'due_date' => $dueDate, 'milestone_id' => $milestone?->id, 'parent_task_id' => $parent?->id]);
        $task->update(['status' => TaskStatus::from($status)]);
        ActivityLog::create(['workspace_id' => $project->workspace_id, 'actor_id' => $owner->id, 'subject_type' => Task::class, 'subject_id' => $task->id, 'action' => 'task.seeded', 'description' => 'Task demo dibuat untuk pilot.']);

        return $task->fresh();
    }

    private function collaboration(User $owner, User $reviewer, Task $task): void
    {
        app(CreateComment::class)->handle($reviewer, $task, 'Saya sudah menambahkan catatan review untuk contoh kolaborasi.');
        $disk = (string) config('sitop.attachments.disk');
        Storage::disk($disk)->put('tasks/'.$task->id.'/demo-evidence.txt', "Bukti demo untuk {$task->identifier}\n");
        Attachment::create(['task_id' => $task->id, 'uploaded_by_id' => $owner->id, 'disk' => $disk, 'path' => 'tasks/'.$task->id.'/demo-evidence.txt', 'original_name' => 'demo-evidence.txt', 'mime_type' => 'text/plain', 'size' => 42]);
        AuditLog::create(['workspace_id' => $task->project->workspace_id, 'actor_id' => $owner->id, 'action' => 'task.seeded.collaboration', 'auditable_type' => Task::class, 'auditable_id' => $task->id, 'request_id' => null, 'before' => null, 'after' => ['demo' => true]]);
    }
}
