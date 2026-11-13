<script module lang="ts">
    import { dashboard } from '@/routes/workspaces/projects';

    export const layout = {
        breadcrumbs: [{ title: 'Dashboard Project', href: dashboard(['', '']) }],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AlertTriangle from '@lucide/svelte/icons/alert-triangle';
    import Ban from '@lucide/svelte/icons/ban';
    import CalendarClock from '@lucide/svelte/icons/calendar-clock';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import CircleDashed from '@lucide/svelte/icons/circle-dashed';
    import ListTodo from '@lucide/svelte/icons/list-todo';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import AppHead from '@/components/AppHead.svelte';
    import ProjectContextNav from '@/components/ProjectContextNav.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null };
    type Stats = { total: number; done: number; progress: number; overdue: number; blocked: number; without_assignee: number; without_deadline: number; status_counts: Record<string, number> };
    type Activity = { id: string; description: string | null; action: string; created_at: string; actor: { id: number; name: string } | null };

    let { workspace, project, stats, activities }: { workspace: Workspace; project: Project; stats: Stats; activities: Activity[] } = $props();

    const taskUrl = (query: Record<string, string | number>): string => toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query }));
</script>

<AppHead title={project.identifier + ' · Dashboard'} />

<div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-3 border-b pb-5 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-sm text-muted-foreground">{project.identifier}</p><h1 class="mt-1 text-2xl font-semibold tracking-tight">Dashboard Project</h1><p class="mt-1 text-sm text-muted-foreground">{project.name}</p></div><Button variant="outline" class="min-h-10" asChild>{#snippet children(props)}<Link href={toUrl(TaskController.index({ workspace: workspace.id, project: project.id }))} {...props}>Lihat semua Task</Link>{/snippet}</Button></header>
    <ProjectContextNav {workspace} {project} active="dashboard" />
    {#if project.archived_at}<div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan.</div>{/if}

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Link href={taskUrl({})} class="rounded-lg border bg-card p-5 hover:bg-muted/30 focus-visible:ring-2 focus-visible:ring-ring"><ListTodo class="size-5 text-muted-foreground" aria-hidden="true" /><p class="mt-4 text-2xl font-semibold">{stats.total}</p><p class="text-sm text-muted-foreground">Total Task</p></Link>
        <Link href={taskUrl({ status: 'done' })} class="rounded-lg border bg-card p-5 hover:bg-muted/30 focus-visible:ring-2 focus-visible:ring-ring"><CheckCircle2 class="size-5 text-muted-foreground" aria-hidden="true" /><p class="mt-4 text-2xl font-semibold">{stats.progress}%</p><p class="text-sm text-muted-foreground">Progress selesai</p></Link>
        <Link href={taskUrl({ status: 'blocked' })} class="rounded-lg border bg-card p-5 hover:bg-muted/30 focus-visible:ring-2 focus-visible:ring-ring"><Ban class="size-5 text-muted-foreground" aria-hidden="true" /><p class="mt-4 text-2xl font-semibold">{stats.blocked}</p><p class="text-sm text-muted-foreground">Task terblokir</p></Link>
        <Link href={taskUrl({})} class="rounded-lg border bg-card p-5 hover:bg-muted/30 focus-visible:ring-2 focus-visible:ring-ring"><AlertTriangle class="size-5 text-muted-foreground" aria-hidden="true" /><p class="mt-4 text-2xl font-semibold">{stats.overdue}</p><p class="text-sm text-muted-foreground">Task terlambat</p></Link>
    </section>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="rounded-lg border bg-card p-5 sm:p-6"><h2 class="text-sm font-semibold">Distribusi status</h2><div class="mt-5 grid gap-3">{#each Object.entries(stats.status_counts) as [status, count]}<div class="flex items-center justify-between gap-3 text-sm"><span class="capitalize text-muted-foreground">{status.replace('_', ' ')}</span><span class="font-medium">{count}</span></div>{/each}</div><div class="mt-6 grid gap-3 border-t pt-5 text-sm"><div class="flex items-center gap-2"><CalendarClock class="size-4 text-muted-foreground" aria-hidden="true" />{stats.without_deadline} Task aktif tanpa deadline</div><div class="flex items-center gap-2"><CircleDashed class="size-4 text-muted-foreground" aria-hidden="true" />{stats.without_assignee} Task aktif tanpa PIC</div></div></section>
        <section class="rounded-lg border bg-card p-5 sm:p-6"><h2 class="text-sm font-semibold">Activity terbaru</h2>{#if activities.length === 0}<p class="mt-4 text-sm text-muted-foreground">Belum ada aktivitas.</p>{:else}<ol class="mt-5 grid gap-4 border-l pl-4">{#each activities as activity (activity.id)}<li><p class="text-sm">{activity.description ?? activity.action}</p><p class="mt-1 text-xs text-muted-foreground">{activity.actor?.name ?? 'Sistem'} · {activity.created_at}</p></li>{/each}</ol>{/if}</section>
    </div>
</div>
