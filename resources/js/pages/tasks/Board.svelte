<script module lang="ts">
    import { board } from '@/routes/workspaces/projects';

    export const layout = {
        breadcrumbs: [{ title: 'Board', href: board(['', '']) }],
    };
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import { DragDropProvider } from '@dnd-kit/svelte';
    import type { DragEndEvent, DragOverEvent, DragStartEvent } from '@dnd-kit/dom';
    import { onMount } from 'svelte';
    import MoveHorizontal from 'carbon-icons-svelte/lib/ArrowsHorizontal.svelte';
    import Rows3 from 'carbon-icons-svelte/lib/List.svelte';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import TaskTransitionController from '@/actions/App/Http/Controllers/TaskTransitionController';
    import AppHead from '@/components/AppHead.svelte';
    import ProjectContextNav from '@/components/ProjectContextNav.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import { taskBreadcrumbs } from '@/lib/page-header';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
    import { toUrl } from '@/lib/utils';
    import BoardColumn from '@/components/board/BoardColumn.svelte';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null };
    type Status = { value: string; label: string };
    type Task = { id: string; identifier: string; title: string; status: string; status_label: string; priority_label: string | null; assignee: { id: number; name: string } | null; due_date: string | null; labels: Array<{ id: string; name: string }> };

    type Filters = { status: string; priority: string; assignee: string; requester: string; milestone: string; preset: string; no_deadline: boolean; no_evidence: boolean; search: string; sort: string; direction: string };
    let { workspace, project, tasks, statuses, filters, allowedTransitions, embedded = false }: { workspace: Workspace; project: Project; tasks: Task[]; statuses: Status[]; filters: Filters; allowedTransitions: Record<string, string[]>; embedded?: boolean } = $props();
    let optimisticTasks = $state<Task[]>([]);
    let dragSnapshot = $state<Task[]>([]);
    let draggedTaskId = $state<string | null>(null);
    let busyTaskId = $state<string | null>(null);
    let transitionTask = $state<Task | null>(null);
    let transitionTarget = $state('');
    let transitionReason = $state('');
    let transitionCompletionNote = $state('');
    let transitionError = $state('');
    let transitionOpen = $state(false);
    let compact = $state(false);
    const preferencesKey = $derived(`sitop:task-preferences:${page.props.auth?.user?.id ?? 'guest'}:${project.id}`);

    onMount(() => {
        try {
            const stored = localStorage.getItem(preferencesKey);
            if (stored) compact = (JSON.parse(stored) as { compact?: boolean }).compact ?? false;
        } catch {
            compact = false;
        }
    });

    function toggleCompact(): void {
        compact = !compact;
        localStorage.setItem(preferencesKey, JSON.stringify({ compact }));
    }

    $effect(() => {
        optimisticTasks = tasks;
    });

    function tasksFor(status: string): Task[] {
        return optimisticTasks.filter((task) => task.status === status);
    }

    function isOverdue(task: Task): boolean {
        return Boolean(task.due_date && !['done', 'canceled'].includes(task.status) && new Date(`${task.due_date}T23:59:59`) < new Date());
    }

    function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
        if (status === 'blocked' || status === 'canceled') return 'destructive';
        if (status === 'done') return 'secondary';

        return 'outline';
    }

    const taskQuery = (): Record<string, string | number | undefined> => ({
        preset: filters.preset || undefined,
        status: filters.status || undefined,
        priority: filters.priority || undefined,
        assignee: filters.assignee || undefined,
        requester: filters.requester || undefined,
        milestone: filters.milestone || undefined,
        no_deadline: filters.no_deadline ? 1 : undefined,
        no_evidence: filters.no_evidence ? 1 : undefined,
        search: filters.search || undefined,
        sort: filters.sort || undefined,
        direction: filters.direction || undefined,
    });

    function submitTransition(task: Task, target: string, reason: string, completionNote: string): void {
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
            onError: () => { optimisticTasks = previous; transitionError = 'Perubahan status gagal disimpan. Task dikembalikan ke status sebelumnya.'; },
        });
    }

    function requestTransition(task: Task, target: string): void {
        if (task.status === target || project.archived_at) return;
        if (!allowedTransitions[task.id]?.includes(target)) {
            transitionError = `Perpindahan ${task.identifier} ke status tersebut tidak diizinkan.`;
            return;
        }
        const needsReason = target === 'blocked' || target === 'canceled' || (task.status === 'done' && target === 'in_progress');
        const needsCompletionNote = target === 'done';
        if (!needsReason && !needsCompletionNote) {
            submitTransition(task, target, '', '');
            return;
        }

        transitionTask = task;
        transitionTarget = target;
        transitionReason = '';
        transitionCompletionNote = '';
        transitionError = '';
        transitionOpen = true;
    }

    function confirmTransition(): void {
        if (!transitionTask) return;
        const needsReason = transitionTarget === 'blocked' || transitionTarget === 'canceled' || (transitionTask.status === 'done' && transitionTarget === 'in_progress');
        const needsCompletionNote = transitionTarget === 'done';
        if (needsReason && !transitionReason.trim()) {
            transitionError = 'Alasan wajib diisi untuk perubahan status ini.';
            return;
        }
        if (needsCompletionNote && !transitionCompletionNote.trim()) {
            transitionError = 'Catatan penyelesaian atau URL bukti wajib diisi sebelum Done.';
            return;
        }

        const task = transitionTask;
        transitionOpen = false;
        submitTransition(task, transitionTarget, transitionReason, transitionCompletionNote);
    }

    function cancelTransition(): void {
        transitionOpen = false;
        transitionTask = null;
        transitionTarget = '';
        transitionReason = '';
        transitionCompletionNote = '';
        transitionError = '';
    }

    function handleDragStart(event: DragStartEvent): void {
        draggedTaskId = event.operation.source ? String(event.operation.source.id) : null;
        dragSnapshot = optimisticTasks;
    }

    function targetStatus(event: DragOverEvent): string | null {
        const targetId = event.operation.target?.id;
        if (!targetId) return null;

        const value = String(targetId);
        if (value.startsWith('column:')) return value.slice('column:'.length);

        return optimisticTasks.find((task) => task.id === value)?.status ?? null;
    }

    function handleDragOver(event: DragOverEvent): void {
        if (!draggedTaskId) return;
        const status = targetStatus(event);
        if (!status) return;

        optimisticTasks = optimisticTasks.map((task) => task.id === draggedTaskId ? { ...task, status, status_label: statuses.find((item) => item.value === status)?.label ?? status } : task);
    }

    function resetDragState(): void {
        optimisticTasks = dragSnapshot;
        dragSnapshot = [];
        draggedTaskId = null;
    }

    function handleDragEnd(event: DragEndEvent): void {
        const task = dragSnapshot.find((item) => item.id === draggedTaskId);
        const movedTask = optimisticTasks.find((item) => item.id === draggedTaskId);
        const target = movedTask?.status;

        resetDragState();

        if (event.canceled || !task || !target || task.status === target) return;
        requestTransition(task, target);
    }

