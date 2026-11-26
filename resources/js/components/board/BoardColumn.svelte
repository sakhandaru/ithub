<script lang="ts">
    import { createDroppable } from '@dnd-kit/svelte';
    import ListTodo from 'carbon-icons-svelte/lib/Task.svelte';
    import Plus from 'carbon-icons-svelte/lib/Add.svelte';
    import { Link } from '@inertiajs/svelte';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import { toUrl } from '@/lib/utils';
    import BoardTaskCard from './BoardTaskCard.svelte';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null };
    type Status = { value: string; label: string };
    type Task = { id: string; identifier: string; title: string; status: string; status_label: string; priority_label: string | null; assignee: { id: number; name: string } | null; due_date: string | null; milestone: { id: string; name: string; target_date: string | null } | null; module: { id: string; name: string; description: string | null } | null };
    type Grouping = 'none' | 'assignee' | 'milestone';

    let { workspace, project, status, tasks, statuses, allowedTransitions, grouping = 'none', compact, busyTaskId, disabled, isOverdue, statusVariant, taskDetailUrl, onRequestTransition }: {
        workspace: Workspace;
        project: Project;
        status: Status;
        tasks: Task[];
        statuses: Status[];
        allowedTransitions: Record<string, string[]>;
        grouping?: Grouping;
        compact: boolean;
        busyTaskId: string | null;
        disabled: boolean;
        isOverdue: (task: Task) => boolean;
        statusVariant: (status: string) => 'default' | 'secondary' | 'destructive' | 'outline';
        taskDetailUrl: (taskId: string) => string;
        onRequestTransition: (task: Task, target: string) => void;
    } = $props();

    type TaskGroup = { key: string; label: string; tasks: Task[] };

    const groupedTasks = $derived.by((): TaskGroup[] => {
        if (grouping === 'none') return [{ key: 'all', label: '', tasks }];

        const groups = new Map<string, TaskGroup>();
        for (const task of tasks) {
            const key = grouping === 'assignee' ? task.assignee?.id.toString() ?? 'unassigned' : task.milestone?.id ?? 'no-milestone';
            const label = grouping === 'assignee' ? task.assignee?.name ?? 'Belum ada PIC' : task.milestone?.name ?? 'Tanpa milestone';
            const group = groups.get(key) ?? { key, label, tasks: [] };
            group.tasks.push(task);
            groups.set(key, group);
        }

        return [...groups.values()];
    });

    const droppable = createDroppable({
        get id() { return `column:${status.value}`; },
        get data() { return { type: 'column', status: status.value }; },
    });

    function statusDot(statusValue: string): string {
        return {
            backlog: 'text-slate-500 dark:text-slate-400',
            todo: 'text-indigo-600 dark:text-indigo-400',
            in_progress: 'text-amber-600 dark:text-amber-400',
            review: 'text-violet-600 dark:text-violet-400',
            done: 'text-emerald-600 dark:text-emerald-400',
            blocked: 'text-rose-600 dark:text-rose-400',
            canceled: 'text-slate-500 dark:text-slate-400',
        }[statusValue] ?? 'text-muted-foreground';
    }
</script>

<section class="flex h-[calc(100dvh-9rem)] max-h-[calc(100dvh-9rem)] min-h-0 w-[min(86vw,18rem)] shrink-0 snap-start flex-col overflow-y-auto rounded-lg bg-neutral-100 p-2 transition-colors dark:bg-neutral-900/70 sm:w-[18.5rem] sm:snap-none lg:w-[19rem] {droppable.isDropTarget ? 'bg-primary/[0.04]' : ''}" aria-label={status.label}>
    <header class="sticky -top-2 z-20 -mx-2 flex items-center justify-between gap-3 bg-neutral-100 px-3 pb-2 pt-2 dark:bg-neutral-900/70"><div class="flex min-w-0 items-center gap-2"><span class="flex size-5 shrink-0 items-center justify-center rounded-full border-2 border-current {statusDot(status.value)} bg-transparent" aria-hidden="true"><span class="size-1.5 rounded-full bg-current"></span></span><h2 class="truncate text-sm font-semibold tracking-tight">{status.label}</h2></div><span class="shrink-0 text-xs font-medium tabular-nums text-muted-foreground">{tasks.length}</span></header>
    <div {@attach droppable.attach} class="grid min-h-24 shrink-0 gap-2 rounded-md p-0.5 transition-colors {droppable.isDropTarget ? 'bg-primary/[0.06]' : ''}">
        {#each groupedTasks as group (group.key)}
            {#if grouping !== 'none'}<div class="px-1 pt-1 text-[11px] font-medium text-muted-foreground">{group.label}</div>{/if}
            {#each group.tasks as task, index (task.id)}
                <BoardTaskCard {workspace} {project} {task} {index} {statuses} {allowedTransitions} {compact} busy={busyTaskId === task.id} {disabled} {isOverdue} {statusVariant} {taskDetailUrl} {onRequestTransition} />
            {/each}
        {/each}
        {#if tasks.length === 0}<div class="flex items-center justify-center py-8 text-xs text-muted-foreground"><ListTodo class="mr-2 size-4" aria-hidden="true" />Belum ada Task</div>{/if}
    </div>
    <Link href={toUrl(TaskController.index({ workspace: workspace.id, project: project.id }))} class="mt-2 flex min-h-10 shrink-0 items-center gap-2 rounded-md px-2 text-xs font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><Plus class="size-4" aria-hidden="true" />Tambah Task</Link>
</section>
