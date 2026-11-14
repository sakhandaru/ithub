<script module lang="ts">
    import { index } from '@/routes/workspaces/projects/tasks';
    export const layout = { breadcrumbs: [{ title: 'Task', href: index(['', '']) }] };
</script>

<script lang="ts">
    import { Form, Link, page, router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import Filter from '@lucide/svelte/icons/filter';
    import ListTodo from '@lucide/svelte/icons/list-todo';
    import Search from '@lucide/svelte/icons/search';
    import Rows3 from '@lucide/svelte/icons/rows-3';
    import SlidersHorizontal from '@lucide/svelte/icons/sliders-horizontal';
    import Pencil from '@lucide/svelte/icons/pencil';
    import X from '@lucide/svelte/icons/x';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import SavedViewController from '@/actions/App/Http/Controllers/SavedViewController';
    import AppHead from '@/components/AppHead.svelte';
    import ProjectContextNav from '@/components/ProjectContextNav.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
    import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null };
    type Option = { value: string; label: string };
    type Person = { id: number; name: string };
    type TaskLabel = { id: string; name: string; color: string | null };
    type Task = {
        id: string; identifier: string; title: string; description: string | null;
        status: string; status_label: string; priority: string | null; priority_label: string | null;
        assignee: Person | null; requester: Person | null; labels: TaskLabel[];
        due_date: string | null; completed_at: string | null; milestone: { id: string; name: string; target_date: string | null } | null;
    };

    type Member = { id: number; name: string }; type Milestone = { id: string; name: string };
    type TaskGroup = { key: string; label: string; tasks: Task[] };
    type SavedView = { id: string; name: string; filters: Record<string, string | number | undefined>; grouping: 'none' | 'status' | 'assignee' | 'milestone'; compact: boolean };
    type LegacySavedView = { id: string; name: string; query: Record<string, string | number | undefined>; grouping: SavedView['grouping']; compact: boolean };
    let { workspace, project, tasks, selectedTask, filters, statuses, priorities, filterMembers, filterMilestones, savedViews: initialSavedViews, activeSavedViewId: initialActiveSavedViewId, savedViewWarnings, canCreate, canBulkUpdate }: {
        workspace: Workspace; project: Project; tasks: Task[]; selectedTask: Task | null;
        filters: { status: string; priority: string; assignee: string; requester: string; milestone: string; preset: string; no_deadline: boolean; no_evidence: boolean; search: string; sort: string; direction: string };
        statuses: Option[]; priorities: Option[]; filterMembers: Member[]; filterMilestones: Milestone[]; savedViews: SavedView[]; activeSavedViewId: string | null; savedViewWarnings: string[]; canCreate: boolean; canBulkUpdate: boolean;
    } = $props();
    let compact = $state(false);
    let grouping = $state<'none' | 'status' | 'assignee' | 'milestone'>('none');
    let savedViews = $state<SavedView[]>([]);
    let saveViewOpen = $state(false);
    let deleteViewOpen = $state(false);
    let deleteViewTarget = $state<SavedView | null>(null);
    let editViewOpen = $state(false);
    let editViewTarget = $state<SavedView | null>(null);
    let migrationOpen = $state(false);
    let migrationProcessing = $state(false);
    let migrationIndex = $state(0);
    let migrationError = $state('');
    let legacyViews = $state<LegacySavedView[]>([]);
    let viewName = $state('');
    let editGrouping = $state<SavedView['grouping']>('none');
    let editCompact = $state(false);
    let selectedTaskIds = $state<string[]>([]);
    let activeSavedViewId = $derived(initialActiveSavedViewId);
    let applyingSavedViewId = $state<string | null>(null);
    let filterOpen = $state(false);
    const savedViewsKey = $derived(`sitop:task-views:${project.id}`);
    const migrationKey = $derived(`sitop:task-views-migrated:${page.props.auth?.user?.id ?? 'guest'}:${project.id}`);
    const preferencesKey = $derived(`sitop:task-preferences:${page.props.auth?.user?.id ?? 'guest'}:${project.id}`);

    onMount(() => {
        try {
            const stored = localStorage.getItem(savedViewsKey);
            if (stored) {
                const parsed = JSON.parse(stored) as LegacySavedView[];
                legacyViews = parsed.filter((view) => view.name && view.query);
                if (legacyViews.length > 0 && !localStorage.getItem(migrationKey)) migrationOpen = true;
            }
            const preferences = localStorage.getItem(preferencesKey);
            if (preferences) {
                const parsed = JSON.parse(preferences) as { compact?: boolean; grouping?: SavedView['grouping'] };
                compact = parsed.compact ?? false;
                grouping = parsed.grouping ?? 'none';
            }
        } catch {
            savedViews = [];
        }
    });

    $effect(() => {
        savedViews = initialSavedViews;
    });

    const taskListUrl = (query: Record<string, string | number | undefined> = {}): string =>
        toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query }));

    const preservedQuery = (includeSavedView = false): Record<string, string | number | undefined> => ({
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
        view: includeSavedView ? activeSavedViewId ?? undefined : undefined,
    });

    function closePeek(): string {
        return taskListUrl(preservedQuery(true));
    }

    const presets = [
        { key: '', label: 'Semua Task' },
        { key: 'mine', label: 'Task saya' },
        { key: 'overdue', label: 'Terlambat' },
        { key: 'blocked', label: 'Blocked' },
        { key: 'unassigned', label: 'Tanpa PIC' },
    ];

    const activeFilterChips = $derived([
        filters.status ? { key: 'status', label: `Status: ${statuses.find((item) => item.value === filters.status)?.label ?? filters.status}` } : null,
        filters.priority ? { key: 'priority', label: `Priority: ${priorities.find((item) => item.value === filters.priority)?.label ?? filters.priority}` } : null,
        filters.assignee ? { key: 'assignee', label: filters.assignee === 'none' ? 'Tanpa PIC' : `PIC: ${filterMembers.find((item) => String(item.id) === filters.assignee)?.name ?? filters.assignee}` } : null,
        filters.milestone ? { key: 'milestone', label: filters.milestone === 'none' ? 'Tanpa milestone' : `Milestone: ${filterMilestones.find((item) => item.id === filters.milestone)?.name ?? filters.milestone}` } : null,
        filters.no_deadline ? { key: 'no_deadline', label: 'Tanpa deadline' } : null,
        filters.no_evidence ? { key: 'no_evidence', label: 'Tanpa bukti' } : null,
        filters.preset ? { key: 'preset', label: presets.find((preset) => preset.key === filters.preset)?.label ?? filters.preset } : null,
        filters.search ? { key: 'search', label: `Cari: ${filters.search}` } : null,
    ].filter((chip): chip is { key: string; label: string } => chip !== null));

    function clearFilter(key: string): string {
        const query = preservedQuery();
        delete query[key];
        return taskListUrl(query);
    }

    const groupedTasks = $derived.by((): TaskGroup[] => {
        if (grouping === 'none') return [{ key: 'all', label: 'Semua Task', tasks }];

        const groups = new Map<string, TaskGroup>();
        for (const task of tasks) {
            const key = grouping === 'status'
                ? task.status
                : grouping === 'assignee'
                    ? task.assignee?.id?.toString() ?? 'unassigned'
                    : task.milestone?.id ?? 'no-milestone';
            const label = grouping === 'status'
                ? task.status_label
                : grouping === 'assignee'
                    ? task.assignee?.name ?? 'Belum ada PIC'
                    : task.milestone?.name ?? 'Tanpa milestone';
            const group = groups.get(key) ?? { key, label, tasks: [] };
            group.tasks.push(task);
            groups.set(key, group);
        }

        return [...groups.values()];
    });

    function persistPreferences(): void {
        localStorage.setItem(preferencesKey, JSON.stringify({ compact, grouping }));
    }

    function toggleCompact(): void {
        compact = !compact;
        persistPreferences();
    }

    function setGrouping(value: string): void {
        grouping = value as SavedView['grouping'];
        persistPreferences();
    }

    const allVisibleSelected = $derived(tasks.length > 0 && tasks.every((task) => selectedTaskIds.includes(task.id)));

    function toggleTask(taskId: string): void {
        selectedTaskIds = selectedTaskIds.includes(taskId) ? selectedTaskIds.filter((id) => id !== taskId) : [...selectedTaskIds, taskId];
    }

    function toggleAllTasks(): void {
        selectedTaskIds = allVisibleSelected ? [] : tasks.map((task) => task.id);
    }

    function applySavedView(view: SavedView): void {
        grouping = view.grouping;
        compact = view.compact;
        persistPreferences();
        applyingSavedViewId = view.id;
        router.visit(taskListUrl({ ...view.filters, view: view.id }), {
            onFinish: () => { applyingSavedViewId = null; },
        });
    }

    function deleteSavedView(view: SavedView): void {
        deleteViewTarget = view;
        deleteViewOpen = true;
    }

    function editSavedView(view: SavedView): void {
        editViewTarget = view;
        viewName = view.name;
        editGrouping = view.grouping;
        editCompact = view.compact;
        editViewOpen = true;
    }

    function migrateNextLegacyView(): void {
        const view = legacyViews[migrationIndex];
        if (!view) {
            localStorage.removeItem(savedViewsKey);
            localStorage.setItem(migrationKey, '1');
            migrationProcessing = false;
            migrationOpen = false;

            return;
        }

        router.post(SavedViewController.store.url({ workspace: workspace.id, project: project.id }), {
            name: view.name,
            filters: Object.fromEntries(Object.entries(view.query).filter(([, value]) => value !== undefined)),
            grouping: view.grouping,
            compact: view.compact,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                migrationIndex += 1;
                migrateNextLegacyView();
            },
            onError: () => {
                migrationProcessing = false;
                migrationError = `View “${view.name}” belum berhasil dipindahkan. Coba lagi nanti.`;
            },
        });
    }

    function startMigration(): void {
        migrationError = '';
        migrationProcessing = true;
        migrationIndex = 0;
        migrateNextLegacyView();
    }

    function skipMigration(): void {
        localStorage.setItem(migrationKey, '1');
        migrationOpen = false;
    }
