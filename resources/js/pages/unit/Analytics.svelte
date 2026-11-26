<script module lang="ts">
    import { analytics } from '@/routes/workspaces';

    export const layout = {
        breadcrumbs: [{ title: 'Analitik', href: analytics('') }],
    };
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Activity from 'carbon-icons-svelte/lib/Activity.svelte';
    import BarChart3 from 'carbon-icons-svelte/lib/ChartBar.svelte';
    import FolderKanban from 'carbon-icons-svelte/lib/FolderTree.svelte';
    import ListTodo from 'carbon-icons-svelte/lib/Task.svelte';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import AppHead from '@/components/AppHead.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import { formatDateTime } from '@/lib/format-date';
    import { toUrl } from '@/lib/utils';
    import { globalHeaderLabels, unitPageBreadcrumbs } from '@/lib/page-header';

    type Workspace = { id: string; name: string; timezone: string };
    type Unit = { id: string; name: string } | null;
    type Stats = { projects: number; tasks: number; active_tasks: number; done_tasks: number; overdue_tasks: number; blocked_tasks: number };
    type StatusCount = { label: string; count: number };
    type Project = { id: string; name: string; identifier: string; tasks: number; done_tasks: number; active_tasks: number };
    type ActivityItem = { id: string; action: string; description: string | null; created_at: string; actor: { id: number; name: string } | null };

    let { workspace, unit, stats, statusCounts, projects, activities }: { workspace: Workspace; unit: Unit; stats: Stats; statusCounts: Record<string, StatusCount>; projects: Project[]; activities: ActivityItem[] } = $props();
    const workspaceTimezone = $derived(page.props.workspaceContext?.active?.timezone ?? workspace.timezone);
    const projectUrl = (project: Project): string => toUrl(ProjectController.show({ workspace: workspace.id, project: project.id }));
    const completion = (project: Project): number => project.tasks > 0 ? Math.round((project.done_tasks / project.tasks) * 100) : 0;
    const metrics: { key: keyof Stats; label: string; icon: typeof FolderKanban }[] = [
        { key: 'projects', label: 'Project aktif', icon: FolderKanban },
        { key: 'tasks', label: 'Total Task', icon: ListTodo },
        { key: 'done_tasks', label: 'Task selesai', icon: BarChart3 },
        { key: 'blocked_tasks', label: 'Task Blocked', icon: Activity },
    ];
</script>

<AppHead title="Analitik" />

<div class="mx-auto flex w-full max-w-7xl flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader breadcrumbs={unitPageBreadcrumbs(unit?.name ?? globalHeaderLabels.unit, 'analytics', undefined, unit?.id)} />

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan analitik">
        {#each metrics as item}
            <div class="rounded-lg border border-border/80 bg-card p-4">
                <item.icon class="size-4 text-muted-foreground" aria-hidden="true" />
                <p class="mt-4 text-2xl font-semibold">{stats[item.key]}</p>
                <p class="text-sm text-muted-foreground">{item.label}</p>
            </div>
        {/each}
    </section>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="rounded-lg border border-border/80 bg-card">
            <div class="border-b border-border/70 px-4 py-3"><h2 class="text-sm font-semibold">Distribusi status Task</h2><p class="mt-1 text-xs text-muted-foreground">Data mengikuti Project yang dapat Anda lihat.</p></div>
            {#if Object.keys(statusCounts).length === 0}<p class="px-4 py-10 text-sm text-muted-foreground">Belum ada data Task.</p>{:else}<div class="grid gap-3 p-4 sm:grid-cols-2">{#each Object.entries(statusCounts) as [status, item]}<div class="flex items-center justify-between gap-3 rounded-md border border-border/70 px-3 py-3"><span class="text-sm text-muted-foreground">{item.label}</span><span class="font-semibold">{item.count}</span></div>{/each}</div>{/if}
        </section>
        <section class="rounded-lg border border-border/80 bg-card p-4"><h2 class="text-sm font-semibold">Perhatian</h2><div class="mt-4 grid gap-3 text-sm"><div class="flex items-center justify-between gap-3"><span class="text-muted-foreground">Task aktif</span><span class="font-medium">{stats.active_tasks}</span></div><div class="flex items-center justify-between gap-3"><span class="text-muted-foreground">Terlambat</span><span class="font-medium">{stats.overdue_tasks}</span></div><div class="flex items-center justify-between gap-3"><span class="text-muted-foreground">Blocked</span><span class="font-medium">{stats.blocked_tasks}</span></div></div></section>
    </div>

    <section class="rounded-lg border border-border/80 bg-card">
        <div class="border-b border-border/70 px-4 py-3"><h2 class="text-sm font-semibold">Progress per Project</h2><p class="mt-1 text-xs text-muted-foreground">Perbandingan Task selesai dan Task aktif.</p></div>
        {#if projects.length === 0}<p class="px-4 py-10 text-sm text-muted-foreground">Belum ada Project aktif pada Unit ini.</p>{:else}<div class="divide-y divide-border/70">{#each projects as project (project.id)}<Link href={projectUrl(project)} class="block px-4 py-4 transition-colors hover:bg-accent/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"><div class="min-w-0"><p class="font-mono text-xs text-muted-foreground">{project.identifier}</p><p class="mt-1 truncate text-sm font-medium">{project.name}</p></div><p class="shrink-0 text-xs text-muted-foreground">{project.done_tasks}/{project.tasks} selesai · {completion(project)}%</p></div><div class="mt-3 h-1.5 overflow-hidden rounded-full bg-muted" aria-label={`${completion(project)}% selesai`}><div class="h-full rounded-full bg-primary transition-[width]" style={`width: ${completion(project)}%`}></div></div></Link>{/each}</div>{/if}
    </section>

    <section class="rounded-lg border border-border/80 bg-card"><div class="border-b border-border/70 px-4 py-3"><h2 class="text-sm font-semibold">Perubahan terbaru</h2></div>{#if activities.length === 0}<p class="px-4 py-10 text-sm text-muted-foreground">Belum ada aktivitas terbaru.</p>{:else}<ol class="divide-y divide-border/70">{#each activities as activity (activity.id)}<li class="px-4 py-3"><p class="text-sm">{activity.description ?? activity.action}</p><p class="mt-1 text-xs text-muted-foreground">{activity.actor?.name ?? 'Sistem'} · {formatDateTime(activity.created_at, workspaceTimezone)}</p></li>{/each}</ol>{/if}</section>
</div>
