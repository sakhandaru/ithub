<script module lang="ts">
    import { releases } from '@/routes/workspaces/projects';
    export const layout = { breadcrumbs: [{ title: 'Detail Rilis', href: releases(['', '']) }] };
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Calendar from 'carbon-icons-svelte/lib/Calendar.svelte';
    import CheckmarkFilled from 'carbon-icons-svelte/lib/CheckmarkFilled.svelte';
    import PackageOpen from 'carbon-icons-svelte/lib/Package.svelte';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import AppHead from '@/components/AppHead.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import ReleaseStatusBadge from '@/components/ReleaseStatusBadge.svelte';
    import { projectBreadcrumbs } from '@/lib/page-header';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived: boolean };
    type Release = { id: string; name: string; description: string | null; target_date: string | null; status: string; tasks_count: number; completed_tasks_count: number };
    type Task = { id: string; identifier: string; title: string; status: string; status_label: string; assignee: { id: number; name: string } | null; due_date: string | null };
    type Module = { id: string; name: string; description: string | null; tasks_count: number };
    type Milestone = { id: string; name: string; target_date: string | null; tasks_count: number };
    let { workspace, project, release, tasks, modules, milestones }: { workspace: Workspace; project: Project; release: Release; tasks: Task[]; modules: Module[]; milestones: Milestone[] } = $props();
    const progress = $derived(release.tasks_count > 0 ? Math.round((release.completed_tasks_count / release.tasks_count) * 100) : 0);
</script>

<AppHead title={`${release.name} · ${project.identifier}`} />

<div class="flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader breadcrumbs={projectBreadcrumbs(page.props.unitContext?.active?.name ?? workspace.name, page.props.unitContext?.active?.id, toUrl(ProjectController.index(workspace.id)), project.name, toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), 'releases', undefined, page.props.projectContext?.active?.icon)} />

    {#if project.archived}<div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan. Rilis bersifat read-only.</div>{/if}

    <section class="rounded-lg border border-border/70 bg-card p-4 sm:p-6" aria-labelledby="release-heading">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h1 id="release-heading" class="text-lg font-semibold tracking-tight">{release.name}</h1><ReleaseStatusBadge status={release.status} /></div>{#if release.description}<p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">{release.description}</p>{/if}</div>
            {#if release.target_date}<span class="inline-flex min-h-7 items-center gap-1.5 rounded-full border border-border/70 px-2.5 py-1 text-xs text-muted-foreground"><Calendar class="size-3.5" aria-hidden="true" />Target {release.target_date}</span>{/if}
        </div>
        <div class="mt-6 grid gap-4 sm:grid-cols-3"><div><p class="text-xs text-muted-foreground">Progress</p><p class="mt-1 text-2xl font-semibold">{progress}%</p><div class="mt-2 h-2 overflow-hidden rounded-full bg-muted"><div class="h-full rounded-full bg-emerald-500" style={`width: ${progress}%`}></div></div></div><div><p class="text-xs text-muted-foreground">Task selesai</p><p class="mt-1 text-2xl font-semibold">{release.completed_tasks_count}<span class="ml-1 text-sm font-normal text-muted-foreground">/ {release.tasks_count}</span></p></div><div><p class="text-xs text-muted-foreground">Total Task</p><p class="mt-1 text-2xl font-semibold">{release.tasks_count}</p></div></div>
    </section>

    <section class="overflow-hidden rounded-lg border border-border/70 bg-card" aria-labelledby="release-tasks-heading">
        <div class="border-b border-border/70 px-4 py-3 sm:px-5"><h2 id="release-tasks-heading" class="text-sm font-semibold">Task dalam Rilis</h2><p class="mt-1 text-xs text-muted-foreground">Daftar pekerjaan yang dikelompokkan ke dalam rilis ini.</p></div>
        {#if tasks.length === 0}<div class="flex min-h-48 flex-col items-center justify-center px-6 py-12 text-center"><PackageOpen class="size-7 text-muted-foreground" aria-hidden="true" /><p class="mt-3 text-sm font-medium">Belum ada Task</p><p class="mt-1 text-sm text-muted-foreground">Pilih Rilis ini dari detail Task untuk menambah pekerjaan.</p></div>{:else}<div class="divide-y divide-border/70">{#each tasks as task (task.id)}<Link href={toUrl(TaskController.show({ workspace: workspace.id, project: project.id, task: task.id }))} class="flex flex-wrap items-center gap-3 px-4 py-3 transition-colors hover:bg-muted/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring sm:px-5"><CheckmarkFilled class={`size-4 shrink-0 ${task.status === 'done' ? 'text-emerald-600' : 'text-muted-foreground'}`} aria-hidden="true" /><span class="font-mono text-xs text-muted-foreground">{task.identifier}</span><span class="min-w-0 flex-1 truncate text-sm font-medium">{task.title}</span><span class="text-xs text-muted-foreground">{task.assignee?.name ?? 'Belum ada PIC'} · {task.due_date ?? 'Tanpa deadline'}</span><span class="rounded-full border border-border/70 px-2 py-0.5 text-xs text-muted-foreground">{task.status_label}</span></Link>{/each}</div>{/if}
    </section>

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="rounded-lg border border-border/70 bg-card" aria-labelledby="release-modules-heading"><div class="border-b border-border/70 px-4 py-3"><h2 id="release-modules-heading" class="text-sm font-semibold">Modul dalam Rilis</h2></div>{#if modules.length === 0}<p class="px-4 py-8 text-sm text-muted-foreground">Belum ada Modul.</p>{:else}<div class="divide-y divide-border/70">{#each modules as module (module.id)}<div class="flex items-center justify-between gap-3 px-4 py-3"><div class="min-w-0"><p class="truncate text-sm font-medium">{module.name}</p>{#if module.description}<p class="truncate text-xs text-muted-foreground">{module.description}</p>{/if}</div><span class="shrink-0 text-xs text-muted-foreground">{module.tasks_count} Task</span></div>{/each}</div>{/if}</section>
        <section class="rounded-lg border border-border/70 bg-card" aria-labelledby="release-milestones-heading"><div class="border-b border-border/70 px-4 py-3"><h2 id="release-milestones-heading" class="text-sm font-semibold">Milestone dalam Rilis</h2></div>{#if milestones.length === 0}<p class="px-4 py-8 text-sm text-muted-foreground">Belum ada Milestone.</p>{:else}<div class="divide-y divide-border/70">{#each milestones as milestone (milestone.id)}<div class="flex items-center justify-between gap-3 px-4 py-3"><div class="min-w-0"><p class="truncate text-sm font-medium">{milestone.name}</p><p class="text-xs text-muted-foreground">{milestone.target_date ?? 'Tanpa target'}</p></div><span class="shrink-0 text-xs text-muted-foreground">{milestone.tasks_count} Task</span></div>{/each}</div>{/if}</section>
    </div>
</div>
