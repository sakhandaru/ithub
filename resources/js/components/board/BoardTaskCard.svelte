<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { createSortable } from '@dnd-kit/svelte/sortable';
    import Draggable from 'carbon-icons-svelte/lib/Draggable.svelte';
    import CalendarDays from 'carbon-icons-svelte/lib/Calendar.svelte';
    import CircleAlert from 'carbon-icons-svelte/lib/WarningAlt.svelte';
    import CircleDashed from 'carbon-icons-svelte/lib/CircleDash.svelte';
    import Flag from 'carbon-icons-svelte/lib/Flag.svelte';
    import Grid from 'carbon-icons-svelte/lib/Grid.svelte';
    import UserAvatar from '@/components/UserAvatar.svelte';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null };
    type Status = { value: string; label: string };
    type Task = { id: string; identifier: string; title: string; status: string; status_label: string; priority_label: string | null; assignee: { id: number; name: string; avatar?: string | null } | null; due_date: string | null; milestone: { id: string; name: string; target_date: string | null } | null; module: { id: string; name: string; description: string | null } | null };

    let { workspace, project, task, index, statuses, allowedTransitions, compact, busy, disabled, isOverdue, statusVariant, taskDetailUrl, onRequestTransition }: {
        workspace: Workspace;
        project: Project;
        task: Task;
        index: number;
        statuses: Status[];
        allowedTransitions: Record<string, string[]>;
        compact: boolean;
        busy: boolean;
        disabled: boolean;
        isOverdue: (task: Task) => boolean;
        statusVariant: (status: string) => 'default' | 'secondary' | 'destructive' | 'outline';
        taskDetailUrl: (taskId: string) => string;
        onRequestTransition: (task: Task, target: string) => void;
    } = $props();

    const sortable = createSortable({
        get id() { return task.id; },
        get index() { return index; },
        get group() { return task.status; },
        get disabled() { return disabled || busy; },
        data: { type: 'task' },
    });

    function statusColor(statusValue: string): string {
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

    function priorityColor(priority: string | null): string {
        const value = priority?.toLowerCase();

        if (value === 'urgent' || value === 'high' || value === 'tinggi') return 'text-rose-500 dark:text-rose-400';
        if (value === 'medium' || value === 'sedang') return 'text-amber-500 dark:text-amber-400';
        if (value === 'low' || value === 'rendah') return 'text-sky-500 dark:text-sky-400';

        return 'text-muted-foreground';
    }
</script>

<article
    {@attach sortable.attach}
    class="rounded-md border border-border/70 bg-card {compact ? 'p-2' : 'p-2.5'} shadow-none transition-[opacity,border-color,background-color] hover:border-border hover:bg-accent/20 {busy ? 'opacity-50' : ''} {sortable.isDragSource ? 'border-primary bg-accent shadow-lg' : ''}"
    aria-label={task.identifier + ' ' + task.title}
>
    <div class="flex items-start gap-2">
        <button
            type="button"
            {@attach sortable.attachHandle}
            class="order-2 -mr-1 -mt-1 flex size-8 shrink-0 cursor-grab items-center justify-center rounded-md text-muted-foreground/70 hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring active:cursor-grabbing disabled:cursor-not-allowed disabled:opacity-50"
            aria-label={'Seret ' + task.identifier}
            disabled={disabled || busy}
        >
            <Draggable class="size-4" aria-hidden="true" />
        </button>
        <div class="min-w-0 flex-1">
            <Link href={taskDetailUrl(task.id)} preserveScroll class="block text-[11px] font-medium tracking-tight text-muted-foreground hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">{task.identifier}</Link>
            <Link href={taskDetailUrl(task.id)} preserveScroll class="mt-0.5 block text-sm font-medium leading-5 text-foreground hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">{task.title}</Link>
        </div>
    </div>
    <div class="mt-2.5 flex flex-wrap gap-1.5 text-[11px]">
        <span class="inline-flex min-h-6 items-center gap-1 whitespace-nowrap rounded-md border border-border/70 bg-background px-1.5 leading-none {statusColor(task.status)}"><span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>{task.status_label}</span>
        {#if task.priority_label}<span class="inline-flex min-h-6 items-center gap-1 whitespace-nowrap rounded-md border border-border/70 bg-background px-1.5 leading-none text-muted-foreground"><CircleAlert class="size-3 {priorityColor(task.priority_label)}" aria-hidden="true" />{task.priority_label}</span>{/if}
        {#if task.milestone}<span class="inline-flex min-h-6 max-w-40 items-center gap-1 whitespace-nowrap rounded-md border border-border/70 bg-background px-1.5 leading-none text-muted-foreground"><Flag class="size-3 shrink-0 text-slate-500 dark:text-slate-400" aria-hidden="true" /><span class="truncate">{task.milestone.name}</span></span>{/if}
        {#if task.module}<span class="inline-flex min-h-6 max-w-40 items-center gap-1 whitespace-nowrap rounded-md border border-border/70 bg-background px-1.5 leading-none text-muted-foreground"><Grid class="size-3 shrink-0 text-slate-500 dark:text-slate-400" aria-hidden="true" /><span class="truncate">{task.module.name}</span></span>{/if}
        {#if task.due_date}<span class="inline-flex min-h-6 items-center gap-1 whitespace-nowrap rounded-md border border-border/70 bg-background px-1.5 leading-none {isOverdue(task) ? 'text-destructive' : 'text-muted-foreground'}"><CalendarDays class="size-3 shrink-0 {isOverdue(task) ? 'text-destructive' : 'text-slate-500 dark:text-slate-400'}" aria-hidden="true" />{task.due_date}</span>{/if}
        {#if task.assignee}<span class="inline-flex min-h-6 items-center gap-1 whitespace-nowrap rounded-md border border-border/70 bg-background px-1.5 leading-none text-muted-foreground"><UserAvatar user={task.assignee} size="size-4" label={false} />{task.assignee.name}</span>{:else}<span class="inline-flex min-h-6 items-center gap-1 whitespace-nowrap rounded-md border border-dashed border-border/70 px-1.5 leading-none text-muted-foreground"><CircleDashed class="size-3.5 shrink-0" aria-hidden="true" />Belum ada PIC</span>{/if}
    </div>
    {#if isOverdue(task)}<p class="mt-2 text-[11px] font-medium text-destructive">Deadline terlewat</p>{/if}
</article>