</script>

{#if !embedded}
    <AppHead title={project.identifier + ' · Board'} />
{/if}

<div class={embedded ? 'min-w-0' : 'flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8'}>
    {#if !embedded}
        <PageHeader breadcrumbs={taskBreadcrumbs(page.props.unitContext?.active?.name ?? workspace.name, page.props.unitContext?.active?.id, toUrl(ProjectController.index(workspace.id)), project.name, toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), toUrl(TaskController.index({ workspace: workspace.id, project: project.id })), tasks.length)} showSidebarTrigger>
        {#snippet actions()}
            <ProjectContextNav {workspace} {project} active="board" taskQuery={taskQuery()} />
            <Button type="button" variant="outline" size="sm" aria-pressed={compact} onclick={toggleCompact}><Rows3 class="size-4" aria-hidden="true" />{compact ? 'Tampilan nyaman' : 'Tampilan ringkas'}</Button>
            <Link href={toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query: taskQuery() }))} class="inline-flex min-h-8 items-center rounded-md border px-2.5 text-xs hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring">Buka List</Link>
        {/snippet}
    </PageHeader>
    {/if}
    {#if project.archived_at}<div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan dan Board bersifat read-only.</div>{/if}
    {#if transitionError && !transitionOpen}<div class="rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive" role="alert">{transitionError}</div>{/if}
    <div class="flex items-center gap-2 rounded-md border border-border/80 bg-surface-2/50 px-3 py-2 text-xs text-muted-foreground sm:hidden"><MoveHorizontal class="size-4 shrink-0" aria-hidden="true" />Geser ke samping untuk melihat semua status. Gunakan menu pada kartu untuk memindahkan Task.</div>

    <DragDropProvider onDragStart={handleDragStart} onDragOver={handleDragOver} onDragEnd={handleDragEnd}>
        <div class="min-w-0 max-w-full overflow-x-auto overflow-y-hidden pb-4" role="region" aria-label="Board Task">
            <div class="flex w-max min-w-full gap-2.5 snap-x snap-mandatory sm:gap-3 sm:snap-none">
                {#each statuses as status (status.value)}
                    <BoardColumn {workspace} {project} {status} tasks={tasksFor(status.value)} {statuses} {allowedTransitions} {compact} {busyTaskId} disabled={!!project.archived_at} {isOverdue} {statusVariant} onRequestTransition={requestTransition} />
                {/each}
            </div>
        </div>
    </DragDropProvider>
</div>

<Dialog bind:open={transitionOpen} onOpenChange={(open) => { if (!open) cancelTransition(); }}>
    <DialogContent class="max-w-xl">
        <DialogTitle>Konfirmasi perubahan status</DialogTitle>
        <DialogDescription>
            {#if transitionTask}Pindahkan {transitionTask.identifier} ke {statuses.find((status) => status.value === transitionTarget)?.label ?? transitionTarget}. Perubahan ini akan tercatat di Activity Log dan Audit Log.{/if}
        </DialogDescription>
        {#if transitionError}<p class="mt-4 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive" role="alert">{transitionError}</p>{/if}
        {#if transitionTarget === 'blocked' || transitionTarget === 'canceled' || (transitionTask?.status === 'done' && transitionTarget === 'in_progress')}
            <label class="mt-5 grid gap-2 text-sm font-medium" for="board-transition-reason">Alasan perubahan <span class="font-normal text-muted-foreground">(wajib)</span><textarea id="board-transition-reason" bind:value={transitionReason} rows={4} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm font-normal outline-none focus-visible:ring-2 focus-visible:ring-ring" placeholder="Jelaskan konteks perubahan status"></textarea></label>
        {/if}
        {#if transitionTarget === 'done'}
            <label class="mt-5 grid gap-2 text-sm font-medium" for="board-completion-note">Catatan penyelesaian atau URL bukti <span class="font-normal text-muted-foreground">(wajib)</span><textarea id="board-completion-note" bind:value={transitionCompletionNote} rows={4} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm font-normal outline-none focus-visible:ring-2 focus-visible:ring-ring" placeholder="Apa yang selesai atau di mana buktinya?"></textarea></label>
        {/if}
        <DialogFooter>
            <Button variant="outline" onclick={cancelTransition}>Batal</Button>
            <Button onclick={confirmTransition}>Simpan perubahan</Button>
        </DialogFooter>
    </DialogContent>
</Dialog>
