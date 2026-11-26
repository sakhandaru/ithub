<script module lang="ts">
    import { index } from '@/routes/workspaces/projects/tasks';
    export const layout = { breadcrumbs: [{ title: 'Task', href: index(['', '']) }] };
</script>

<script lang="ts">
    import { Form, Link, page, router } from '@inertiajs/svelte';
    import { createTable, tableFeatures } from '@tanstack/svelte-table';
    import type { ColumnDef } from '@tanstack/svelte-table';
    import { onMount } from 'svelte';
    import type { ComponentProps } from 'svelte';
    import Filter from 'carbon-icons-svelte/lib/Filter.svelte';
    import ListTodo from 'carbon-icons-svelte/lib/Task.svelte';
    import Search from 'carbon-icons-svelte/lib/Search.svelte';
    import Check from 'carbon-icons-svelte/lib/Checkmark.svelte';
    import CheckCircle2 from 'carbon-icons-svelte/lib/CheckmarkFilled.svelte';
    import ChevronDown from 'carbon-icons-svelte/lib/ChevronDown.svelte';
    import CalendarDays from 'carbon-icons-svelte/lib/Calendar.svelte';
    import CircleAlert from 'carbon-icons-svelte/lib/WarningAlt.svelte';
    import CircleDashed from 'carbon-icons-svelte/lib/CircleDash.svelte';
    import Flag from 'carbon-icons-svelte/lib/Flag.svelte';
    import Hashtag from 'carbon-icons-svelte/lib/Hashtag.svelte';
    import TextAlignLeft from 'carbon-icons-svelte/lib/TextAlignLeft.svelte';
    import User from 'carbon-icons-svelte/lib/User.svelte';
    import SlidersHorizontal from 'carbon-icons-svelte/lib/SettingsAdjust.svelte';
    import Pencil from 'carbon-icons-svelte/lib/Edit.svelte';
    import Plus from 'carbon-icons-svelte/lib/Add.svelte';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import SavedViewController from '@/actions/App/Http/Controllers/SavedViewController';
    import AppHead from '@/components/AppHead.svelte';
    import DataTable from '@/components/DataTable.svelte';
    import Board from './Board.svelte';
    import Calendar from './Calendar.svelte';
    import Gantt from './Gantt.svelte';
    import TaskDetail from './Show.svelte';
    import ProjectContextNav from '@/components/ProjectContextNav.svelte';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import { globalHeaderLabels, taskBreadcrumbs } from '@/lib/page-header';
    import InputError from '@/components/InputError.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
    import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
    import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import { avatarColor } from '@/lib/avatar-color';
    import { getInitials } from '@/lib/initials';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null };
    type Option = { value: string; label: string };
    type Person = { id: number; name: string; avatar?: string | null };
    type Task = {
        id: string; identifier: string; title: string; description: string | null;
        status: string; status_label: string; priority: string | null; priority_label: string | null;
        assignee: Person | null; requester: Person | null;
        start_date: string | null; due_date: string | null; completed_at: string | null; created_at: string; milestone: { id: string; name: string; target_date: string | null } | null; module: { id: string; name: string; description: string | null } | null;
    };

    type Member = { id: number; name: string; avatar?: string | null }; type Milestone = { id: string; name: string; target_date: string | null }; type Module = { id: string; name: string; description: string | null }; type Release = { id: string; name: string; status: string; target_date: string | null };
    type TaskGroup = { key: string; label: string; tasks: Task[] };
    const taskFeatures = tableFeatures({});
    const taskColumns: ColumnDef<typeof taskFeatures, Task>[] = [
        { accessorKey: 'identifier', header: 'ID' },
        { accessorKey: 'title', id: 'name', header: 'Nama' },
        { accessorKey: 'priority_label', header: 'Priority' },
        { accessorFn: (task) => task.milestone?.name ?? 'Tanpa milestone', id: 'milestone', header: 'Milestone' },
        { accessorFn: (task) => task.module?.name ?? 'Tanpa modul', id: 'module', header: 'Modul' },
        { accessorKey: 'due_date', header: 'Deadline' },
        { accessorFn: (task) => task.assignee?.name ?? 'Belum ada PIC', id: 'assignee', header: 'PIC' },
    ];
    const taskHeaderIcons = {
        identifier: Hashtag,
        name: TextAlignLeft,
        priority_label: CircleAlert,
        milestone: Flag,
        due_date: CalendarDays,
        assignee: User,
    };
    type SavedView = { id: string; name: string; filters: Record<string, string | number | undefined>; grouping: 'none' | 'status' | 'assignee' | 'milestone'; compact: boolean };
    type TaskDetailProps = ComponentProps<typeof TaskDetail>;
    type LegacySavedView = { id: string; name: string; query: Record<string, string | number | undefined>; grouping: SavedView['grouping']; compact: boolean };
    let { workspace, project, tasks, scheduledTasks, selectedTask, taskDetail, editableTaskIds, filters, statuses, priorities, filterMembers, filterMilestones, filterModules, filterReleases, savedViews: initialSavedViews, activeSavedViewId: initialActiveSavedViewId, savedViewWarnings, canCreate, canBulkUpdate, view, milestones, modules, releases, allowedTransitions }: {
        workspace: Workspace; project: Project; tasks: Task[]; selectedTask: Task | null;
        taskDetail: TaskDetailProps | null;
        scheduledTasks: Task[];
        editableTaskIds: string[];
        filters: { status: string; priority: string; assignee: string; requester: string; milestone: string; module: string; release: string; preset: string; no_deadline: boolean; no_evidence: boolean; search: string; sort: string; direction: string };
        statuses: Option[]; priorities: Option[]; filterMembers: Member[]; filterMilestones: Milestone[]; filterModules: Module[]; filterReleases: Release[]; savedViews: SavedView[]; activeSavedViewId: string | null; savedViewWarnings: string[]; canCreate: boolean; canBulkUpdate: boolean; view: 'list' | 'board' | 'calendar' | 'gantt'; milestones: Milestone[]; modules: Module[]; releases: Release[]; allowedTransitions: Record<string, string[]>;
    } = $props();
    let compact = $state(false);
    let createTaskOpen = $state(false);
    let createAssigneeId = $state('none');
    let createPriority = $state('none');
    let grouping = $state<'none' | 'status' | 'assignee' | 'milestone'>('status');
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
    let desktopFilterOpen = $state(false);
    let filterPopover: HTMLDivElement;
    let desktopFilterStatus = $state('');
    let desktopFilterPriority = $state('');
    let desktopFilterAssignee = $state('');
    const savedViewsKey = $derived(`sitop:task-views:${project.id}`);
    const migrationKey = $derived(`sitop:task-views-migrated:${page.props.auth?.user?.id ?? 'guest'}:${project.id}`);
    const preferencesKey = $derived(`sitop:task-preferences:${page.props.auth?.user?.id ?? 'guest'}:${project.id}`);

    $effect(() => {
        desktopFilterStatus = filters.status;
        desktopFilterPriority = filters.priority;
        desktopFilterAssignee = filters.assignee;
    });

    function closeDesktopFilter(event: MouseEvent): void {
        if (desktopFilterOpen && filterPopover && !filterPopover.contains(event.target as Node)) desktopFilterOpen = false;
    }

    onMount(() => {
        if (new URL(page.url, window.location.origin).searchParams.get('create') === '1') createTaskOpen = true;

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
                grouping = parsed.grouping ?? 'status';
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
        release: filters.release || undefined,
        no_deadline: filters.no_deadline ? 1 : undefined,
        no_evidence: filters.no_evidence ? 1 : undefined,
        search: filters.search || undefined,
        sort: filters.sort || undefined,
        direction: filters.direction || undefined,
        view: includeSavedView ? activeSavedViewId ?? undefined : undefined,
    });

    function taskPeekUrl(taskId: string): string {
        return taskListUrl({ ...preservedQuery(), view, task: taskId });
    }

    function closeTaskDetail(): void {
        router.visit(taskListUrl({ ...preservedQuery(), view }), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    const presets = [
        { key: '', label: 'Semua Task' },
        { key: 'mine', label: 'Task saya' },
        { key: 'week', label: 'Minggu ini' },
        { key: 'overdue', label: 'Terlambat' },
        { key: 'no_deadline', label: 'Tanpa deadline' },
        { key: 'blocked', label: 'Blocked' },
        { key: 'unassigned', label: 'Tanpa PIC' },
    ];

    const activeFilterChips = $derived([
        filters.status ? { key: 'status', label: `Status: ${statuses.find((item) => item.value === filters.status)?.label ?? filters.status}` } : null,
        filters.priority ? { key: 'priority', label: `Priority: ${priorities.find((item) => item.value === filters.priority)?.label ?? filters.priority}` } : null,
        filters.assignee ? { key: 'assignee', label: filters.assignee === 'none' ? 'Tanpa PIC' : `PIC: ${filterMembers.find((item) => String(item.id) === filters.assignee)?.name ?? filters.assignee}` } : null,
        filters.milestone ? { key: 'milestone', label: filters.milestone === 'none' ? 'Tanpa milestone' : `Milestone: ${filterMilestones.find((item) => item.id === filters.milestone)?.name ?? filters.milestone}` } : null,
        filters.release ? { key: 'release', label: filters.release === 'none' ? 'Tanpa rilis' : `Rilis: ${filterReleases.find((item) => item.id === filters.release)?.name ?? filters.release}` } : null,
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

        if (grouping === 'status') {
            return statuses
                .map((status) => groups.get(status.value))
                .filter((group): group is TaskGroup => group !== undefined);
        }

        return [...groups.values()];
    });

    const taskTable = createTable({
        features: taskFeatures,
        columns: taskColumns,
        get data() { return groupedTasks.flatMap((group) => group.tasks); },
        getRowId: (task) => task.id,
    });

    function groupKeyForTask(task: Task): string {
        if (grouping === 'none') return 'all';
        return grouping === 'status'
            ? task.status
            : grouping === 'assignee'
                ? task.assignee?.id?.toString() ?? 'unassigned'
                : task.milestone?.id ?? 'no-milestone';
    }

    function groupForTask(task: Task): TaskGroup | undefined {
        return groupedTasks.find((group) => group.key === groupKeyForTask(task));
    }

    function statusTone(status: string): string {
        return {
            backlog: 'bg-slate-100/80 text-slate-700 dark:bg-slate-900/60 dark:text-slate-300',
            todo: 'bg-indigo-50/80 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300',
            in_progress: 'bg-amber-50/80 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
            review: 'bg-violet-50/80 text-violet-800 dark:bg-violet-950/40 dark:text-violet-300',
            done: 'bg-emerald-50/80 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300',
            blocked: 'bg-rose-50/80 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300',
            canceled: 'bg-slate-100/80 text-slate-600 dark:bg-slate-900/60 dark:text-slate-400',
        }[status] ?? 'bg-surface-2/80 text-muted-foreground';
    }

    function statusDot(status: string): string {
        return {
            backlog: 'bg-slate-500',
            todo: 'bg-indigo-500',
            in_progress: 'bg-amber-500',
            review: 'bg-violet-500',
            done: 'bg-emerald-500',
            blocked: 'bg-rose-500',
            canceled: 'bg-slate-400',
        }[status] ?? 'bg-muted-foreground';
    }

    function priorityTone(priority: string | null): string {
        const value = priority?.toLowerCase();
        if (value === 'urgent' || value === 'high' || value === 'tinggi') return 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300';
        if (value === 'medium' || value === 'sedang') return 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300';
        if (value === 'low' || value === 'rendah') return 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300';
        return 'bg-surface-2/70 text-muted-foreground';
    }

    function isOverdue(task: Task): boolean {
        return Boolean(task.due_date && task.status !== 'done' && task.due_date < new Date().toISOString().slice(0, 10));
    }

    function formatDueDate(date: string | null): string {
        if (!date) return 'Tambah deadline';
        return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(`${date}T00:00:00`));
    }

    function persistPreferences(): void {
        localStorage.setItem(preferencesKey, JSON.stringify({ compact, grouping }));
    }

    function setGrouping(value: string): void {
        grouping = value as SavedView['grouping'];
        persistPreferences();
    }

    function groupingLabel(value: SavedView['grouping']): string {
        return { none: 'Tanpa grouping', status: 'Status', assignee: 'PIC', milestone: 'Milestone' }[value];
    }

    const visibleGrouping = $derived(view === 'board' && grouping === 'status' ? 'none' : grouping);
    const selectedCreateAssignee = $derived(filterMembers.find((member) => String(member.id) === createAssigneeId) ?? null);
    const selectedCreatePriority = $derived(priorities.find((priority) => priority.value === createPriority) ?? null);

    function createPriorityTone(value: string): string {
        return value === 'urgent' || value === 'high' ? 'text-rose-500' : value === 'medium' ? 'text-amber-500' : 'text-sky-500';
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

<svelte:window onclick={closeDesktopFilter} />

<AppHead title={project.identifier + ' · Task'} />

<div class="flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
        <PageHeader breadcrumbs={taskBreadcrumbs(page.props.unitContext?.active?.name ?? workspace.name, page.props.unitContext?.active?.id, toUrl(ProjectController.index(workspace.id)), project.name, toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), toUrl(TaskController.index({ workspace: workspace.id, project: project.id })), tasks.length, globalHeaderLabels.task, page.props.projectContext?.active?.icon)} showSidebarTrigger>
        {#snippet actions()}
            <ProjectContextNav {workspace} {project} active={view === 'list' ? 'tasks' : view} taskQuery={preservedQuery()} />
            <div class="relative hidden lg:block" bind:this={filterPopover}>
                <Button type="button" variant="outline" class="relative h-7 w-7 border-border/80 bg-background p-0 text-muted-foreground" aria-label="Filter task" aria-expanded={desktopFilterOpen} title="Filter task" onclick={() => { desktopFilterOpen = !desktopFilterOpen; }}>
                    <Filter class="size-3.5" aria-hidden="true" />
                    {#if activeFilterChips.length > 0}<span class="absolute -right-1 -top-1 flex size-3.5 items-center justify-center rounded-full bg-primary text-[9px] font-semibold text-primary-foreground">{activeFilterChips.length}</span>{/if}
                </Button>
                {#if desktopFilterOpen}
                    <div class="absolute right-0 top-full z-40 mt-2 w-72 rounded-md border border-border/80 bg-background p-3 shadow-lg" role="dialog" aria-label="Filter Task">
                        <div class="mb-3 flex items-center justify-between"><div><p class="text-xs font-semibold">Filter Task</p><p class="mt-0.5 text-[11px] text-muted-foreground">Persempit daftar pekerjaan.</p></div>{#if activeFilterChips.length > 0}<span class="text-[11px] text-muted-foreground">{activeFilterChips.length} aktif</span>{/if}</div>
                        <form method="get" action={taskListUrl()} class="grid gap-2.5" onsubmit={() => { desktopFilterOpen = false; }}>
                            <input type="hidden" name="view" value={view} />
                            <label class="grid gap-1 text-[11px] font-medium text-muted-foreground">Cari task<input name="search" value={filters.search} class="h-7 rounded-md border border-border/80 bg-background px-2 text-xs text-foreground outline-none focus:border-primary focus:ring-2 focus:ring-ring/20" placeholder="ID atau judul" /></label>
                            <input type="hidden" name="status" value={desktopFilterStatus} /><input type="hidden" name="priority" value={desktopFilterPriority} /><input type="hidden" name="assignee" value={desktopFilterAssignee} />
                            <label class="grid gap-1 text-[11px] font-medium text-muted-foreground">Status<Select value={desktopFilterStatus} onValueChange={(value) => { desktopFilterStatus = value; }}><SelectTrigger class="h-7 w-full rounded-md border border-border/80 bg-background px-2 text-xs text-foreground shadow-none focus:ring-2 focus:ring-ring/20"><span>{statuses.find((status) => status.value === desktopFilterStatus)?.label ?? 'Semua status'}</span></SelectTrigger><SelectContent><SelectItem value="">Semua status</SelectItem>{#each statuses as status (status.value)}<SelectItem value={status.value}>{status.label}</SelectItem>{/each}</SelectContent></Select></label>
                            <label class="grid gap-1 text-[11px] font-medium text-muted-foreground">Priority<Select value={desktopFilterPriority} onValueChange={(value) => { desktopFilterPriority = value; }}><SelectTrigger class="h-7 w-full rounded-md border border-border/80 bg-background px-2 text-xs text-foreground shadow-none focus:ring-2 focus:ring-ring/20"><span>{priorities.find((priority) => priority.value === desktopFilterPriority)?.label ?? 'Semua priority'}</span></SelectTrigger><SelectContent><SelectItem value="">Semua priority</SelectItem>{#each priorities as priority (priority.value)}<SelectItem value={priority.value}>{priority.label}</SelectItem>{/each}</SelectContent></Select></label>
                            <label class="grid gap-1 text-[11px] font-medium text-muted-foreground">PIC<Select value={desktopFilterAssignee} onValueChange={(value) => { desktopFilterAssignee = value; }}><SelectTrigger class="h-7 w-full rounded-md border border-border/80 bg-background px-2 text-xs text-foreground shadow-none focus:ring-2 focus:ring-ring/20"><span>{desktopFilterAssignee === 'none' ? 'Tanpa PIC' : filterMembers.find((member) => String(member.id) === desktopFilterAssignee)?.name ?? 'Semua PIC'}</span></SelectTrigger><SelectContent><SelectItem value="">Semua PIC</SelectItem><SelectItem value="none">Tanpa PIC</SelectItem>{#each filterMembers as member (member.id)}<SelectItem value={String(member.id)}>{member.name}</SelectItem>{/each}</SelectContent></Select></label>
                            <label class="flex min-h-7 items-center gap-2 text-xs text-muted-foreground"><input type="checkbox" name="no_deadline" value="1" checked={filters.no_deadline} class="size-3.5" />Tanpa deadline</label>
                            <div class="mt-1 flex items-center justify-between gap-2 border-t border-border/70 pt-3"><Link href={taskListUrl({ view })} class="text-xs text-muted-foreground hover:text-foreground" onclick={() => { desktopFilterOpen = false; }}>Reset</Link><Button type="submit" class="h-7 px-2.5 text-xs">Terapkan filter</Button></div>
                        </form>
                    </div>
                {/if}
            </div>
            {#if view === 'list' || view === 'board'}
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        {#snippet children(props)}
                            <Button {...props} type="button" variant="outline" class="h-7 gap-1.5 border-border/80 bg-background px-2.5 text-xs text-muted-foreground" aria-label={`Kelompokkan: ${groupingLabel(visibleGrouping as SavedView['grouping'])}`}>
                                <span>{groupingLabel(visibleGrouping as SavedView['grouping'])}</span>
                                <ChevronDown class="size-3.5 text-muted-foreground" aria-hidden="true" />
                            </Button>
                        {/snippet}
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-44">
                        <DropdownMenuItem class={visibleGrouping === 'none' ? 'bg-muted' : ''} onclick={() => setGrouping('none')}>{#if visibleGrouping === 'none'}<Check class="size-3.5" />{/if}Tanpa grouping</DropdownMenuItem>
                        {#if view === 'list'}<DropdownMenuItem class={visibleGrouping === 'status' ? 'bg-muted' : ''} onclick={() => setGrouping('status')}>{#if visibleGrouping === 'status'}<Check class="size-3.5" />{/if}Status</DropdownMenuItem>{/if}
                        <DropdownMenuItem class={visibleGrouping === 'assignee' ? 'bg-muted' : ''} onclick={() => setGrouping('assignee')}>{#if visibleGrouping === 'assignee'}<Check class="size-3.5" />{/if}PIC</DropdownMenuItem>
                        <DropdownMenuItem class={visibleGrouping === 'milestone' ? 'bg-muted' : ''} onclick={() => setGrouping('milestone')}>{#if visibleGrouping === 'milestone'}<Check class="size-3.5" />{/if}Milestone</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            {/if}
            {#if canCreate && !project.archived_at}
                <Button type="button" variant="outline" class="h-7 shrink-0 gap-1.5 border-border/80 bg-background px-2.5 text-xs text-muted-foreground" onclick={() => { createTaskOpen = true; }}>
                    <Plus class="size-3.5" aria-hidden="true" />Tambah Task
                </Button>
            {/if}
        {/snippet}
    </PageHeader>
    <div class="min-w-0">
        <div class="min-w-0 grid gap-6">
    {#if view === 'list'}

    {#if project.archived_at}
        <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan. Task bersifat read-only sampai Project dipulihkan.</div>
    {/if}

    {#if false}
    {#if canCreate && !project.archived_at}
        <section id="quick-task" class="rounded-lg border border-border/80 bg-surface-2/40 p-3 sm:p-4">
            <Form {...TaskController.store.form({ workspace: workspace.id, project: project.id })} class="flex flex-col gap-3 sm:flex-row sm:items-end">
                {#snippet children({ errors, processing })}
                    <div class="min-w-0 flex-1">
                        <Label for="quick-task-title" class="text-xs font-medium text-muted-foreground">Buat Task baru</Label>
                        <Input id="quick-task-title" name="title" class="mt-2 min-h-10 bg-background" placeholder="Apa yang perlu dikerjakan?" required maxlength={200} autocomplete="off" />
                        <InputError message={errors.title} />
                    </div>
                    <Button type="submit" class="min-h-10" disabled={processing}>{processing ? 'Membuat...' : 'Tambah Task'}</Button>
                {/snippet}
            </Form>
        </section>
    {/if}

    <section class="rounded-lg border border-border/80 bg-card p-3 sm:p-4">
        <div class="flex flex-col gap-3 border-b border-border/70 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold">Mulai dari fokus kerja</h2>
                <p class="mt-1 text-xs text-muted-foreground">Preset ini membantu menemukan Task yang perlu ditangani lebih dulu.</p>
            </div>
            <div class="flex flex-wrap gap-2" aria-label="Preset Task">
                {#each presets as preset (preset.key)}
                    <Link href={taskListUrl({ ...preservedQuery(), preset: preset.key || undefined })} class="inline-flex min-h-9 items-center rounded-md border px-3 text-xs font-medium transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {filters.preset === preset.key ? 'border-primary/40 bg-accent text-accent-foreground' : 'border-border/80 bg-background'}" aria-current={filters.preset === preset.key ? 'page' : undefined}>{preset.label}</Link>
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
                        <div><Label for="mobile-task-release">Rilis</Label><select id="mobile-task-release" name="release" value={filters.release} class="mt-2 h-11 w-full rounded-md border border-input bg-background px-3 text-sm"><option value="">Semua rilis</option><option value="none">Tanpa rilis</option>{#each filterReleases as release (release.id)}<option value={release.id}>{release.name}</option>{/each}</select></div>
                        <label class="flex min-h-11 items-center gap-3 text-sm"><input class="size-4" type="checkbox" name="no_deadline" value="1" checked={filters.no_deadline} />Tanpa deadline</label>
                        <label class="flex min-h-11 items-center gap-3 text-sm"><input class="size-4" type="checkbox" name="no_evidence" value="1" checked={filters.no_evidence} />Tanpa bukti</label>
                        <div class="flex gap-2"><Button type="submit" class="min-h-11 flex-1"><Filter class="size-4" aria-hidden="true" />Terapkan filter</Button><Link href={taskListUrl()} class="inline-flex min-h-11 items-center rounded-md border px-3 text-sm" onclick={() => { filterOpen = false; }}>Reset</Link></div>
                    </form>
                </SheetContent>
            </Sheet>
        </div>
        <form method="get" action={taskListUrl()} class="hidden gap-3 pt-4 sm:grid sm:grid-cols-2 lg:flex lg:items-end">
            <input type="hidden" name="preset" value={filters.preset} />
            <div class="sm:col-span-2 lg:min-w-64 lg:flex-1">
                <Label for="task-search">Cari Task</Label>
                <div class="relative mt-2"><Search class="pointer-events-none absolute left-3 top-3 size-4 text-muted-foreground" aria-hidden="true" /><Input id="task-search" name="search" value={filters.search} class="min-h-10 bg-background pl-9" placeholder="ID atau judul" /></div>
            </div>
            <div><Label for="task-status">Status</Label><select id="task-status" name="status" value={filters.status} class="mt-2 h-10 w-full rounded-md border border-border/80 bg-background px-3 text-sm"><option value="">Semua status</option>{#each statuses as status (status.value)}<option value={status.value}>{status.label}</option>{/each}</select></div>
            <div><Label for="task-priority">Priority</Label><select id="task-priority" name="priority" value={filters.priority} class="mt-2 h-10 w-full rounded-md border border-border/80 bg-background px-3 text-sm"><option value="">Semua priority</option>{#each priorities as priority (priority.value)}<option value={priority.value}>{priority.label}</option>{/each}</select></div>
            <div><Label for="task-assignee">PIC</Label><select id="task-assignee" name="assignee" value={filters.assignee} class="mt-2 h-10 w-full rounded-md border border-border/80 bg-background px-3 text-sm"><option value="">Semua PIC</option><option value="none">Tanpa PIC</option>{#each filterMembers as member (member.id)}<option value={member.id}>{member.name}</option>{/each}</select></div>
            <div><Label for="task-milestone">Milestone</Label><select id="task-milestone" name="milestone" value={filters.milestone} class="mt-2 h-10 w-full rounded-md border border-border/80 bg-background px-3 text-sm"><option value="">Semua milestone</option><option value="none">Tanpa milestone</option>{#each filterMilestones as milestone (milestone.id)}<option value={milestone.id}>{milestone.name}</option>{/each}</select></div>
            <div><Label for="task-release">Rilis</Label><select id="task-release" name="release" value={filters.release} class="mt-2 h-10 w-full rounded-md border border-border/80 bg-background px-3 text-sm"><option value="">Semua rilis</option><option value="none">Tanpa rilis</option>{#each filterReleases as release (release.id)}<option value={release.id}>{release.name}</option>{/each}</select></div>
            <label class="flex min-h-10 items-center gap-2 text-sm"><input type="checkbox" name="no_deadline" value="1" checked={filters.no_deadline} />Tanpa deadline</label>
            <label class="flex min-h-10 items-center gap-2 text-sm"><input type="checkbox" name="no_evidence" value="1" checked={filters.no_evidence} />Tanpa bukti</label>
            <Button type="submit" variant="outline" class="min-h-10"><Filter class="size-4" aria-hidden="true" />Filter</Button>
            <Link href={taskListUrl()} class="min-h-10 text-sm text-muted-foreground hover:text-foreground hover:underline focus-visible:ring-2 focus-visible:ring-ring">Reset filter</Link>
        </form>
    </section>
    {/if}

    {#if activeFilterChips.length > 0}
        <div class="flex flex-wrap items-center gap-2" aria-label="Filter aktif"><span class="text-xs font-medium text-muted-foreground">Filter aktif:</span>{#each activeFilterChips as chip (chip.key)}<Link href={clearFilter(chip.key)} class="inline-flex min-h-8 items-center rounded-md border border-border/80 bg-accent/60 px-2.5 text-xs hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">{chip.label}<span class="ml-2 text-muted-foreground" aria-hidden="true">×</span><span class="sr-only">Hapus filter {chip.label}</span></Link>{/each}</div>
    {/if}


    {#if tasks.length === 0}
        <section class="flex min-h-64 items-center justify-center rounded-lg border border-dashed bg-card px-6 py-12 text-center"><div class="max-w-md"><ListTodo class="mx-auto size-8 text-muted-foreground" aria-hidden="true" /><h2 class="mt-4 text-sm font-semibold">Belum ada Task yang cocok</h2><p class="mt-1 text-sm leading-6 text-muted-foreground">{activeFilterChips.length > 0 ? 'Coba ubah preset atau filter untuk melihat Task lain.' : 'Buat Task pertama untuk mulai bekerja.'}</p>{#if activeFilterChips.length > 0}<Link href={taskListUrl()} class="mt-4 inline-flex min-h-10 items-center rounded-md border px-3 text-sm hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring">Reset semua filter</Link>{/if}</div></section>
    {:else}
        <section class="min-w-0 rounded-lg border border-border/80 bg-card">
            {#if false && canBulkUpdate && selectedTaskIds.length > 0}
                <div class="border-b border-border/70 bg-accent/40 px-4 py-3">
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
            <DataTable table={taskTable} caption={`Daftar Task dalam ${project.name}`} headerIcons={taskHeaderIcons} wrapperClass="hidden min-w-0 lg:block mt-0" tableWrapperClass="w-full min-w-0 overflow-x-auto" tableClass="w-full min-w-[68rem] whitespace-nowrap text-left text-xs" headerClass="border-b border-border/70 bg-muted/25 px-3 py-2.5 text-xs font-medium text-muted-foreground">
                {#snippet children(row)}
                    {@const task = row.original}
                    {@const previousTask = row.index > 0 ? taskTable.getRowModel().rows[row.index - 1]?.original : null}
                    {#if grouping !== 'none' && (!previousTask || groupKeyForTask(task) !== groupKeyForTask(previousTask))}
                        {@const group = groupForTask(task)}
                        <tr class={statusTone(group?.key ?? '')}><th colspan="7" class="px-4 py-2.5 text-left text-xs font-semibold"><span class="inline-flex items-center gap-2"><span class={`size-2 rounded-full ${statusDot(group?.key ?? '')}`} aria-hidden="true"></span>{group?.label ?? 'Kelompok'} <span class="font-normal opacity-70">{group?.tasks.length ?? 0}</span></span></th></tr>
                    {/if}
                    <tr class="{selectedTask?.id === task.id ? 'bg-accent/60' : ''}">
                        <td class="w-24 px-3 py-2.5 align-top font-mono text-xs tracking-tight text-muted-foreground">{task.identifier}</td>
                        <td class="min-w-[24rem] px-3 py-2.5"><Link href={taskPeekUrl(task.id)} class="block min-w-0 rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><span class="block truncate text-sm font-medium">{task.title}</span></Link></td>
                        <td class="w-32 px-3 py-2.5">{#if task.priority_label}<span class={`inline-flex min-h-7 items-center gap-1 rounded-md px-2 text-xs font-medium ${priorityTone(task.priority_label)}`}><CircleAlert class="size-3.5" aria-hidden="true" />{task.priority_label}</span>{:else}<span class="text-xs text-muted-foreground">Belum diatur</span>{/if}</td>
                        <td class="w-44 px-3 py-2.5">{#if task.milestone}<span class="inline-flex min-h-7 max-w-44 items-center rounded-full border border-border/70 px-2.5 py-0.5 text-xs leading-4 text-muted-foreground"><span class="truncate">{task.milestone.name}</span></span>{:else}<span class="text-xs text-muted-foreground">Tanpa milestone</span>{/if}</td>
                        <td class="w-44 px-3 py-2.5">{#if task.module}<span class="inline-flex min-h-7 max-w-44 items-center rounded-full border border-border/70 px-2.5 py-0.5 text-xs leading-4 text-muted-foreground"><span class="truncate">{task.module.name}</span></span>{:else}<span class="text-xs text-muted-foreground">Tanpa modul</span>{/if}</td>
                        <td class="w-40 px-3 py-2.5 {isOverdue(task) ? 'text-destructive' : 'text-muted-foreground'}"><div class="flex items-center gap-1.5 whitespace-nowrap text-xs {isOverdue(task) ? 'font-medium' : ''}"><CalendarDays class="size-3.5" aria-hidden="true" />{formatDueDate(task.due_date)}</div></td>
                        <td class="w-40 px-3 py-2.5">{#if task.assignee}<UserAvatar user={task.assignee} size="size-4" class="text-xs text-muted-foreground" />{:else}<span class="inline-flex items-center gap-1.5 text-xs text-muted-foreground"><CircleDashed class="size-4" aria-hidden="true" />Belum ada PIC</span>{/if}</td>
                    </tr>
                {/snippet}
                {#snippet footer()}
                    <div class="flex items-center justify-between border-t border-border/70 px-3 py-2.5 text-xs text-muted-foreground">
                        <span class="inline-flex items-center gap-1.5"><CheckCircle2 class="size-3.5" aria-hidden="true" />Data diperbarui dari Project</span>
                        <span>{tasks.length} Task</span>
                    </div>
                {/snippet}
            </DataTable>
            <div class="hidden">
                {#each groupedTasks as group (group.key)}
                    <section class="overflow-hidden rounded-lg border border-border/80 bg-card">
                        <header class={`flex items-center justify-between gap-3 border-b border-border/70 px-4 py-2.5 ${statusTone(group.key)}`}>
                            <div class="flex min-w-0 items-center gap-2">
                                <span class={`size-2 rounded-full ${statusDot(group.key)}`} aria-hidden="true"></span>
                                <h3 class="truncate text-xs font-semibold">{group.label}</h3>
                                <span class="text-xs tabular-nums opacity-70">{group.tasks.length}</span>
                            </div>
                            <span class="text-xs tracking-widest opacity-50" aria-hidden="true">···</span>
                        </header>
                        <div class="grid grid-cols-[minmax(20rem,1fr)_8rem_12rem_10rem_10rem] border-b border-border/70 bg-surface-2/50 px-4 py-2 text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                            <span class="flex items-center gap-2"><input type="checkbox" class="size-3.5" aria-label={'Pilih semua Task di ' + group.label} checked={group.tasks.every((task) => selectedTaskIds.includes(task.id))} onchange={() => { const ids = group.tasks.map((task) => task.id); selectedTaskIds = ids.every((id) => selectedTaskIds.includes(id)) ? selectedTaskIds.filter((id) => !ids.includes(id)) : [...new Set([...selectedTaskIds, ...ids])]; }} />Nama</span>
                            <span>Priority</span>
                            <span>Milestone</span>
                            <span>Deadline</span>
                            <span>PIC</span>
                        </div>
                        <div class="divide-y divide-border/70">
                            {#each group.tasks as task (task.id)}
                                <div class="group grid grid-cols-[minmax(20rem,1fr)_8rem_12rem_10rem_10rem] items-center px-4 py-3 transition-colors hover:bg-accent/30 {selectedTask?.id === task.id ? 'bg-accent/60' : ''} {compact ? 'text-xs' : 'text-sm'}">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <input type="checkbox" class="size-3.5 shrink-0" aria-label={'Pilih ' + task.identifier} checked={selectedTaskIds.includes(task.id)} onchange={() => toggleTask(task.id)} />
                                        <Link href={taskPeekUrl(task.id)} class="min-w-0 rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <span class="flex min-w-0 items-center gap-2"><span class="shrink-0 font-mono text-[11px] text-muted-foreground">{task.identifier}</span><span class="truncate font-medium group-hover:text-primary">{task.title}</span></span>
                                        </Link>
                                    </div>
                                    <div>{#if task.priority_label}<span class={`inline-flex min-h-6 items-center gap-1 rounded-md border px-2 text-xs font-medium ${priorityTone(task.priority_label)}`}><CircleAlert class="size-3" aria-hidden="true" />{task.priority_label}</span>{:else}<span class="text-xs text-muted-foreground">Belum diatur</span>{/if}</div>
                                    <span class="truncate text-muted-foreground">{task.milestone?.name ?? 'Tanpa milestone'}</span>
                                    <span class="inline-flex items-center gap-1.5 {isOverdue(task) ? 'font-medium text-destructive' : 'text-muted-foreground'}"><CalendarDays class="size-3.5 shrink-0" aria-hidden="true" />{formatDueDate(task.due_date)}</span>
                                    {#if task.assignee}<UserAvatar user={task.assignee} size="size-6" class="text-muted-foreground" />{:else}<span class="inline-flex items-center gap-1.5 text-muted-foreground"><CircleDashed class="size-3.5" aria-hidden="true" />Belum ada PIC</span>{/if}
                                </div>
                            {/each}
                        </div>
                    </section>
                {/each}
            </div>
            <div class="grid gap-2 p-3 lg:hidden">
                <div class="flex justify-end border-b px-1 pb-2 text-xs text-muted-foreground"><span>{tasks.length} Task</span></div>
                {#each groupedTasks as group (group.key)}
                    {#if grouping !== 'none'}<div class="px-1 pt-2 text-xs font-semibold text-muted-foreground">{group.label} <span class="font-normal">· {group.tasks.length}</span></div>{/if}
                    {#each group.tasks as task (task.id)}
                        <article class="rounded-lg border border-border/80 bg-card p-3 transition-colors hover:bg-accent/30">
                            <Link href={taskPeekUrl(task.id)} class="block min-w-0 rounded-sm py-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><p class="font-mono text-xs text-muted-foreground">{task.identifier}</p><p class="mt-1 text-[13px] font-medium">{task.title}</p></Link>
                            <div class="mt-3 flex flex-wrap items-center gap-2"><Badge variant={task.status === 'done' ? 'secondary' : 'outline'}>{task.status_label}</Badge>{#if task.priority_label}<Badge variant="outline">{task.priority_label}</Badge>{/if}</div>
                            <dl class="mt-3 grid grid-cols-2 gap-3 border-t pt-3 text-xs"><div><dt class="text-muted-foreground">PIC</dt><dd class="mt-1 font-medium">{task.assignee?.name ?? 'Belum ada PIC'}</dd></div><div><dt class="text-muted-foreground">Deadline</dt><dd class="mt-1 font-medium">{task.due_date ?? 'Tanpa deadline'}</dd></div></dl>
                        </article>
                    {/each}
                {/each}
            </div>
        </section>
    {/if}
    {:else if view === 'board'}
        <Board {workspace} {project} {tasks} {statuses} {filters} {allowedTransitions} grouping={grouping === 'status' ? 'none' : grouping} embedded />
    {:else if view === 'calendar'}
        <Calendar {workspace} {project} {tasks} {milestones} {editableTaskIds} taskQuery={preservedQuery(true)} embedded />
    {:else}
        <Gantt {workspace} {project} tasks={scheduledTasks} {milestones} {modules} {editableTaskIds} taskQuery={preservedQuery(true)} embedded />
    {/if}

        </div>
    </div>
</div>

{#if taskDetail}
    <Sheet open onOpenChange={(open) => { if (!open) closeTaskDetail(); }}>
        <SheetContent side="right" class="box-border w-full max-w-[100vw] overflow-x-hidden overflow-y-auto p-0 sm:max-w-xl">
            <TaskDetail {...taskDetail} embedded />
        </SheetContent>
    </Sheet>
{/if}

<Dialog bind:open={createTaskOpen} onOpenChange={(open) => { createTaskOpen = open; }}>
    <DialogContent class="max-h-[92vh] max-w-xl overflow-y-auto border-border bg-background p-0 shadow-lg">
        <div class="px-6 pb-1 pt-5 sm:px-7"><DialogTitle class="text-sm font-medium text-muted-foreground">Task:</DialogTitle></div>
        <Form {...TaskController.store.form({ workspace: workspace.id, project: project.id })} class="grid gap-4 px-6 pb-5 sm:px-7" onSuccess={() => { createTaskOpen = false; createAssigneeId = 'none'; createPriority = 'none'; }}>
            {#snippet children({ errors, processing })}
                <div class="grid gap-2"><Label for="create-task-title" class="sr-only">Judul Task</Label><Input id="create-task-title" name="title" class="h-auto rounded-none border-0 bg-transparent px-0 text-xl font-medium shadow-none focus-visible:ring-0" required maxlength={200} placeholder="Apa yang perlu dikerjakan?" autocomplete="off" /><InputError message={errors.title} /></div>
                <div class="grid gap-3 border-t border-border/70 pt-4"><div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center"><div class="flex items-center gap-2 text-sm text-muted-foreground"><CircleDashed class="size-4" aria-hidden="true" />Status</div><span class="inline-flex w-fit items-center gap-2 text-sm font-semibold text-foreground"><CircleDashed class="size-3.5 text-slate-400" aria-hidden="true" />Backlog</span></div><div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center"><div class="flex items-center gap-2 text-sm text-muted-foreground"><CircleAlert class="size-4" aria-hidden="true" />Priority</div><Select value={createPriority} onValueChange={(value) => { createPriority = value; }}><SelectTrigger id="create-task-priority" class="h-9 w-fit min-w-0 max-w-full justify-self-start rounded-md border border-border/70 bg-transparent px-2 text-sm shadow-none focus:ring-0"><span class="inline-flex items-center gap-2 px-1.5 py-1 text-muted-foreground">{#if selectedCreatePriority}<CircleAlert class="size-3.5" aria-hidden="true" />{selectedCreatePriority.label}{:else}Tidak ada priority{/if}</span></SelectTrigger><SelectContent><SelectItem value="none"><span class="inline-flex min-h-6 items-center rounded-md px-2 text-xs text-muted-foreground">Tidak ada priority</span></SelectItem>{#each priorities as priority (priority.value)}<SelectItem value={priority.value}><span class={`inline-flex min-h-6 items-center gap-1 rounded-md px-2 text-xs font-medium ${priorityTone(priority.label)}`}><CircleAlert class={`size-3.5 ${createPriorityTone(priority.value)}`} aria-hidden="true" />{priority.label}</span></SelectItem>{/each}</SelectContent></Select><input type="hidden" name="priority" value={createPriority === 'none' ? '' : createPriority} /></div><div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center"><div class="flex items-center gap-2 text-sm text-muted-foreground"><User class="size-4" aria-hidden="true" />PIC</div><Select value={createAssigneeId} onValueChange={(value) => { createAssigneeId = value; }}><SelectTrigger id="create-task-assignee" class="h-9 w-fit min-w-0 max-w-full justify-self-start rounded-md border border-border/70 bg-transparent px-2 text-sm shadow-none focus:ring-0"><span class="inline-flex min-w-0 items-center gap-2 px-1.5 py-1 text-muted-foreground">{#if selectedCreateAssignee}<span class={`flex size-5 shrink-0 items-center justify-center rounded-full text-[9px] font-semibold ${avatarColor(selectedCreateAssignee.name)}`} aria-hidden="true">{getInitials(selectedCreateAssignee.name)}</span><span class="truncate">{selectedCreateAssignee.name}</span>{:else}<span class="text-muted-foreground">Belum ada PIC</span>{/if}</span></SelectTrigger><SelectContent><SelectItem value="none"><span class="inline-flex min-h-6 items-center rounded-md px-2 text-xs text-muted-foreground">Belum ada PIC</span></SelectItem>{#each filterMembers as member (member.id)}<SelectItem value={String(member.id)}><span class="inline-flex min-h-6 items-center gap-1.5 rounded-md px-2 text-xs"><span class={`flex size-5 shrink-0 items-center justify-center rounded-full text-[9px] font-semibold ${avatarColor(member.name)}`} aria-hidden="true">{getInitials(member.name)}</span>{member.name}</span></SelectItem>{/each}</SelectContent></Select><input type="hidden" name="assignee_id" value={createAssigneeId === 'none' ? '' : createAssigneeId} /></div><div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center"><div class="flex items-center gap-2 text-sm text-muted-foreground"><CalendarDays class="size-4" aria-hidden="true" />Deadline</div><Input id="create-task-due-date" name="due_date" type="date" class="h-8 w-auto border-0 bg-muted/30 px-2 text-sm shadow-none focus-visible:ring-0" /></div><div class="grid gap-1 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-start"><div></div><div class="grid gap-1 sm:grid-cols-2"><InputError message={errors.priority} /><InputError message={errors.assignee_id} /><InputError message={errors.due_date} /></div></div></div>
                <div class="grid gap-1.5"><div class="flex items-center gap-2 text-xs text-muted-foreground"><TextAlignLeft class="size-3.5" aria-hidden="true" /><Label for="create-task-description" class="font-normal">Deskripsi <span>(opsional)</span></Label></div><textarea id="create-task-description" name="description" rows={3} maxlength={10000} placeholder="Tambahkan konteks pekerjaan..." class="min-h-20 w-full resize-y border-0 bg-transparent px-0 py-1 text-sm leading-6 outline-none placeholder:text-muted-foreground/60 focus-visible:ring-0"></textarea><InputError message={errors.description} /></div>
                <div class="grid gap-1.5"><div class="flex items-center gap-2 text-xs text-muted-foreground"><TextAlignLeft class="size-3.5" aria-hidden="true" /><Label for="create-task-outcome" class="font-normal">Hasil yang diharapkan <span>(opsional)</span></Label></div><textarea id="create-task-outcome" name="expected_outcome" rows={2} maxlength={10000} placeholder="Hasil akhir yang ingin dicapai..." class="min-h-14 w-full resize-y border-0 bg-transparent px-0 py-1 text-sm leading-6 outline-none placeholder:text-muted-foreground/60 focus-visible:ring-0"></textarea><InputError message={errors.expected_outcome} /></div>
                <div class="flex flex-col-reverse gap-2 border-t border-border/70 pt-4 sm:flex-row sm:justify-end"><Button type="button" variant="ghost" onclick={() => { createTaskOpen = false; }}>Batal</Button><Button type="submit" disabled={processing}>{processing ? 'Membuat...' : 'Buat Task'}</Button></div>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>

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
