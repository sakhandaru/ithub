<script module lang="ts">
    import { board } from '@/routes/workspaces/projects';

    export const layout = {
        breadcrumbs: [{ title: 'Board', href: board(['', '']) }],
    };
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import GripVertical from '@lucide/svelte/icons/grip-vertical';
    import ListTodo from '@lucide/svelte/icons/list-todo';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import TaskTransitionController from '@/actions/App/Http/Controllers/TaskTransitionController';
    import AppHead from '@/components/AppHead.svelte';
    import ProjectContextNav from '@/components/ProjectContextNav.svelte';
    import { Badge } from '@/components/ui/badge';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null };
    type Status = { value: string; label: string };
    type Task = { id: string; identifier: string; title: string; status: string; status_label: string; priority_label: string | null; assignee: { id: number; name: string } | null; due_date: string | null; labels: Array<{ id: string; name: string }> };

    let { workspace, project, tasks, statuses }: { workspace: Workspace; project: Project; tasks: Task[]; statuses: Status[] } = $props();
    let optimisticTasks = $state<Task[]>([]);
    let draggedTask = $state<Task | null>(null);
    let busyTaskId = $state<string | null>(null);

    $effect(() => {
        optimisticTasks = tasks;
    });

    function tasksFor(status: string): Task[] {
        return optimisticTasks.filter((task) => task.status === status);
    }

    function transition(task: Task, target: string): void {
        if (task.status === target || project.archived_at) return;
        let reason = '';
        let completionNote = '';
        if (target === 'blocked' || target === 'canceled' || (task.status === 'done' && target === 'in_progress')) {
            reason = window.prompt('Alasan perubahan status:') ?? '';
            if (!reason.trim()) return;
        }
        if (target === 'done') {
            completionNote = window.prompt('Catatan penyelesaian atau URL bukti:') ?? '';
            if (!completionNote.trim()) return;
        }

        const previous = optimisticTasks;
        optimisticTasks = optimisticTasks.map((item) => item.id === task.id ? { ...item, status: target, status_label: statuses.find((status) => status.value === target)?.label ?? target } : item);
        busyTaskId = task.id;
        router.post(toUrl(TaskTransitionController.url({ workspace: workspace.id, project: project.id, task: task.id })), {
            status: target,
            reason,
            completion_note: completionNote,
        }, {
            preserveScroll: true,
            onFinish: () => { busyTaskId = null; },
            onError: () => { optimisticTasks = previous; },
        });
    }

    function drop(event: DragEvent, status: string): void {
        event.preventDefault();
        if (draggedTask) transition(draggedTask, status);
        draggedTask = null;
    }
</script>

<AppHead title={project.identifier + ' · Board'} />

<div class="flex min-w-0 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-3 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-sm text-muted-foreground">{project.identifier}</p><h1 class="mt-1 text-2xl font-semibold tracking-tight">Board</h1><p class="mt-1 text-sm text-muted-foreground">Pindahkan Task berdasarkan workflow Project.</p></div>
        <Link href={toUrl(TaskController.index({ workspace: workspace.id, project: project.id }))} class="inline-flex min-h-10 items-center rounded-md border px-3 text-sm hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring">Buka List</Link>
    </header>
    <ProjectContextNav {workspace} {project} active="board" />
    {#if project.archived_at}<div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan dan Board bersifat read-only.</div>{/if}

    <div class="flex gap-4 overflow-x-auto pb-3" aria-label="Board Task">
        {#each statuses as status (status.value)}
            <section class="w-[18rem] shrink-0 rounded-lg border bg-muted/20 p-3 sm:w-[20rem]" ondragover={(event) => event.preventDefault()} ondrop={(event) => drop(event, status.value)} aria-label={status.label}>
                <header class="flex items-center justify-between gap-2 px-1 pb-3"><h2 class="text-sm font-semibold">{status.label}</h2><Badge variant="outline">{tasksFor(status.value).length}</Badge></header>
                <div class="grid min-h-28 gap-3 rounded-md border border-dashed p-2">
                    {#each tasksFor(status.value) as task (task.id)}
                        <article draggable={!project.archived_at} ondragstart={() => { draggedTask = task; }} class="rounded-md border bg-card p-3 shadow-sm {busyTaskId === task.id ? 'opacity-50' : ''}">
                            <div class="flex items-start gap-2"><GripVertical class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" /><Link href={toUrl(TaskController.show({ workspace: workspace.id, project: project.id, task: task.id }))} class="min-w-0 flex-1 text-sm font-medium hover:underline focus-visible:ring-2 focus-visible:ring-ring">{task.title}</Link></div>
                            <p class="mt-2 font-mono text-[11px] text-muted-foreground">{task.identifier}</p>
                            <div class="mt-3 flex flex-wrap gap-1">{#if task.priority_label}<Badge variant="outline" class="text-[11px]">{task.priority_label}</Badge>{/if}{#each task.labels as label (label.id)}<Badge variant="outline" class="text-[11px]">{label.name}</Badge>{/each}</div>
                            <div class="mt-3 flex items-center justify-between gap-2 text-xs text-muted-foreground"><span>{task.assignee?.name ?? 'Belum ada PIC'}</span><span>{task.due_date ?? '—'}</span></div>
                            <select aria-label={'Pindahkan ' + task.identifier} value={task.status} disabled={!!project.archived_at || busyTaskId === task.id} onchange={(event) => transition(task, event.currentTarget.value)} class="mt-3 h-9 w-full rounded-md border border-input bg-background px-2 text-xs"><option value={task.status}>Pindahkan ke...</option>{#each statuses as next (next.value)}{#if next.value !== task.status}<option value={next.value}>{next.label}</option>{/if}{/each}</select>
                        </article>
                    {/each}
                    {#if tasksFor(status.value).length === 0}<div class="flex items-center justify-center py-8 text-xs text-muted-foreground"><ListTodo class="mr-2 size-4" aria-hidden="true" />Kosong</div>{/if}
                </div>
            </section>
        {/each}
    </div>
</div>
