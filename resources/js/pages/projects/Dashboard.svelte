<script module lang="ts">
    import { dashboard } from '@/routes/workspaces/projects';

    export const layout = {
        breadcrumbs: [{ title: 'Dashboard Project', href: dashboard(['', '']) }],
    };
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AlertTriangle from 'carbon-icons-svelte/lib/WarningAlt.svelte';
    import Ban from 'carbon-icons-svelte/lib/ErrorOutline.svelte';
    import CalendarClock from 'carbon-icons-svelte/lib/Time.svelte';
    import CheckCircle2 from 'carbon-icons-svelte/lib/CheckmarkFilled.svelte';
    import CircleDashed from 'carbon-icons-svelte/lib/CircleDash.svelte';
    import ListTodo from 'carbon-icons-svelte/lib/Task.svelte';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import AppHead from '@/components/AppHead.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import { projectBreadcrumbs } from '@/lib/page-header';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { toUrl } from '@/lib/utils';
    import { formatDateTime } from '@/lib/format-date';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null };
    type Stats = { total: number; done: number; progress: number; overdue: number; blocked: number; without_assignee: number; without_deadline: number; status_counts: Record<string, number> };
    type Activity = { id: string; description: string | null; action: string; created_at: string; actor: { id: number; name: string } | null };

    let { workspace, project, stats, activities }: { workspace: Workspace; project: Project; stats: Stats; activities: Activity[] } = $props();
    const workspaceTimezone = $derived(page.props.workspaceContext?.active?.timezone);

    const taskUrl = (query: Record<string, string | number>): string => toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query }));
</script>

<AppHead title={project.identifier + ' · Dashboard'} />

<div class="mx-auto flex w-full max-w-6xl flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader breadcrumbs={projectBreadcrumbs(page.props.unitContext?.active?.name ?? workspace.name, page.props.unitContext?.active?.id, toUrl(ProjectController.index(workspace.id)), project.name, toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), 'dashboard')}>
        {#snippet actions()}<Button variant="outline" class="min-h-10" asChild>{#snippet children(props)}<Link href={toUrl(TaskController.index({ workspace: workspace.id, project: project.id }))} {...props}>Lihat semua Task</Link>{/snippet}</Button>{/snippet}
    </PageHeader>
    {#if project.archived_at}<div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan.</div>{/if}

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Link href={taskUrl({})} class="rounded-lg border border-border/80 bg-card p-4 transition-colors hover:bg-accent/40 focus-visible:ring-2 focus-visible:ring-ring"><ListTodo class="size-5 text-muted-foreground" aria-hidden="true" /><p class="mt-4 text-2xl font-semibold">{stats.total}</p><p class="text-sm text-muted-foreground">Total Task</p></Link>
        <Link href={taskUrl({ status: 'done' })} class="rounded-lg border border-border/80 bg-card p-4 transition-colors hover:bg-accent/40 focus-visible:ring-2 focus-visible:ring-ring"><CheckCircle2 class="size-5 text-muted-foreground" aria-hidden="true" /><p class="mt-4 text-2xl font-semibold">{stats.progress}%</p><p class="text-sm text-muted-foreground">Progress selesai</p></Link>
        <Link href={taskUrl({ status: 'blocked' })} class="rounded-lg border border-border/80 bg-card p-4 transition-colors hover:bg-accent/40 focus-visible:ring-2 focus-visible:ring-ring"><Ban class="size-5 text-muted-foreground" aria-hidden="true" /><p class="mt-4 text-2xl font-semibold">{stats.blocked}</p><p class="text-sm text-muted-foreground">Task terblokir</p></Link>
        <Link href={taskUrl({ preset: 'overdue' })} class="rounded-lg border border-border/80 bg-card p-4 transition-colors hover:bg-accent/40 focus-visible:ring-2 focus-visible:ring-ring"><AlertTriangle class="size-5 text-muted-foreground" aria-hidden="true" /><p class="mt-4 text-2xl font-semibold">{stats.overdue}</p><p class="text-sm text-muted-foreground">Task terlambat</p></Link>
    </section>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="rounded-lg border border-border/80 bg-card p-4 sm:p-5"><h2 class="text-sm font-semibold">Distribusi status</h2><div class="mt-5 grid gap-3">{#each Object.entries(stats.status_counts) as [status, count]}<div class="flex items-center justify-between gap-3 text-sm"><span class="capitalize text-muted-foreground">{status.replace('_', ' ')}</span><span class="font-medium">{count}</span></div>{/each}</div><div class="mt-6 grid gap-3 border-t border-border/70 pt-5 text-sm"><div class="flex items-center gap-2"><CalendarClock class="size-4 text-muted-foreground" aria-hidden="true" />{stats.without_deadline} Task aktif tanpa deadline</div><div class="flex items-center gap-2"><CircleDashed class="size-4 text-muted-foreground" aria-hidden="true" />{stats.without_assignee} Task aktif tanpa PIC</div></div></section>
        <section class="rounded-lg border border-border/80 bg-card p-4 sm:p-5"><h2 class="text-sm font-semibold">Activity terbaru</h2>{#if activities.length === 0}<p class="mt-4 text-sm text-muted-foreground">Belum ada aktivitas.</p>{:else}<ol class="mt-5 grid gap-4 border-l pl-4">{#each activities as activity (activity.id)}<li><p class="text-sm">{activity.description ?? activity.action}</p><p class="mt-1 text-xs text-muted-foreground">{activity.actor?.name ?? 'Sistem'} · {formatDateTime(activity.created_at, workspaceTimezone)}</p></li>{/each}</ol>{/if}</section>
    </div>
</div>
