<script lang="ts">
    import { Form, Link, page } from '@inertiajs/svelte';
    import Archive from 'carbon-icons-svelte/lib/Archive.svelte';
    import CalendarDays from 'carbon-icons-svelte/lib/Calendar.svelte';
    import FolderKanban from 'carbon-icons-svelte/lib/FolderTree.svelte';
    import ListTodo from 'carbon-icons-svelte/lib/Task.svelte';
    import RotateCcw from 'carbon-icons-svelte/lib/Renew.svelte';
    import Settings from 'carbon-icons-svelte/lib/Settings.svelte';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import ProjectArchiveController from '@/actions/App/Http/Controllers/ProjectArchiveController';
    import ProjectSettingsController from '@/actions/App/Http/Controllers/ProjectSettingsController';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import AppHead from '@/components/AppHead.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { toUrl } from '@/lib/utils';
    import { projectDetailBreadcrumbs } from '@/lib/page-header';
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

<div class="flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader breadcrumbs={projectDetailBreadcrumbs(page.props.unitContext?.active?.name ?? workspace.name, page.props.unitContext?.active?.id, toUrl(ProjectController.index(workspace.id)), project.name, undefined, page.props.projectContext?.active?.icon)}>
        {#snippet actions()}
            <div class="flex flex-wrap items-center gap-1">
            {#if canManage}
                <Button variant="outline" class="h-7 shrink-0 gap-1.5 border-border/80 bg-background px-2.5 text-xs text-muted-foreground" asChild>
                    {#snippet children(props)}
                        <Link href={toUrl(ProjectSettingsController.show({ workspace: workspace.id, project: project.id }))} {...props} aria-label="Pengaturan project" title="Pengaturan project"><Settings class="size-4" aria-hidden="true" />Pengaturan</Link>
                    {/snippet}
                </Button>
                {#if !project.archived}
                    <Form {...ProjectArchiveController.archive.form({ workspace: workspace.id, project: project.id })} onsubmit={confirmArchive}>
                        {#snippet children({ processing })}
                        <Button type="submit" variant="outline" class="h-7 shrink-0 gap-1.5 border-border/80 bg-background px-2.5 text-xs text-muted-foreground hover:text-destructive" disabled={processing} aria-label="Arsipkan project" title="Arsipkan project">
                                <Archive class="size-4" aria-hidden="true" />
                                Arsipkan
                            </Button>
                        {/snippet}
                    </Form>
                {/if}
            {:else if canRestore}
                <Form {...ProjectArchiveController.restore.form({ workspace: workspace.id, project: project.id })}>
                    {#snippet children({ processing })}
                        <Button type="submit" variant="outline" class="h-7 shrink-0 gap-1.5 border-border/80 bg-background px-2.5 text-xs text-muted-foreground" disabled={processing} aria-label="Pulihkan project" title="Pulihkan project">
                            <RotateCcw class="size-4" aria-hidden="true" />
                            Pulihkan Project
                        </Button>
                    {/snippet}
                </Form>
            {/if}
            </div>
        {/snippet}
    </PageHeader>


    {#if project.archived}
        <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">
            Project ini diarsipkan dan bersifat read-only sampai dipulihkan.
        </div>
    {/if}

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="rounded-lg border border-border/80 bg-card p-4 sm:p-5">
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
            <div class="mt-8 rounded-md border border-border/80 bg-surface-2/50 px-4 py-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <ListTodo class="mt-0.5 size-5 text-muted-foreground" aria-hidden="true" />
                        <div>
                            <p class="text-sm font-medium">Pekerjaan dalam Project</p>
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

        <aside class="rounded-lg border border-border/80 bg-surface-2/40 p-4 lg:sticky lg:top-4 lg:self-start">
            <h2 class="text-sm font-semibold">Ringkasan Project</h2>
            <dl class="mt-4 grid gap-4 text-sm">
                <div>
                    <dt class="text-xs text-muted-foreground">Project Lead</dt>
                    <dd class="mt-1 font-medium">{project.project_lead?.name ?? 'Belum ditentukan'}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">PIC default</dt>
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
