<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = { breadcrumbs: [{ title: 'Beranda', href: dashboard() }] };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Activity from '@lucide/svelte/icons/activity';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import AppHead from '@/components/AppHead.svelte';
    import { Badge } from '@/components/ui/badge';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string; name: string; timezone: string };
    type Task = { id: string; identifier: string; title: string; project_id: string; status?: string; due_date?: string | null };
    type Project = { id: string; name: string; identifier: string; unit: string | null; tasks: number; active_tasks: number; overdue_tasks: number; blocked_tasks: number; attention: number };
    type ActivityItem = { id: string; action: string; description: string; created_at: string | null; actor: { id: number; name: string } | null };
    type Stats = { my_tasks: number; overdue: number; blocked: number; active: number };

    let { workspace, stats, projects, myTasks, overdueTasks, blockedTasks, focus, focusTasks, activities }: { workspace: Workspace; stats: Stats; projects: Project[]; myTasks: Task[]; overdueTasks: Task[]; blockedTasks: Task[]; focus: string | null; focusTasks: Task[]; activities: ActivityItem[] } = $props();

    const taskUrl = (task: Task): string => toUrl(TaskController.show({ workspace: workspace.id, project: task.project_id, task: task.id }));
    const focusUrl = (value: string): string => toUrl(dashboard({ query: { focus: value } }));
    const clearFocusUrl = toUrl(dashboard());
    const focusLabels: Record<string, string> = { my_tasks: 'Task saya', overdue: 'Task terlambat', blocked: 'Task Blocked' };
    const activityTime = (value: string | null): string => value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) : 'Baru saja';
</script>

<AppHead title="Beranda" />

