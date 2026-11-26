<script module lang="ts">
    import { dashboard } from '@/routes/workspaces/projects';
    export const layout = { breadcrumbs: [{ title: 'Dashboard Project', href: dashboard(['', '']) }] };
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AlertTriangle from 'carbon-icons-svelte/lib/WarningAlt.svelte';
    import ArrowRight from 'carbon-icons-svelte/lib/ArrowRight.svelte';
    import Ban from 'carbon-icons-svelte/lib/ErrorOutline.svelte';
    import Calendar from 'carbon-icons-svelte/lib/Calendar.svelte';
    import CheckCircle2 from 'carbon-icons-svelte/lib/CheckmarkFilled.svelte';
    import CircleDashed from 'carbon-icons-svelte/lib/CircleDash.svelte';
    import Flag from 'carbon-icons-svelte/lib/Flag.svelte';
    import ListTodo from 'carbon-icons-svelte/lib/Task.svelte';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import AppHead from '@/components/AppHead.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import ProjectProgressSegments from '@/components/ProjectProgressSegments.svelte';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { projectBreadcrumbs } from '@/lib/page-header';
    import { Button } from '@/components/ui/button';
    import { toUrl } from '@/lib/utils';
    import { formatDate, formatDateTime } from '@/lib/format-date';
    import { index as milestonesIndex } from '@/routes/workspaces/projects/milestones';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; icon?: string; archived_at: string | null };
    type StatusCount = { value: string; label: string; count: number };
    type Stats = { total: number; done: number; progress: number; overdue: number; blocked: number; without_assignee: number; without_deadline: number; status_counts: StatusCount[] };
    type Person = { id: number; name: string; avatar?: string | null };
    type TaskPreview = { id: string; identifier: string; title: string; status: string; status_label: string; due_date: string | null; assignee: Person | null };
    type MilestonePreview = { id: string; name: string; target_date: string | null; tasks_count: number; completed_tasks_count: number };
    type Activity = { id: string; description: string | null; action: string; created_at: string; actor: { id: number; name: string } | null; entity?: { label?: string; url?: string } | null };

    let { workspace, project, stats, health, next_milestone, next_tasks, activities }: { workspace: Workspace; project: Project; stats: Stats; health: 'on_track' | 'at_risk' | 'off_track'; next_milestone: MilestonePreview | null; next_tasks: TaskPreview[]; activities: Activity[] } = $props();
    const workspaceTimezone = $derived(page.props.workspaceContext?.active?.timezone);
    const taskUrl = (query: Record<string, string | number>): string => toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query }));
    const taskDetailUrl = $derived((task: TaskPreview): string => toUrl(TaskController.show({ workspace: workspace.id, project: project.id, task: task.id })));
    const milestoneUrl = $derived(toUrl(milestonesIndex({ workspace: workspace.id, project: project.id })));
    const milestoneProgress = $derived(next_milestone && next_milestone.tasks_count > 0 ? Math.min(100, Math.max(0, Math.round((next_milestone.completed_tasks_count / next_milestone.tasks_count) * 100))) : 0);
    const healthLabel = $derived({ on_track: 'On track', at_risk: 'Perlu perhatian', off_track: 'Off track' }[health]);
    const healthDescription = $derived({ on_track: 'Pekerjaan berjalan sesuai rencana.', at_risk: 'Ada beberapa hal yang perlu dibereskan.', off_track: 'Ada hambatan yang perlu segera ditindaklanjuti.' }[health]);
    const healthClass = $derived({ on_track: 'bg-emerald-500', at_risk: 'bg-amber-500', off_track: 'bg-rose-500' }[health]);
    const statusClass = (status: string): string => status === 'done' ? 'bg-emerald-500' : status === 'blocked' ? 'bg-rose-500' : status === 'in_progress' ? 'bg-[#2373F4]' : 'bg-muted-foreground/50';
</script>

<AppHead title={project.identifier + ' · Dashboard'} />

