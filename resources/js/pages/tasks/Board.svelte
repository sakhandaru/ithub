<script module lang="ts">
    import { board } from '@/routes/workspaces/projects';

    export const layout = {
        breadcrumbs: [{ title: 'Board', href: board(['', '']) }],
    };
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import GripVertical from '@lucide/svelte/icons/grip-vertical';
    import ListTodo from '@lucide/svelte/icons/list-todo';
    import MoveHorizontal from '@lucide/svelte/icons/move-horizontal';
    import Rows3 from '@lucide/svelte/icons/rows-3';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import TaskTransitionController from '@/actions/App/Http/Controllers/TaskTransitionController';
    import AppHead from '@/components/AppHead.svelte';
    import ProjectContextNav from '@/components/ProjectContextNav.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null };
    type Status = { value: string; label: string };
    type Task = { id: string; identifier: string; title: string; status: string; status_label: string; priority_label: string | null; assignee: { id: number; name: string } | null; due_date: string | null; labels: Array<{ id: string; name: string }> };

    type Filters = { status: string; priority: string; assignee: string; requester: string; milestone: string; preset: string; no_deadline: boolean; no_evidence: boolean; search: string; sort: string; direction: string };
    let { workspace, project, tasks, statuses, filters, allowedTransitions }: { workspace: Workspace; project: Project; tasks: Task[]; statuses: Status[]; filters: Filters; allowedTransitions: Record<string, string[]> } = $props();
    let optimisticTasks = $state<Task[]>([]);
    let draggedTask = $state<Task | null>(null);
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

    const hasFilters = Object.values(taskQuery()).some((value) => value !== undefined);

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

    function drop(event: DragEvent, status: string): void {
        event.preventDefault();
        if (draggedTask) requestTransition(draggedTask, status);
        draggedTask = null;
    }
</script>

<AppHead title={project.identifier + ' · Board'} />

<div class="flex min-w-0 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-3 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-sm text-muted-foreground">{project.identifier}</p><h1 class="mt-1 text-2xl font-semibold tracking-tight">Board</h1><p class="mt-1 text-sm text-muted-foreground">Pindahkan Task berdasarkan workflow Project.</p></div>
        <div class="flex flex-wrap gap-2"><Button type="button" variant="outline" size="sm" aria-pressed={compact} onclick={toggleCompact}><Rows3 class="size-4" aria-hidden="true" />{compact ? 'Tampilan nyaman' : 'Tampilan ringkas'}</Button><Link href={toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query: taskQuery() }))} class="inline-flex min-h-10 items-center rounded-md border px-3 text-sm hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring">Buka List</Link></div>
    </header>
    <ProjectContextNav {workspace} {project} active="board" taskQuery={taskQuery()} />
    {#if project.archived_at}<div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan dan Board bersifat read-only.</div>{/if}
    {#if transitionError && !transitionOpen}<div class="rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive" role="alert">{transitionError}</div>{/if}
    {#if hasFilters}<div class="flex flex-wrap items-center gap-2 rounded-md border bg-muted/30 px-3 py-2 text-xs text-muted-foreground"><span class="font-medium text-foreground">Filter aktif</span>{#if filters.search}<span>“{filters.search}”</span>{/if}{#if filters.preset}<span>{filters.preset}</span>{/if}{#if filters.status}<span>Status: {filters.status}</span>{/if}{#if filters.priority}<span>Priority: {filters.priority}</span>{/if}{#if filters.assignee}<span>PIC dipilih</span>{/if}<Link href={toUrl(TaskController.board({ workspace: workspace.id, project: project.id }))} class="ml-auto text-foreground underline underline-offset-2">Reset</Link></div>{/if}

    <div class="flex items-center gap-2 rounded-md border bg-muted/30 px-3 py-2 text-xs text-muted-foreground sm:hidden"><MoveHorizontal class="size-4 shrink-0" aria-hidden="true" />Geser ke samping untuk melihat semua status. Gunakan menu pada kartu untuk memindahkan Task.</div>

    <div class="flex gap-3 overflow-x-auto pb-4 snap-x snap-mandatory sm:gap-4 sm:snap-none" role="region" aria-label="Board Task">
        {#each statuses as status (status.value)}
            <section class="w-[min(82vw,18rem)] shrink-0 snap-start rounded-lg border bg-muted/20 p-3 sm:w-[20rem] sm:snap-none" ondragover={(event) => event.preventDefault()} ondrop={(event) => drop(event, status.value)} aria-label={status.label}>
                <header class="sticky top-0 z-10 flex items-center justify-between gap-2 bg-muted/20 px-1 pb-3 pt-1"><div><h2 class="text-sm font-semibold">{status.label}</h2><p class="mt-1 text-[11px] text-muted-foreground">{tasksFor(status.value).length === 1 ? '1 Task' : `${tasksFor(status.value).length} Task`}</p></div><Badge variant={statusVariant(status.value)}>{tasksFor(status.value).length}</Badge></header>
                <div class="grid min-h-28 gap-3 rounded-md border border-dashed p-2">
                    {#each tasksFor(status.value) as task (task.id)}
                        <article draggable={!project.archived_at} ondragstart={() => { draggedTask = task; }} class="rounded-md border bg-card {compact ? 'p-2' : 'p-3'} shadow-sm transition-shadow hover:shadow-md {busyTaskId === task.id ? 'opacity-50' : ''}">
                            <div class="flex items-start gap-2"><GripVertical class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" /><Link href={toUrl(TaskController.show({ workspace: workspace.id, project: project.id, task: task.id }))} class="min-w-0 flex-1 text-sm font-medium hover:underline focus-visible:ring-2 focus-visible:ring-ring">{task.title}</Link></div>
                            <p class="mt-2 font-mono text-[11px] text-muted-foreground">{task.identifier}</p>
                            <div class="mt-3 flex flex-wrap gap-1">{#if task.priority_label}<Badge variant="outline" class="text-[11px]">{task.priority_label}</Badge>{/if}{#each task.labels as label (label.id)}<Badge variant="outline" class="text-[11px]">{label.name}</Badge>{/each}</div>
                            <div class="mt-3 flex items-center justify-between gap-2 text-xs"><span class="truncate text-muted-foreground">{task.assignee?.name ?? 'Belum ada PIC'}</span><span class={isOverdue(task) ? 'font-medium text-destructive' : 'text-muted-foreground'}>{task.due_date ?? 'Tanpa deadline'}</span></div>
                            {#if isOverdue(task)}<p class="mt-2 text-[11px] font-medium text-destructive">Deadline terlewat</p>{/if}
                            <label class="sr-only" for={'move-' + task.id}>Pindahkan {task.identifier}</label><select id={'move-' + task.id} aria-label={'Pindahkan ' + task.identifier} value={task.status} disabled={!!project.archived_at || busyTaskId === task.id || allowedTransitions[task.id]?.length === 0} onchange={(event) => requestTransition(task, event.currentTarget.value)} class="mt-3 h-10 w-full rounded-md border border-input bg-background px-2 text-xs"><option value={task.status}>{allowedTransitions[task.id]?.length ? 'Pindahkan ke...' : 'Tidak ada transisi'}</option>{#each statuses as next (next.value)}{#if allowedTransitions[task.id]?.includes(next.value)}<option value={next.value}>{next.label}</option>{/if}{/each}</select>
                        </article>
                    {/each}
                    {#if tasksFor(status.value).length === 0}<div class="flex items-center justify-center py-8 text-xs text-muted-foreground"><ListTodo class="mr-2 size-4" aria-hidden="true" />Kosong</div>{/if}
                </div>
            </section>
        {/each}
    </div>
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