<div class="flex min-w-0 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <header>
        <p class="text-sm text-muted-foreground">Workspace</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">{workspace.name}</h1>
        <p class="mt-1 text-sm text-muted-foreground">Mulai dari pekerjaan yang paling membutuhkan perhatian hari ini.</p>
    </header>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {#each [['my_tasks', 'Task saya'], ['overdue', 'Terlambat'], ['blocked', 'Blocked'], ['active', 'Task aktif']] as item}
            {#if item[0] === 'active'}
                <div class="rounded-lg border bg-card p-4"><p class="text-sm text-muted-foreground">{item[1]}</p><p class="mt-2 text-2xl font-semibold">{stats[item[0] as keyof Stats]}</p></div>
            {:else}
                <Link href={focusUrl(item[0])} aria-current={focus === item[0] ? 'page' : undefined} class="rounded-lg border bg-card p-4 transition-colors hover:border-foreground/30 hover:bg-muted/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {focus === item[0] ? 'border-foreground/50 bg-muted/40' : ''}">
                    <p class="text-sm text-muted-foreground">{item[1]}</p><p class="mt-2 text-2xl font-semibold">{stats[item[0] as keyof Stats]}</p><p class="mt-1 text-xs text-muted-foreground">Buka daftar Task →</p>
                </Link>
            {/if}
        {/each}
    </div>

    {#if focus}
        <section class="rounded-lg border bg-card">
            <div class="flex items-center justify-between gap-3 border-b px-4 py-3"><div><h2 class="text-sm font-semibold">{focusLabels[focus]}</h2><p class="mt-1 text-xs text-muted-foreground">Daftar lintas Project sesuai fokus yang dipilih.</p></div><Link href={clearFocusUrl} class="text-xs text-muted-foreground hover:text-foreground hover:underline">Reset fokus</Link></div>
            {#if focusTasks.length === 0}<p class="px-4 py-8 text-sm text-muted-foreground">Tidak ada Task pada fokus ini.</p>{:else}<div class="divide-y">{#each focusTasks as task (task.id)}<Link href={taskUrl(task)} class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-muted/40"><div class="min-w-0"><p class="font-mono text-xs text-muted-foreground">{task.identifier}</p><p class="mt-1 truncate text-sm font-medium">{task.title}</p></div><p class="shrink-0 text-xs text-muted-foreground">{task.due_date ?? 'Tanpa deadline'}</p></Link>{/each}</div>{/if}
        </section>
    {/if}

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-lg border bg-card"><div class="flex items-center justify-between border-b px-4 py-3"><h2 class="text-sm font-semibold">Task saya</h2><Link href={focusUrl('my_tasks')} class="text-xs text-muted-foreground hover:text-foreground hover:underline">Lihat semua</Link></div>{#if myTasks.length === 0}<p class="px-4 py-8 text-sm text-muted-foreground">Tidak ada Task aktif yang ditugaskan kepada Anda.</p>{:else}<div class="divide-y">{#each myTasks as task (task.id)}<Link href={taskUrl(task)} class="block px-4 py-3 hover:bg-muted/40"><p class="font-mono text-xs text-muted-foreground">{task.identifier}</p><p class="mt-1 text-sm font-medium">{task.title}</p><p class="mt-1 text-xs text-muted-foreground">{task.due_date ? `Deadline ${task.due_date}` : 'Tanpa deadline'}</p></Link>{/each}</div>{/if}</section>
        <section class="rounded-lg border bg-card"><div class="flex items-center justify-between border-b px-4 py-3"><div><h2 class="text-sm font-semibold">Project perlu perhatian</h2><p class="mt-1 text-xs text-muted-foreground">Urutan berdasarkan Task terlambat dan Blocked.</p></div><Link href={toUrl(ProjectController.index(workspace.id))} class="text-xs text-muted-foreground hover:text-foreground hover:underline">Lihat semua</Link></div>{#if projects.length === 0}<p class="px-4 py-8 text-sm text-muted-foreground">Belum ada Project.</p>{:else}<div class="divide-y">{#each projects.slice(0, 6) as project (project.id)}<Link href={toUrl(ProjectController.show({ workspace: workspace.id, project: project.id }))} class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-muted/40"><div class="min-w-0"><p class="font-mono text-xs text-muted-foreground">{project.identifier}</p><p class="mt-1 truncate text-sm font-medium">{project.name}</p><p class="mt-1 text-xs text-muted-foreground">{project.active_tasks} Task aktif · {project.unit ?? 'Tanpa Unit'}</p></div><div class="flex shrink-0 items-center gap-2">{#if project.blocked_tasks > 0}<Badge variant="destructive">{project.blocked_tasks} Blocked</Badge>{:else if project.overdue_tasks > 0}<Badge variant="outline">{project.overdue_tasks} terlambat</Badge>{:else}<Badge variant="secondary">Sehat</Badge>{/if}<ArrowRight class="size-4 text-muted-foreground" aria-hidden="true" /></div></Link>{/each}</div>{/if}</section>
        <section class="rounded-lg border bg-card"><div class="flex items-center justify-between border-b px-4 py-3"><h2 class="text-sm font-semibold">Terlambat</h2><Link href={focusUrl('overdue')} class="text-xs text-muted-foreground hover:text-foreground hover:underline">Lihat semua</Link></div>{#if overdueTasks.length === 0}<p class="px-4 py-8 text-sm text-muted-foreground">Tidak ada Task terlambat.</p>{:else}<div class="divide-y">{#each overdueTasks as task (task.id)}<Link href={taskUrl(task)} class="block px-4 py-3 hover:bg-muted/40"><p class="text-sm font-medium">{task.identifier} · {task.title}</p><p class="mt-1 text-xs text-destructive">Deadline {task.due_date}</p></Link>{/each}</div>{/if}</section>
        <section class="rounded-lg border bg-card"><div class="flex items-center justify-between border-b px-4 py-3"><h2 class="text-sm font-semibold">Blocked</h2><Link href={focusUrl('blocked')} class="text-xs text-muted-foreground hover:text-foreground hover:underline">Lihat semua</Link></div>{#if blockedTasks.length === 0}<p class="px-4 py-8 text-sm text-muted-foreground">Tidak ada Task Blocked.</p>{:else}<div class="divide-y">{#each blockedTasks as task (task.id)}<Link href={taskUrl(task)} class="block px-4 py-3 hover:bg-muted/40"><p class="text-sm font-medium">{task.identifier} · {task.title}</p></Link>{/each}</div>{/if}</section>
        <section class="rounded-lg border bg-card"><div class="flex items-center justify-between border-b px-4 py-3"><div><h2 class="text-sm font-semibold">Perubahan terbaru</h2><p class="mt-1 text-xs text-muted-foreground">Aktivitas terakhir di workspace.</p></div><Activity class="size-4 text-muted-foreground" aria-hidden="true" /></div>{#if activities.length === 0}<p class="px-4 py-8 text-sm text-muted-foreground">Belum ada aktivitas terbaru.</p>{:else}<div class="divide-y">{#each activities as activity (activity.id)}<div class="px-4 py-3"><div class="flex items-start justify-between gap-3"><p class="text-sm font-medium">{activity.description}</p><span class="shrink-0 text-xs text-muted-foreground">{activityTime(activity.created_at)}</span></div><p class="mt-1 text-xs text-muted-foreground">{activity.actor?.name ?? 'Sistem'} · {activity.action}</p></div>{/each}</div>{/if}</section>
    </div>
</div>
