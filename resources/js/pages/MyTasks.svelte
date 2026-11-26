<script module lang="ts">
    import { myTasks } from '@/routes';

    export const layout = { breadcrumbs: [{ title: 'Tugas saya', href: myTasks() }] };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ListBulleted from 'carbon-icons-svelte/lib/ListBulleted.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import PageBreadcrumb from '@/components/PageBreadcrumb.svelte';
    import { Badge } from '@/components/ui/badge';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import { myTasks as globalMyTasks } from '@/routes';
    import { toUrl } from '@/lib/utils';
    import { globalHeaderLabels, myTasksBreadcrumbs } from '@/lib/page-header';

    type Scope = 'global' | 'unit';
    type Unit = { id: string; name: string; workspace_id: string; workspace_name: string | null };
    type Task = { id: string; identifier: string; title: string; status: string; status_label: string; priority_label: string | null; due_date: string | null; workspace_id: string; project_id: string; project_name: string; project_identifier: string; unit_name: string | null };
    type Stats = { total: number; active: number; overdue: number; completed: number };

    let { scope, unit, units, tasks, stats }: { scope: Scope; unit: Unit | null; units: Unit[]; tasks: Task[]; stats: Stats } = $props();

    const taskUrl = (task: Task): string => toUrl(TaskController.show({ workspace: task.workspace_id, project: task.project_id, task: task.id }));
    const scopeUrl = (nextScope: Scope, nextUnitId?: string): string => nextScope === 'global'
        ? toUrl(globalMyTasks())
        : toUrl(globalMyTasks({ query: { unit: nextUnitId ?? unit?.id ?? '' } }));
</script>

<AppHead title="Tugas saya" />

<div class="flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader>
        {#snippet leading()}
            {@const scopeName = scope === 'unit' ? unit?.name ?? globalHeaderLabels.unit : globalHeaderLabels.allUnits}
            <PageBreadcrumb items={myTasksBreadcrumbs(scopeName, stats.total, scope === 'unit' ? unit?.id : undefined)} showSidebarTrigger />
        {/snippet}
        {#snippet actions()}
            <nav class="flex flex-wrap items-center gap-1 rounded-md border border-border/80 bg-muted/40 p-1" aria-label="Scope Tugas saya">
            <Link href={scopeUrl('global')} aria-current={scope === 'global' ? 'page' : undefined} class="inline-flex min-h-8 items-center gap-2 rounded px-2.5 text-xs font-medium hover:bg-background {scope === 'global' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground'}">Global</Link>
            {#if unit}
                <Link href={scopeUrl('unit')} aria-current={scope === 'unit' ? 'page' : undefined} class="inline-flex min-h-8 items-center gap-2 rounded px-2.5 text-xs font-medium hover:bg-background {scope === 'unit' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground'}">Unit</Link>
            {/if}
            </nav>
        {/snippet}
    </PageHeader>

    {#if scope === 'global' && units.length > 0}
        <section class="flex flex-wrap items-center gap-2" aria-label="Pilih Unit">
            <span class="text-xs font-medium text-muted-foreground">Lihat Unit:</span>
            {#each units as availableUnit (availableUnit.id)}
                <Link href={scopeUrl('unit', availableUnit.id)} class="inline-flex min-h-8 items-center rounded-md border border-border/80 px-2.5 text-xs hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">{availableUnit.name}</Link>
            {/each}
        </section>
    {/if}

    <div class="grid gap-3 sm:grid-cols-4">
        <div class="rounded-lg border border-border/80 bg-card p-4"><p class="text-xs text-muted-foreground">Total</p><p class="mt-2 text-2xl font-semibold">{stats.total}</p></div>
        <div class="rounded-lg border border-border/80 bg-card p-4"><p class="text-xs text-muted-foreground">Aktif</p><p class="mt-2 text-2xl font-semibold">{stats.active}</p></div>
        <div class="rounded-lg border border-border/80 bg-card p-4"><p class="text-xs text-muted-foreground">Terlambat</p><p class="mt-2 text-2xl font-semibold text-destructive">{stats.overdue}</p></div>
        <div class="rounded-lg border border-border/80 bg-card p-4"><p class="text-xs text-muted-foreground">Selesai</p><p class="mt-2 text-2xl font-semibold">{stats.completed}</p></div>
    </div>

    <section class="overflow-hidden rounded-lg border border-border/80 bg-card" aria-label="Daftar Tugas saya">
        <div class="border-b border-border/70 px-4 py-3"><h2 class="text-sm font-semibold">Daftar tugas</h2><p class="mt-1 text-xs text-muted-foreground">{scope === 'unit' ? `Task yang ditugaskan kepada Anda di ${unit?.name ?? 'Unit ini'}.` : 'Task yang ditugaskan kepada Anda di semua Unit.'}</p></div>
        {#if tasks.length === 0}
            <div class="flex min-h-56 flex-col items-center justify-center px-6 py-12 text-center"><ListBulleted class="size-8 text-muted-foreground" aria-hidden="true" /><h2 class="mt-4 text-sm font-semibold">Belum ada tugas</h2><p class="mt-1 text-sm text-muted-foreground">Task yang ditugaskan kepada Anda akan muncul di sini.</p></div>
        {:else}
            <div class="divide-y divide-border/70">
                {#each tasks as task (task.id)}
                    <Link href={taskUrl(task)} class="flex flex-col gap-2 px-4 py-3 transition-colors hover:bg-accent/40 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0"><p class="font-mono text-[11px] text-muted-foreground">{task.identifier}</p><p class="mt-1 truncate text-sm font-medium">{task.title}</p><p class="mt-1 text-xs text-muted-foreground">{task.project_name} · {task.unit_name ?? 'Tanpa Unit'}</p></div>
                        <div class="flex shrink-0 items-center gap-2"><Badge variant={task.status === 'done' ? 'secondary' : 'outline'}>{task.status_label}</Badge>{#if task.priority_label}<Badge variant="outline">{task.priority_label}</Badge>{/if}<span class="text-xs text-muted-foreground">{task.due_date ?? 'Tanpa deadline'}</span></div>
                    </Link>
                {/each}
            </div>
        {/if}
    </section>
</div>