<div class="dashboard-cardless mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-5 sm:px-6 lg:gap-8 lg:px-8">
    <PageHeader breadcrumbs={projectBreadcrumbs(page.props.unitContext?.active?.name ?? workspace.name, page.props.unitContext?.active?.id, toUrl(ProjectController.index(workspace.id)), project.name, toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), 'dashboard', undefined, project.icon)}>
        {#snippet actions()}<Button variant="outline" class="h-7 shrink-0 gap-1.5 border-border/80 bg-background px-2.5 text-xs text-muted-foreground" asChild>{#snippet children(props)}<Link href={taskUrl({})} {...props}>Lihat Task</Link>{/snippet}</Button>{/snippet}
    </PageHeader>

    {#if project.archived_at}<div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan. Data tetap bisa dilihat, tetapi tidak dapat diubah.</div>{/if}

    <section class="rounded-xl border border-border/70 bg-card p-5 sm:p-6"><div class="flex flex-wrap items-start justify-between gap-4"><div><p class="font-mono text-xs text-muted-foreground">{project.identifier}</p><h1 class="mt-1 text-xl font-semibold tracking-tight">{project.name}</h1><p class="mt-2 text-sm text-muted-foreground">{healthDescription}</p></div><div class="inline-flex items-center gap-2 rounded-full border border-border px-3 py-1.5 text-sm"><span class={'size-2 rounded-full ' + healthClass}></span>{healthLabel}</div></div><div class="mt-6"><div class="flex items-center justify-between text-xs"><span class="text-muted-foreground">Progress project</span><span class="font-medium">{stats.progress}%</span></div><ProjectProgressSegments completed={stats.done} total={stats.total} segments={180} /><p class="mt-2 text-xs text-muted-foreground">{stats.done} dari {stats.total} task selesai</p></div></section>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4"><Link href={taskUrl({})} class="rounded-xl border border-border/70 bg-card p-4 transition-colors hover:bg-accent/30"><div class="flex items-center gap-2 text-xs text-muted-foreground"><ListTodo class="size-4" aria-hidden="true" />Total task</div><p class="mt-3 text-2xl font-semibold tracking-tight">{stats.total}</p><p class="mt-1 text-xs text-muted-foreground">Semua pekerjaan</p></Link><Link href={taskUrl({ status: 'done' })} class="rounded-xl border border-border/70 bg-card p-4 transition-colors hover:bg-accent/30"><div class="flex items-center gap-2 text-xs text-muted-foreground"><CheckCircle2 class="size-4 text-emerald-500" aria-hidden="true" />Selesai</div><p class="mt-3 text-2xl font-semibold tracking-tight">{stats.done}</p><p class="mt-1 text-xs text-muted-foreground">{stats.progress}% dari total task</p></Link><Link href={taskUrl({ preset: 'overdue' })} class="rounded-xl border border-border/70 bg-card p-4 transition-colors hover:bg-accent/30"><div class="flex items-center gap-2 text-xs text-muted-foreground"><AlertTriangle class="size-4 text-amber-500" aria-hidden="true" />Terlambat</div><p class="mt-3 text-2xl font-semibold tracking-tight">{stats.overdue}</p><p class="mt-1 text-xs text-muted-foreground">Melewati deadline</p></Link><Link href={taskUrl({ preset: 'blocked' })} class="rounded-xl border border-border/70 bg-card p-4 transition-colors hover:bg-accent/30"><div class="flex items-center gap-2 text-xs text-muted-foreground"><Ban class="size-4 text-rose-500" aria-hidden="true" />Terblokir</div><p class="mt-3 text-2xl font-semibold tracking-tight">{stats.blocked}</p><p class="mt-1 text-xs text-muted-foreground">Perlu ditindaklanjuti</p></Link></section>

    <div class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(18rem,0.8fr)]"><section class="rounded-xl border border-border/70 bg-card p-5"><div class="flex items-start justify-between gap-4"><div><h2 class="text-sm font-semibold">Perlu perhatian</h2><p class="mt-1 text-xs text-muted-foreground">Hal-hal yang bisa menghambat pekerjaan.</p></div><AlertTriangle class="size-5 text-amber-500" aria-hidden="true" /></div><div class="mt-4 divide-y divide-border/60"><Link href={taskUrl({ preset: 'overdue' })} class="flex items-center gap-3 py-3 text-sm transition-colors hover:text-foreground"><AlertTriangle class="size-4 text-amber-500" aria-hidden="true" /><span class="flex-1">Terlambat</span><span class="tabular-nums text-muted-foreground">{stats.overdue}</span><ArrowRight class="size-3 text-muted-foreground" aria-hidden="true" /></Link><Link href={taskUrl({ preset: 'blocked' })} class="flex items-center gap-3 py-3 text-sm transition-colors hover:text-foreground"><Ban class="size-4 text-rose-500" aria-hidden="true" /><span class="flex-1">Terblokir</span><span class="tabular-nums text-muted-foreground">{stats.blocked}</span><ArrowRight class="size-3 text-muted-foreground" aria-hidden="true" /></Link><Link href={taskUrl({ preset: 'unassigned' })} class="flex items-center gap-3 py-3 text-sm transition-colors hover:text-foreground"><CircleDashed class="size-4 text-muted-foreground" aria-hidden="true" /><span class="flex-1">Tanpa PIC</span><span class="tabular-nums text-muted-foreground">{stats.without_assignee}</span><ArrowRight class="size-3 text-muted-foreground" aria-hidden="true" /></Link><Link href={taskUrl({ preset: 'no_deadline' })} class="flex items-center gap-3 py-3 text-sm transition-colors hover:text-foreground"><Calendar class="size-4 text-amber-500" aria-hidden="true" /><span class="flex-1">Tanpa deadline</span><span class="tabular-nums text-muted-foreground">{stats.without_deadline}</span><ArrowRight class="size-3 text-muted-foreground" aria-hidden="true" /></Link></div></section><section class="flex flex-col rounded-xl border border-border/70 bg-card p-5"><div class="flex items-start justify-between gap-4"><div><h2 class="text-sm font-semibold">Milestone berikutnya</h2><p class="mt-1 text-xs text-muted-foreground">Target terdekat dalam project.</p></div><Flag class="size-5 text-muted-foreground" aria-hidden="true" /></div>{#if next_milestone}<Link href={milestoneUrl} class="mt-5 flex flex-1 flex-col justify-between rounded-lg bg-muted/20 p-4 transition-colors hover:bg-accent/30"><div><p class="text-base font-medium tracking-tight">{next_milestone.name}</p><p class="mt-2 flex items-center gap-2 text-xs text-muted-foreground"><Calendar class="size-3.5" aria-hidden="true" />{formatDate(next_milestone.target_date)}</p></div><div class="mt-6"><div class="flex items-center justify-between text-xs text-muted-foreground"><span>{next_milestone.completed_tasks_count}/{next_milestone.tasks_count} task selesai</span><span>{milestoneProgress}%</span></div><ProjectProgressSegments completed={next_milestone.completed_tasks_count} total={next_milestone.tasks_count} segments={72} /><div class="mt-4 flex items-center justify-between text-xs font-medium text-muted-foreground"><span>Lihat milestone</span><ArrowRight class="size-3.5" aria-hidden="true" /></div></div></Link>{:else}<div class="mt-5 flex flex-1 items-center rounded-lg bg-muted/20 p-4 text-sm text-muted-foreground">Belum ada milestone mendatang.</div>{/if}</section></div>

<div class="grid gap-4 lg:grid-cols-[minmax(0,1.35fr)_minmax(18rem,0.65fr)]"><section class="rounded-xl border border-border/70 bg-card p-5"><div class="flex items-center justify-between"><div><h2 class="text-sm font-semibold">Task berikutnya</h2><p class="mt-1 text-xs text-muted-foreground">Pekerjaan aktif yang paling dekat untuk dikerjakan.</p></div><Link href={taskUrl({})} class="text-xs text-muted-foreground hover:text-foreground">Semua task <ArrowRight class="ml-1 inline size-3" aria-hidden="true" /></Link></div>{#if next_tasks.length === 0}<p class="mt-5 text-sm text-muted-foreground">Belum ada task aktif.</p>{:else}<div class="mt-4 divide-y divide-border/60">{#each next_tasks as task (task.id)}<Link href={taskDetailUrl(task)} class="flex min-w-0 items-center gap-3 py-3 transition-colors hover:bg-accent/30"><span class={'size-2 shrink-0 rounded-full ' + statusClass(task.status)}></span><div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{task.title}</p><p class="mt-1 text-xs text-muted-foreground">{task.identifier} · {task.status_label}</p></div>{#if task.assignee}<span class="inline-flex shrink-0 items-center gap-2 rounded-full border border-border px-2 py-1 text-xs text-muted-foreground"><UserAvatar user={task.assignee} size="size-5" label={false} />{task.assignee.name}</span>{:else}<CircleDashed class="size-4 shrink-0 text-muted-foreground" aria-label="Belum ada PIC" />{/if}<span class="shrink-0 text-xs text-muted-foreground">{formatDate(task.due_date)}</span></Link>{/each}</div>{/if}</section><section class="rounded-xl border border-border/70 bg-card p-5"><div class="flex items-center justify-between"><div><h2 class="text-sm font-semibold">Distribusi status</h2><p class="mt-1 text-xs text-muted-foreground">Ringkasan posisi task.</p></div><Link href={taskUrl({})} class="text-xs text-muted-foreground hover:text-foreground">Lihat <ArrowRight class="ml-1 inline size-3" aria-hidden="true" /></Link></div><div class="mt-4 divide-y divide-border/60">{#each stats.status_counts as status}<Link href={taskUrl({ status: status.value })} class="flex items-center gap-3 py-2.5 text-sm hover:text-foreground"><span class={'size-2 rounded-full ' + statusClass(status.value)}></span><span class="flex-1">{status.label}</span><span class="tabular-nums text-muted-foreground">{status.count}</span><ArrowRight class="size-3 text-muted-foreground" aria-hidden="true" /></Link>{/each}</div></section></div>

    <section class="rounded-xl border border-border/70 bg-card p-5"><div class="flex items-center justify-between"><div><h2 class="text-sm font-semibold">Aktivitas terbaru</h2><p class="mt-1 text-xs text-muted-foreground">Perubahan terakhir pada project dan task.</p></div><Link href={toUrl(ProjectController.activity({ workspace: workspace.id, project: project.id }))} class="text-xs text-muted-foreground hover:text-foreground">Lihat semua <ArrowRight class="ml-1 inline size-3" aria-hidden="true" /></Link></div>{#if activities.length === 0}<p class="mt-5 text-sm text-muted-foreground">Belum ada aktivitas.</p>{:else}<ol class="mt-4 grid gap-3">{#each activities as activity (activity.id)}<li class="flex gap-3 text-sm"><span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-[#2373F4]"></span><div class="min-w-0 flex-1"><p class="truncate">{activity.description ?? activity.action}{#if activity.entity?.label}<span class="text-muted-foreground"> · {activity.entity.label}</span>{/if}</p><div class="mt-1 inline-flex items-center gap-2 rounded-full border border-border px-2 py-1 text-xs text-muted-foreground">{#if activity.actor}<UserAvatar user={activity.actor} size="size-4" label={false} />{activity.actor.name}{:else}Sistem{/if}</div><span class="mt-1 block text-xs text-muted-foreground">{formatDateTime(activity.created_at, workspaceTimezone)}</span></div></li>{/each}</ol>{/if}</section>
</div>

<style>
    :global(.dashboard-cardless) {
        gap: 1rem;
    }

    :global(.dashboard-cardless section.rounded-xl.border.border-border\/70.bg-card) {
        padding: 1rem;
    }

    :global(.dashboard-cardless section.rounded-xl.border.border-border\/70.bg-card.sm\:p-6) {
        padding: 1.25rem;
    }

    :global(.dashboard-cardless .mt-6) {
        margin-top: 1rem;
    }

    :global(.dashboard-cardless .mt-5) {
        margin-top: 1rem;
    }

    :global(.dashboard-cardless .mt-4) {
        margin-top: 0.75rem;
    }

    :global(.dashboard-cardless .py-3) {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }

    :global(.dashboard-cardless section.rounded-xl.border.border-border\/70.bg-card),
    :global(.dashboard-cardless a.rounded-xl.border.border-border\/70.bg-card) {
        border-color: transparent;
        border-radius: 0;
        background: transparent;
    }

    :global(.dashboard-cardless section.rounded-xl.border.border-border\/70.bg-card) {
        border-bottom: 1px solid hsl(var(--border) / 0.6);
    }

    :global(.dashboard-cardless a.rounded-lg.border.border-border\/70) {
        border-color: transparent;
        border-radius: 0;
        background: hsl(var(--muted) / 0.22);
    }
</style>
