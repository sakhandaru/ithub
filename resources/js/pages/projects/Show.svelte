<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import Archive from '@lucide/svelte/icons/archive';
    import CalendarDays from '@lucide/svelte/icons/calendar-days';
    import FolderKanban from '@lucide/svelte/icons/folder-kanban';
    import ListTodo from '@lucide/svelte/icons/list-todo';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import ProjectArchiveController from '@/actions/App/Http/Controllers/ProjectArchiveController';
    import ProjectSettingsController from '@/actions/App/Http/Controllers/ProjectSettingsController';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import AppHead from '@/components/AppHead.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { toUrl } from '@/lib/utils';
    import type { ProjectSummary, ProjectPerson } from '@/types';

    type Workspace = { id: string; name: string };
    type Project = ProjectSummary & {
        description: string | null;
        project_lead: ProjectPerson | null;
        default_assignee: ProjectPerson | null;
        created_by: ProjectPerson | null;
        members_count: number;
        tasks_count: number;
        is_read_only: boolean;
    };

    let { workspace, project, canManage, canRestore }: { workspace: Workspace; project: Project; canManage: boolean; canRestore: boolean } = $props();

    function confirmArchive(event: SubmitEvent): void {
        if (!window.confirm('Arsipkan Project ini? Data dan histori tetap dipertahankan.')) {
            event.preventDefault();
        }
    }
</script>

<AppHead title={`${project.identifier} · ${project.name}`} />

<div class="flex min-w-0 flex-col gap-8 px-4 py-6 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-5 border-b pb-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                <Link href={toUrl(ProjectController.index(workspace.id))} class="hover:text-foreground hover:underline">Projects</Link>
                <span aria-hidden="true">/</span>
                <span class="font-mono">{project.identifier}</span>
            </div>
            <div class="mt-3 flex items-start gap-3">
                <div class="mt-1 flex size-10 shrink-0 items-center justify-center rounded-md border bg-muted/40">
                    <FolderKanban class="size-5 text-muted-foreground" aria-hidden="true" />
                </div>
                <div class="min-w-0">
                    <h1 class="text-2xl font-semibold tracking-tight">{project.name}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">{workspace.name} · {project.unit?.name ?? 'Tanpa Unit'}</p>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            {#if canManage}
                <Button variant="outline" class="min-h-11" asChild>
                    {#snippet children(props)}
                        <Link href={toUrl(ProjectSettingsController.show({ workspace: workspace.id, project: project.id }))} {...props}>Pengaturan</Link>
                    {/snippet}
                </Button>
                {#if !project.archived}
                    <Form {...ProjectArchiveController.archive.form({ workspace: workspace.id, project: project.id })} onsubmit={confirmArchive}>
                        {#snippet children({ processing })}
                            <Button type="submit" variant="outline" class="min-h-11" disabled={processing}>
                                <Archive class="size-4" aria-hidden="true" />
                                Arsipkan
                            </Button>
                        {/snippet}
                    </Form>
                {/if}
            {:else if canRestore}
                <Form {...ProjectArchiveController.restore.form({ workspace: workspace.id, project: project.id })}>
                    {#snippet children({ processing })}
                        <Button type="submit" class="min-h-11" disabled={processing}>
                            <RotateCcw class="size-4" aria-hidden="true" />
                            Pulihkan Project
                        </Button>
                    {/snippet}
                </Form>
            {/if}
        </div>
    </header>

    {#if project.archived}
        <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">
            Project ini diarsipkan dan bersifat read-only sampai dipulihkan.
        </div>
    {/if}

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="rounded-lg border bg-card p-5 sm:p-6">
            <div class="flex flex-wrap items-center gap-2">
                <Badge variant="secondary">{project.status_label}</Badge>
                <Badge variant="outline">{project.visibility_label}</Badge>
            </div>
            <h2 class="mt-6 text-base font-semibold">Tujuan Project</h2>
            {#if project.description}
                <p class="mt-2 whitespace-pre-wrap text-sm leading-7 text-muted-foreground">{project.description}</p>
            {:else}
                <p class="mt-2 text-sm text-muted-foreground">Belum ada deskripsi atau tujuan Project.</p>
            {/if}
            <div class="mt-8 rounded-md border bg-muted/20 px-4 py-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <ListTodo class="mt-0.5 size-5 text-muted-foreground" aria-hidden="true" />
                        <div>
                            <p class="text-sm font-medium">{project.tasks_count} Task</p>
                            <p class="mt-1 text-sm text-muted-foreground">Buka daftar Task untuk mulai bekerja.</p>
                        </div>
                    </div>
                    <Button variant="outline" class="min-h-10" asChild>
                        {#snippet children(props)}
                            <Link href={toUrl(TaskController.index({ workspace: workspace.id, project: project.id }))} {...props}>Buka Task</Link>
                        {/snippet}
                    </Button>
                </div>
            </div>
        </section>

        <aside class="rounded-lg border bg-card p-5">
            <h2 class="text-sm font-semibold">Ringkasan Project</h2>
            <dl class="mt-4 grid gap-4 text-sm">
                <div>
                    <dt class="text-xs text-muted-foreground">Project Lead</dt>
                    <dd class="mt-1 font-medium">{project.project_lead?.name ?? 'Belum ditentukan'}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Default Assignee</dt>
                    <dd class="mt-1 font-medium">{project.default_assignee?.name ?? 'Tidak ada'}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Anggota</dt>
                    <dd class="mt-1 font-medium">{project.members_count} orang</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Tanggal</dt>
                    <dd class="mt-1 flex items-center gap-2 font-medium">
                        <CalendarDays class="size-4 text-muted-foreground" aria-hidden="true" />
                        {project.start_date ?? '—'} → {project.target_date ?? '—'}
                    </dd>
                </div>
            </dl>
        </aside>
    </div>
</div>