</script>

<AppHead title={project.identifier + ' · Task'} />

<div class="flex min-w-0 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                <Link href={toUrl(TaskController.index({ workspace: workspace.id, project: project.id }))} class="hover:text-foreground hover:underline">{project.identifier}</Link>
                <span aria-hidden="true">/</span><span>Task</span>
            </div>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight">{project.name}</h1>
            <p class="mt-1 text-sm text-muted-foreground">Pilih prioritas kerja, buka konteks, lalu lanjutkan Task tanpa kehilangan posisi.</p>
        </div>
        {#if selectedTask}
            <Link href={closePeek()} class="inline-flex min-h-10 items-center gap-2 rounded-md border px-3 text-sm hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring"><X class="size-4" aria-hidden="true" />Tutup detail</Link>
        {/if}
    </header>

    <ProjectContextNav {workspace} {project} active="tasks" taskQuery={preservedQuery()} />

    <div class={selectedTask ? 'grid min-w-0 gap-6 lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-start' : ''}>
        <div class="min-w-0 grid gap-6">

    {#if project.archived_at}
        <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan. Task bersifat read-only sampai Project dipulihkan.</div>
    {/if}

    {#if canCreate && !project.archived_at}
        <section class="rounded-lg border bg-card p-4">
            <Form {...TaskController.store.form({ workspace: workspace.id, project: project.id })} class="flex flex-col gap-3 sm:flex-row sm:items-end">
                {#snippet children({ errors, processing })}
                    <div class="min-w-0 flex-1">
                        <Label for="quick-task-title">Buat Task</Label>
                        <Input id="quick-task-title" name="title" class="mt-2 min-h-11" placeholder="Apa yang perlu dikerjakan?" required maxlength={200} autocomplete="off" />
                        <InputError message={errors.title} />
                    </div>
                    <Button type="submit" class="min-h-11" disabled={processing}>{processing ? 'Membuat...' : 'Tambah Task'}</Button>
                {/snippet}
            </Form>
        </section>
    {/if}

    <section class="rounded-lg border bg-card p-4">
        <div class="flex flex-col gap-3 border-b pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold">Mulai dari fokus kerja</h2>
                <p class="mt-1 text-xs text-muted-foreground">Preset ini membantu menemukan Task yang perlu ditangani lebih dulu.</p>
            </div>
            <div class="flex flex-wrap gap-2" aria-label="Preset Task">
                {#each presets as preset (preset.key)}
                    <Link href={taskListUrl({ ...preservedQuery(), preset: preset.key || undefined })} class="inline-flex min-h-9 items-center rounded-md border px-3 text-xs font-medium transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {filters.preset === preset.key ? 'border-foreground bg-foreground text-background hover:bg-foreground' : 'bg-background'}" aria-current={filters.preset === preset.key ? 'page' : undefined}>{preset.label}</Link>
                {/each}
            </div>
        </div>
        <div class="lg:hidden">
            <Sheet bind:open={filterOpen}>
                <SheetTrigger asChild>
                    {#snippet children(props)}
                        <Button variant="outline" class="min-h-11 w-full justify-between" {...props}>
                            <span class="inline-flex items-center gap-2"><SlidersHorizontal class="size-4" aria-hidden="true" />Filter Task</span>
                            {#if activeFilterChips.length > 0}<Badge variant="secondary">{activeFilterChips.length}</Badge>{/if}
                        </Button>
                    {/snippet}
                </SheetTrigger>
                <SheetContent side="bottom" class="max-h-[88vh] rounded-t-xl">
                    <SheetHeader><SheetTitle>Filter Task</SheetTitle></SheetHeader>
                    <form method="get" action={taskListUrl()} class="mt-5 grid gap-4 overflow-y-auto pb-4">
                        <input type="hidden" name="preset" value={filters.preset} />
                        <div><Label for="mobile-task-search">Cari Task</Label><Input id="mobile-task-search" name="search" value={filters.search} class="mt-2 min-h-11" placeholder="ID atau judul" /></div>
                        <div><Label for="mobile-task-status">Status</Label><select id="mobile-task-status" name="status" value={filters.status} class="mt-2 h-11 w-full rounded-md border border-input bg-background px-3 text-sm"><option value="">Semua status</option>{#each statuses as status (status.value)}<option value={status.value}>{status.label}</option>{/each}</select></div>
                        <div><Label for="mobile-task-priority">Priority</Label><select id="mobile-task-priority" name="priority" value={filters.priority} class="mt-2 h-11 w-full rounded-md border border-input bg-background px-3 text-sm"><option value="">Semua priority</option>{#each priorities as priority (priority.value)}<option value={priority.value}>{priority.label}</option>{/each}</select></div>
                        <div><Label for="mobile-task-assignee">PIC</Label><select id="mobile-task-assignee" name="assignee" value={filters.assignee} class="mt-2 h-11 w-full rounded-md border border-input bg-background px-3 text-sm"><option value="">Semua PIC</option><option value="none">Tanpa PIC</option>{#each filterMembers as member (member.id)}<option value={member.id}>{member.name}</option>{/each}</select></div>
                        <div><Label for="mobile-task-milestone">Milestone</Label><select id="mobile-task-milestone" name="milestone" value={filters.milestone} class="mt-2 h-11 w-full rounded-md border border-input bg-background px-3 text-sm"><option value="">Semua milestone</option><option value="none">Tanpa milestone</option>{#each filterMilestones as milestone (milestone.id)}<option value={milestone.id}>{milestone.name}</option>{/each}</select></div>
                        <label class="flex min-h-11 items-center gap-3 text-sm"><input class="size-4" type="checkbox" name="no_deadline" value="1" checked={filters.no_deadline} />Tanpa deadline</label>
                        <label class="flex min-h-11 items-center gap-3 text-sm"><input class="size-4" type="checkbox" name="no_evidence" value="1" checked={filters.no_evidence} />Tanpa bukti</label>
                        <div class="flex gap-2"><Button type="submit" class="min-h-11 flex-1"><Filter class="size-4" aria-hidden="true" />Terapkan filter</Button><Link href={taskListUrl()} class="inline-flex min-h-11 items-center rounded-md border px-3 text-sm" onclick={() => { filterOpen = false; }}>Reset</Link></div>
                    </form>
                </SheetContent>
            </Sheet>
        </div>
        <form method="get" action={taskListUrl()} class="hidden gap-3 sm:grid sm:grid-cols-2 lg:flex lg:items-end">
            <input type="hidden" name="preset" value={filters.preset} />
            <div class="sm:col-span-2 lg:min-w-64 lg:flex-1">
                <Label for="task-search">Cari Task</Label>
                <div class="relative mt-2"><Search class="pointer-events-none absolute left-3 top-3 size-4 text-muted-foreground" aria-hidden="true" /><Input id="task-search" name="search" value={filters.search} class="min-h-11 pl-9" placeholder="ID atau judul" /></div>
            </div>
            <div><Label for="task-status">Status</Label><select id="task-status" name="status" value={filters.status} class="mt-2 h-11 w-full rounded-md border border-input bg-background px-3 text-sm"><option value="">Semua status</option>{#each statuses as status (status.value)}<option value={status.value}>{status.label}</option>{/each}</select></div>
            <div><Label for="task-priority">Priority</Label><select id="task-priority" name="priority" value={filters.priority} class="mt-2 h-11 w-full rounded-md border border-input bg-background px-3 text-sm"><option value="">Semua priority</option>{#each priorities as priority (priority.value)}<option value={priority.value}>{priority.label}</option>{/each}</select></div>
            <div><Label for="task-assignee">PIC</Label><select id="task-assignee" name="assignee" value={filters.assignee} class="mt-2 h-11 w-full rounded-md border border-input bg-background px-3 text-sm"><option value="">Semua PIC</option><option value="none">Tanpa PIC</option>{#each filterMembers as member (member.id)}<option value={member.id}>{member.name}</option>{/each}</select></div>
            <div><Label for="task-milestone">Milestone</Label><select id="task-milestone" name="milestone" value={filters.milestone} class="mt-2 h-11 w-full rounded-md border border-input bg-background px-3 text-sm"><option value="">Semua milestone</option><option value="none">Tanpa milestone</option>{#each filterMilestones as milestone (milestone.id)}<option value={milestone.id}>{milestone.name}</option>{/each}</select></div>
            <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="no_deadline" value="1" checked={filters.no_deadline} />Tanpa deadline</label>
            <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="no_evidence" value="1" checked={filters.no_evidence} />Tanpa bukti</label>
            <Button type="submit" variant="outline" class="min-h-11"><Filter class="size-4" aria-hidden="true" />Filter</Button>
            <Link href={taskListUrl()} class="min-h-10 text-sm text-muted-foreground hover:text-foreground hover:underline focus-visible:ring-2 focus-visible:ring-ring">Reset filter</Link>
        </form>
    </section>

    {#if activeFilterChips.length > 0}
        <div class="flex flex-wrap items-center gap-2" aria-label="Filter aktif"><span class="text-xs font-medium text-muted-foreground">Filter aktif:</span>{#each activeFilterChips as chip (chip.key)}<Link href={clearFilter(chip.key)} class="inline-flex min-h-8 items-center rounded-full border bg-muted/40 px-3 text-xs hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">{chip.label}<span class="ml-2 text-muted-foreground" aria-hidden="true">×</span><span class="sr-only">Hapus filter {chip.label}</span></Link>{/each}</div>
    {/if}

    {#if savedViews.length > 0}<div class="flex flex-wrap items-center gap-2" aria-label="View tersimpan"><span class="text-xs font-medium text-muted-foreground">View tersimpan:</span>{#each savedViews as view (view.id)}<span class="inline-flex min-h-11 items-center rounded-full border text-xs {activeSavedViewId === view.id ? 'border-foreground bg-muted' : 'bg-muted/40'}"><button type="button" class="min-h-11 px-3 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-current={activeSavedViewId === view.id ? 'true' : undefined} disabled={applyingSavedViewId !== null} onclick={() => applySavedView(view)}>{applyingSavedViewId === view.id ? 'Menerapkan…' : view.name}</button><button type="button" class="min-h-11 border-l px-3 text-muted-foreground hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label={'Edit view ' + view.name} onclick={() => editSavedView(view)}><Pencil class="size-3.5" aria-hidden="true" /></button><button type="button" class="min-h-11 border-l px-3 text-muted-foreground hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label={'Hapus view ' + view.name} onclick={() => deleteSavedView(view)}>×</button></span>{/each}</div>{/if}

    {#if savedViewWarnings.length > 0}
        <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">{savedViewWarnings.length} filter pada Saved View ini sudah tidak tersedia dan diabaikan.</div>
    {/if}

    {#if tasks.length === 0}
        <section class="flex min-h-64 items-center justify-center rounded-lg border border-dashed bg-card px-6 py-12 text-center"><div class="max-w-md"><ListTodo class="mx-auto size-8 text-muted-foreground" aria-hidden="true" /><h2 class="mt-4 text-sm font-semibold">Belum ada Task yang cocok</h2><p class="mt-1 text-sm leading-6 text-muted-foreground">{activeFilterChips.length > 0 ? 'Coba ubah preset atau filter untuk melihat Task lain.' : 'Buat Task pertama untuk mulai bekerja.'}</p>{#if activeFilterChips.length > 0}<Link href={taskListUrl()} class="mt-4 inline-flex min-h-10 items-center rounded-md border px-3 text-sm hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring">Reset semua filter</Link>{/if}</div></section>
    {:else}
        <section class="overflow-x-auto rounded-lg border bg-card">
            {#if canBulkUpdate && selectedTaskIds.length > 0}
                <div class="border-b bg-muted/20 px-4 py-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div><p class="text-sm font-medium">{selectedTaskIds.length} Task dipilih</p><p class="mt-1 text-xs text-muted-foreground">Perubahan akan dicatat di Activity Log dan Audit Log.</p></div>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <Form {...TaskController.bulkUpdate.form({ workspace: workspace.id, project: project.id })} class="flex items-end gap-2" onSuccess={() => { selectedTaskIds = []; }}>
                                {#snippet children({ errors, processing })}
                                    <label class="grid gap-1 text-xs text-muted-foreground"><span>Priority baru</span><select name="priority" class="h-10 rounded-md border border-input bg-background px-2 text-xs" required><option value="">Pilih priority</option>{#each priorities as priority (priority.value)}<option value={priority.value}>{priority.label}</option>{/each}</select></label>
                                    {#each selectedTaskIds as taskId}<input type="hidden" name="task_ids[]" value={taskId} />{/each}
                                    <Button type="submit" size="sm" disabled={processing}>{processing ? 'Menerapkan...' : 'Terapkan priority'}</Button>
                                    {#if errors.priority}<p class="text-xs text-destructive" role="alert">{errors.priority}</p>{/if}
                                    {#if errors.task_ids}<p class="text-xs text-destructive" role="alert">{errors.task_ids}</p>{/if}
                                {/snippet}
                            </Form>
                            <Form {...TaskController.bulkUpdate.form({ workspace: workspace.id, project: project.id })} class="flex items-end gap-2" onSuccess={() => { selectedTaskIds = []; }}>
                                {#snippet children({ errors, processing })}
                                    <label class="grid gap-1 text-xs text-muted-foreground"><span>PIC baru</span><select name="assignee_id" class="h-10 rounded-md border border-input bg-background px-2 text-xs" required><option value="">Pilih PIC</option>{#each filterMembers as member (member.id)}<option value={member.id}>{member.name}</option>{/each}</select></label>
                                    {#each selectedTaskIds as taskId}<input type="hidden" name="task_ids[]" value={taskId} />{/each}
                                    <Button type="submit" size="sm" disabled={processing}>{processing ? 'Menerapkan...' : 'Terapkan PIC'}</Button>
                                    {#if errors.assignee_id}<p class="text-xs text-destructive" role="alert">{errors.assignee_id}</p>{/if}
                                    {#if errors.task_ids}<p class="text-xs text-destructive" role="alert">{errors.task_ids}</p>{/if}
                                {/snippet}
                            </Form>
                            <Button type="button" variant="ghost" size="sm" onclick={() => { selectedTaskIds = []; }}>Batal</Button>
                        </div>
                    </div>
                </div>
            {/if}
            <div class="flex flex-col gap-3 border-b px-4 py-3 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-sm font-semibold">Daftar Task</h2><p class="mt-1 text-xs text-muted-foreground">{tasks.length} Task tampil · {groupedTasks.length} kelompok</p></div><div class="flex flex-wrap items-center gap-2"><label class="text-xs text-muted-foreground" for="task-grouping">Kelompokkan</label><select id="task-grouping" value={grouping} onchange={(event) => setGrouping(event.currentTarget.value)} class="h-9 rounded-md border border-input bg-background px-2 text-xs"><option value="none">Tanpa grouping</option><option value="status">Status</option><option value="assignee">PIC</option><option value="milestone">Milestone</option></select><Button type="button" variant="outline" size="sm" onclick={() => { saveViewOpen = true; }} aria-label="Simpan view saat ini">Simpan view</Button><Button type="button" variant="outline" size="sm" aria-pressed={compact} onclick={toggleCompact}><Rows3 class="size-4" aria-hidden="true" />{compact ? 'Tampilan nyaman' : 'Tampilan ringkas'}</Button></div></div>
            <table class="hidden w-full min-w-[61rem] text-left lg:table {compact ? 'text-xs [&>tbody>tr>td]:px-3 [&>tbody>tr>td]:py-2' : 'text-sm'}"><caption class="sr-only">Daftar Task dalam {project.name}</caption><thead class="bg-muted/40 text-xs text-muted-foreground"><tr><th class="w-12 px-4 py-3 font-medium"><input type="checkbox" class="size-4" aria-label="Pilih semua Task yang tampil" checked={allVisibleSelected} onchange={toggleAllTasks} /></th><th class="px-4 py-3 font-medium">ID</th><th class="px-4 py-3 font-medium">Task</th><th class="px-4 py-3 font-medium">Status</th><th class="px-4 py-3 font-medium">Priority</th><th class="px-4 py-3 font-medium">PIC</th><th class="px-4 py-3 font-medium">Deadline</th></tr></thead>
                {#each groupedTasks as group (group.key)}<tbody class="divide-y">{#if grouping !== 'none'}<tr class="bg-muted/20"><th colspan="7" class="px-4 py-2 text-left text-xs font-semibold text-muted-foreground">{group.label} <span class="font-normal">· {group.tasks.length}</span></th></tr>{/if}{#each group.tasks as task (task.id)}<tr class="hover:bg-muted/20 {selectedTask?.id === task.id ? 'bg-muted/40' : ''}"><td class="px-4 py-3 align-top"><input type="checkbox" aria-label={'Pilih ' + task.identifier} checked={selectedTaskIds.includes(task.id)} onchange={() => toggleTask(task.id)} /></td><td class="px-4 py-3 align-top font-mono text-xs text-muted-foreground">{task.identifier}</td><td class="px-4 py-3 align-top"><Link href={taskListUrl({ ...preservedQuery(true), task: task.id })} class="block min-h-10 rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><span class="font-medium hover:underline">{task.title}</span>{#if task.labels.length}<span class="mt-1 flex flex-wrap gap-1">{#each task.labels as label (label.id)}<Badge variant="outline" class="text-[11px]">{label.name}</Badge>{/each}</span>{/if}</Link></td><td class="px-4 py-3 align-top"><Badge variant={task.status === 'done' ? 'secondary' : 'outline'}>{task.status_label}</Badge></td><td class="px-4 py-3 align-top text-muted-foreground">{task.priority_label ?? '—'}</td><td class="px-4 py-3 align-top text-muted-foreground">{task.assignee?.name ?? 'Belum ada PIC'}</td><td class="px-4 py-3 align-top text-muted-foreground">{task.due_date ?? '—'}</td></tr>{/each}</tbody>{/each}
            </table>
            <div class="grid gap-2 p-3 lg:hidden">
                <div class="flex items-center justify-between gap-3 border-b px-1 pb-2 text-xs text-muted-foreground"><label class="inline-flex min-h-10 items-center gap-3 font-medium"><input type="checkbox" class="size-4" aria-label="Pilih semua Task yang tampil" checked={allVisibleSelected} onchange={toggleAllTasks} />Pilih semua</label><span>{tasks.length} Task</span></div>
                {#each groupedTasks as group (group.key)}
                    {#if grouping !== 'none'}<div class="px-1 pt-2 text-xs font-semibold text-muted-foreground">{group.label} <span class="font-normal">· {group.tasks.length}</span></div>{/if}
                    {#each group.tasks as task (task.id)}
                        <article class="rounded-md border p-3 {selectedTaskIds.includes(task.id) ? 'border-primary bg-primary/[0.04]' : 'bg-card'}">
                            <div class="flex items-start gap-3"><label class="flex min-h-10 min-w-10 items-center justify-center rounded-md border" aria-label={'Pilih ' + task.identifier}><input type="checkbox" class="size-4" checked={selectedTaskIds.includes(task.id)} onchange={() => toggleTask(task.id)} /></label><Link href={taskListUrl({ ...preservedQuery(true), task: task.id })} class="min-w-0 flex-1 rounded-sm py-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><p class="font-mono text-xs text-muted-foreground">{task.identifier}</p><p class="mt-1 text-sm font-medium">{task.title}</p></Link></div>
                            <div class="mt-3 flex flex-wrap items-center gap-2 pl-[3.25rem]"><Badge variant={task.status === 'done' ? 'secondary' : 'outline'}>{task.status_label}</Badge>{#if task.priority_label}<Badge variant="outline">{task.priority_label}</Badge>{/if}</div>
                            <dl class="mt-3 grid grid-cols-2 gap-3 border-t pt-3 pl-[3.25rem] text-xs"><div><dt class="text-muted-foreground">PIC</dt><dd class="mt-1 font-medium">{task.assignee?.name ?? 'Belum ada PIC'}</dd></div><div><dt class="text-muted-foreground">Deadline</dt><dd class="mt-1 font-medium">{task.due_date ?? 'Tanpa deadline'}</dd></div></dl>
                        </article>
                    {/each}
                {/each}
            </div>
        </section>
    {/if}
        </div>

{#if selectedTask}
    <div class="fixed inset-0 z-40 bg-black/30 lg:hidden" aria-hidden="true"></div>
    <aside class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto border-l bg-background p-5 shadow-xl sm:max-w-lg lg:sticky lg:top-4 lg:z-auto lg:max-h-[calc(100vh-2rem)] lg:w-auto lg:self-start lg:rounded-lg lg:border lg:p-5 lg:shadow-none" aria-label="Detail Task">
        <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="font-mono text-xs text-muted-foreground">{selectedTask.identifier}</p><h2 class="mt-1 text-lg font-semibold">{selectedTask.title}</h2></div><Link href={closePeek()} aria-label="Tutup detail Task" class="rounded-md p-2 hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring"><X class="size-4" aria-hidden="true" /></Link></div>
        <div class="mt-5 flex flex-wrap gap-2"><Badge variant="secondary">{selectedTask.status_label}</Badge>{#if selectedTask.priority_label}<Badge variant="outline">{selectedTask.priority_label}</Badge>{/if}</div>
        <p class="mt-5 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">{selectedTask.description ?? 'Belum ada deskripsi.'}</p>
        <dl class="mt-6 grid gap-4 border-t pt-5 text-sm"><div><dt class="text-xs text-muted-foreground">PIC</dt><dd class="mt-1 font-medium">{selectedTask.assignee?.name ?? 'Belum ada PIC'}</dd></div><div><dt class="text-xs text-muted-foreground">Pemohon</dt><dd class="mt-1 font-medium">{selectedTask.requester?.name ?? '—'}</dd></div><div><dt class="text-xs text-muted-foreground">Deadline</dt><dd class="mt-1 font-medium">{selectedTask.due_date ?? '—'}</dd></div></dl>
        <div class="mt-6 grid gap-2">
            <Link href={toUrl(TaskController.show({ workspace: workspace.id, project: project.id, task: selectedTask.id }))} class="inline-flex min-h-10 items-center justify-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring">Buka halaman penuh</Link>
            <Link href={toUrl(TaskController.show({ workspace: workspace.id, project: project.id, task: selectedTask.id }) + '#status-form')} class="inline-flex min-h-10 items-center justify-center rounded-md border px-3 text-sm hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring">Lanjutkan ke aksi Task</Link>
        </div>
    </aside>
{/if}
    </div>
</div>

<Dialog bind:open={saveViewOpen} onOpenChange={(open) => { if (!open) { saveViewOpen = false; viewName = ''; } }}>
    <DialogContent class="max-w-md">
        <DialogTitle>Simpan view Task</DialogTitle>
        <DialogDescription>Simpan filter, grouping, dan density agar tersedia lintas sesi dan perangkat.</DialogDescription>
        <Form {...SavedViewController.store.form({ workspace: workspace.id, project: project.id })} class="mt-5 grid gap-4" onSuccess={() => { saveViewOpen = false; viewName = ''; }}>
            {#snippet children({ errors, processing })}
                <label class="grid gap-2 text-sm font-medium" for="saved-view-name">Nama view<input id="saved-view-name" name="name" bind:value={viewName} maxlength="120" placeholder="Contoh: Task saya minggu ini" class="h-10 rounded-md border border-input bg-background px-3 text-sm font-normal outline-none focus-visible:ring-2 focus-visible:ring-ring" required /></label>
                <InputError message={errors.name} />
                {#each Object.entries(preservedQuery()) as [key, value]}<input type="hidden" name={'filters[' + key + ']'} value={value ?? ''} />{/each}
                <input type="hidden" name="grouping" value={grouping} />
                <input type="hidden" name="compact" value={compact ? '1' : '0'} />
                <InputError message={errors.filters} />
                <InputError message={errors.grouping} />
                <InputError message={errors.compact} />
                <DialogFooter><Button type="button" variant="outline" onclick={() => { saveViewOpen = false; viewName = ''; }}>Batal</Button><Button type="submit" disabled={processing || !viewName.trim()}>{processing ? 'Menyimpan...' : 'Simpan view'}</Button></DialogFooter>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>

<Dialog bind:open={editViewOpen} onOpenChange={(open) => { if (!open) { editViewOpen = false; editViewTarget = null; viewName = ''; } }}>
    <DialogContent class="max-w-md">
        <DialogTitle>Edit view Task</DialogTitle>
        <DialogDescription>Perbarui nama dan pengaturan tampilan view ini. Filter yang tersimpan tetap sama.</DialogDescription>
        {#if editViewTarget}
            {@const view = editViewTarget}
            <Form {...SavedViewController.update.form({ workspace: workspace.id, project: project.id, savedView: view.id })} class="mt-5 grid gap-4" onSuccess={() => { editViewOpen = false; editViewTarget = null; viewName = ''; }}>
                {#snippet children({ errors, processing })}
                    <label class="grid gap-2 text-sm font-medium" for="edit-saved-view-name">Nama view<input id="edit-saved-view-name" name="name" bind:value={viewName} maxlength="120" class="h-10 rounded-md border border-input bg-background px-3 text-sm font-normal outline-none focus-visible:ring-2 focus-visible:ring-ring" required /></label>
                    <InputError message={errors.name} />
                    {#each Object.entries(view.filters) as [key, value]}<input type="hidden" name={'filters[' + key + ']'} value={value ?? ''} />{/each}
                    <label class="grid gap-2 text-sm font-medium" for="edit-saved-view-grouping">Kelompokkan<select id="edit-saved-view-grouping" name="grouping" bind:value={editGrouping} class="h-10 rounded-md border border-input bg-background px-3 text-sm font-normal"><option value="none">Tanpa grouping</option><option value="status">Status</option><option value="assignee">PIC</option><option value="milestone">Milestone</option></select></label>
                    <label class="flex min-h-10 items-center gap-3 text-sm font-medium"><input type="hidden" name="compact" value="0" /><input type="checkbox" name="compact" value="1" bind:checked={editCompact} class="size-4" />Tampilan ringkas</label>
                    <InputError message={errors.filters} />
                    <InputError message={errors.grouping} />
                    <InputError message={errors.compact} />
                    <DialogFooter><Button type="button" variant="outline" onclick={() => { editViewOpen = false; editViewTarget = null; viewName = ''; }}>Batal</Button><Button type="submit" disabled={processing || !viewName.trim()}>{processing ? 'Menyimpan...' : 'Simpan perubahan'}</Button></DialogFooter>
                {/snippet}
            </Form>
        {/if}
    </DialogContent>
</Dialog>

<Dialog bind:open={deleteViewOpen} onOpenChange={(open) => { if (!open) { deleteViewOpen = false; deleteViewTarget = null; } }}>
    <DialogContent class="max-w-md">
        <DialogTitle>Hapus view Task?</DialogTitle>
        <DialogDescription>View “{deleteViewTarget?.name ?? ''}” akan dihapus untuk akun ini.</DialogDescription>
        {#if deleteViewTarget}
            <Form {...SavedViewController.destroy.form({ workspace: workspace.id, project: project.id, savedView: deleteViewTarget.id })} onSuccess={() => { deleteViewOpen = false; deleteViewTarget = null; }}>
                {#snippet children({ processing })}
                    <DialogFooter><Button type="button" variant="outline" onclick={() => { deleteViewOpen = false; deleteViewTarget = null; }}>Batal</Button><Button type="submit" variant="destructive" disabled={processing}>{processing ? 'Menghapus...' : 'Hapus view'}</Button></DialogFooter>
                {/snippet}
            </Form>
        {/if}
    </DialogContent>
</Dialog>

<Dialog bind:open={migrationOpen} onOpenChange={(open) => { if (!open && !migrationProcessing) migrationOpen = false; }}>
    <DialogContent class="max-w-md">
        <DialogTitle>Pindahkan view lama?</DialogTitle>
        <DialogDescription>{migrationError || `${legacyViews.length} view tersimpan di browser ini. Pindahkan ke akun agar tersedia lintas sesi dan perangkat?`}</DialogDescription>
        <DialogFooter>
            <Button type="button" variant="outline" disabled={migrationProcessing} onclick={skipMigration}>Nanti</Button>
            <Button type="button" disabled={migrationProcessing} onclick={startMigration}>{migrationProcessing ? `Memindahkan ${migrationIndex + 1}/${legacyViews.length}...` : 'Pindahkan ke akun'}</Button>
        </DialogFooter>
    </DialogContent>
</Dialog>
