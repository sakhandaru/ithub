<script module lang="ts">
    import { calendar } from '@/routes/workspaces/projects';

    export const layout = {
        breadcrumbs: [{ title: 'Calendar', href: calendar(['', '']) }],
    };
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import { Calendar as FullCalendar, type CalendarOptions, type EventClickInfo, type EventDropInfo } from 'fullcalendar';
    import dayGridPlugin from 'fullcalendar/daygrid';
    import listPlugin from 'fullcalendar/list';
    import timeGridPlugin from 'fullcalendar/timegrid';
    import classicThemePlugin from 'fullcalendar/themes/classic';
    import idLocale from 'fullcalendar/locales/id';
    import { onMount } from 'svelte';
    import BarChart3 from 'carbon-icons-svelte/lib/ChartBar.svelte';
    import CalendarDays from 'carbon-icons-svelte/lib/Calendar.svelte';
    import ChevronLeft from 'carbon-icons-svelte/lib/ChevronLeft.svelte';
    import ChevronRight from 'carbon-icons-svelte/lib/ChevronRight.svelte';
    import CircleAlert from 'carbon-icons-svelte/lib/WarningAlt.svelte';
    import Filter from 'carbon-icons-svelte/lib/Filter.svelte';
    import ListTodo from 'carbon-icons-svelte/lib/Task.svelte';
    import SlidersHorizontal from 'carbon-icons-svelte/lib/SettingsAdjust.svelte';
    import Users from 'carbon-icons-svelte/lib/UserMultiple.svelte';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import AppHead from '@/components/AppHead.svelte';
    import ProjectContextNav from '@/components/ProjectContextNav.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
    import { globalHeaderLabels, taskBreadcrumbs } from '@/lib/page-header';
    import { toUrl } from '@/lib/utils';

    import 'fullcalendar/skeleton.css';
    import 'fullcalendar/themes/classic/theme.css';
    import 'fullcalendar/themes/classic/palette.css';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null };
    type Task = {
        id: string;
        identifier: string;
        title: string;
        status: string;
        status_label: string;
        priority_label: string | null;
        assignee: { id: number; name: string } | null;
        start_date: string | null;
        due_date: string | null;
    };
    type Milestone = { id: string; name: string; target_date: string | null };
    type Workload = { key: string; name: string; total: number; overdue: number; dueSoon: number };

    let { workspace, project, tasks, milestones = [], editableTaskIds = [], taskQuery = {}, embedded = false }: { workspace: Workspace; project: Project; tasks: Task[]; milestones?: Milestone[]; editableTaskIds?: string[]; taskQuery?: Record<string, string | number | undefined>; embedded?: boolean } = $props();
    let calendarElement = $state<HTMLElement | null>(null);
    let fullCalendar = $state<FullCalendar | null>(null);
    let busyTaskId = $state<string | null>(null);
    let updateError = $state('');
    let calendarTitle = $state('Calendar');
    let calendarView = $state<'dayGridMonth' | 'timeGridWeek' | 'listWeek'>('dayGridMonth');
    let filterOpen = $state(false);
    let optionsOpen = $state(false);
    let showMilestones = $state(true);
    let showWorkload = $state(false);

    const today = (): string => {
        const date = new Date();
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    };

    const addDays = (dateString: string, days: number): string => {
        const date = new Date(`${dateString}T00:00:00`);
        date.setDate(date.getDate() + days);

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    };

    const formatDueDate = (dateString: string): string => new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
    }).format(new Date(`${dateString}T00:00:00`));

    const todayString = $derived(today());
    const weekEndString = $derived(addDays(todayString, 7));
    const scheduledTasks = $derived(tasks.filter((task) => task.due_date));
    const noDeadlineTasks = $derived(tasks.filter((task) => !task.due_date));
    const overdueTasks = $derived(tasks.filter((task) => task.due_date && task.due_date < todayString && !['done', 'canceled'].includes(task.status)));
    const dueThisWeekTasks = $derived(tasks.filter((task) => task.due_date && task.due_date >= todayString && task.due_date <= weekEndString && !['done', 'canceled'].includes(task.status)));
    const attentionTasks = $derived([...overdueTasks, ...tasks.filter((task) => task.status === 'blocked' && !overdueTasks.some((item) => item.id === task.id)), ...tasks.filter((task) => !task.assignee && !overdueTasks.some((item) => item.id === task.id) && task.status !== 'done')].filter((task, index, items) => items.findIndex((item) => item.id === task.id) === index).slice(0, 6));
    const workload = $derived.by((): Workload[] => {
        const groups = new Map<string, Workload>();
        for (const task of tasks.filter((item) => !['done', 'canceled'].includes(item.status))) {
            const key = task.assignee?.id.toString() ?? 'unassigned';
            const group = groups.get(key) ?? { key, name: task.assignee?.name ?? 'Belum ada PIC', total: 0, overdue: 0, dueSoon: 0 };
            group.total += 1;
            if (task.due_date && task.due_date < todayString) group.overdue += 1;
            if (task.due_date && task.due_date >= todayString && task.due_date <= weekEndString) group.dueSoon += 1;
            groups.set(key, group);
        }

        return [...groups.values()].sort((first, second) => second.total - first.total || first.name.localeCompare(second.name)).slice(0, 6);
    });

    const taskEvents = $derived(scheduledTasks.map((task) => ({
        id: task.id,
        title: `${task.identifier} · ${task.title}`,
        start: task.start_date ?? task.due_date as string,
        allDay: true,
        end: task.start_date && task.due_date && task.start_date !== task.due_date ? addDays(task.due_date, 1) : undefined,
        editable: editableTaskIds.includes(task.id),
        classNames: [
            `task-status-${task.status}`,
            task.due_date && task.due_date < todayString && !['done', 'canceled'].includes(task.status) ? 'task-overdue' : '',
        ].filter(Boolean),
        extendedProps: { taskId: task.id, status: task.status_label, statusCode: task.status, priority: task.priority_label, assignee: task.assignee?.name ?? 'Belum ada PIC' },
    })));
    const milestoneEvents = $derived(milestones.filter((milestone) => milestone.target_date).map((milestone) => ({
        id: `milestone-${milestone.id}`,
        title: `Milestone · ${milestone.name}`,
        start: milestone.target_date as string,
        allDay: true,
        editable: false,
        classNames: ['project-milestone'],
        extendedProps: { milestone: true, status: 'Target milestone' },
    })));
    const events = $derived([...taskEvents, ...(showMilestones ? milestoneEvents : [])]);
    const calendarViewKey = $derived(`ithub:calendar-view:${page.props.auth?.user?.id ?? 'guest'}:${project.id}`);
    const calendarOptionsKey = $derived(`ithub:calendar-options:${page.props.auth?.user?.id ?? 'guest'}:${project.id}`);
    const calendarUrl = (query: Record<string, string | number | undefined> = {}): string => toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query: { ...taskQuery, ...query, view: 'calendar' } }));

    function persistCalendarOptions(): void {
        try {
            localStorage.setItem(calendarOptionsKey, JSON.stringify({ showMilestones, showWorkload }));
        } catch {
        }
    }

    function toggleMilestones(): void {
        showMilestones = !showMilestones;
        persistCalendarOptions();
    }

    function toggleWorkload(): void {
        showWorkload = !showWorkload;
        persistCalendarOptions();
    }

    function changeCalendarView(view: 'dayGridMonth' | 'timeGridWeek' | 'listWeek'): void {
        calendarView = view;
        fullCalendar?.changeView(view);
    }

    function openTask(info: EventClickInfo): void {
        const taskId = String(info.event.extendedProps.taskId ?? '');
        if (!taskId || info.event.extendedProps.milestone) return;
        openTaskById(taskId);
    }

    function openTaskById(taskId: string): void {
        if (!taskId) return;
        router.visit(toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query: { ...taskQuery, view: 'calendar', task: taskId } })));
    }

    function updateDueDate(info: EventDropInfo): void {
        const taskId = String(info.event.extendedProps.taskId ?? '');
        const dueDate = info.event.startStr.slice(0, 10);
        if (!taskId || !dueDate || busyTaskId) {
            info.revert();

            return;
        }

        busyTaskId = taskId;
        updateError = '';
        router.patch(toUrl(TaskController.update({ workspace: workspace.id, project: project.id, task: taskId })), { due_date: dueDate }, {
            preserveScroll: true,
            onFinish: () => { busyTaskId = null; },
            onError: () => {
                info.revert();
                updateError = 'Deadline gagal diperbarui. Task dikembalikan ke tanggal sebelumnya.';
            },
        });
    }

    onMount(() => {
        if (!calendarElement) return;

        const allowedViews = ['dayGridMonth', 'timeGridWeek', 'listWeek'];
        let initialView = window.matchMedia('(max-width: 639px)').matches ? 'listWeek' : 'dayGridMonth';
        try {
            const savedView = localStorage.getItem(calendarViewKey);
            if (savedView && allowedViews.includes(savedView)) initialView = savedView;
            const savedOptions = localStorage.getItem(calendarOptionsKey);
            if (savedOptions) {
                const parsed = JSON.parse(savedOptions) as { showMilestones?: boolean; showWorkload?: boolean };
                showMilestones = parsed.showMilestones ?? true;
                showWorkload = parsed.showWorkload ?? false;
            }
        } catch {
            initialView = window.matchMedia('(max-width: 639px)').matches ? 'listWeek' : 'dayGridMonth';
        }
        calendarView = initialView as typeof calendarView;

        const options: CalendarOptions = {
            plugins: [dayGridPlugin, timeGridPlugin, listPlugin, classicThemePlugin],
            locales: [idLocale],
            locale: 'id',
            initialView,
            firstDay: 1,
            height: 'auto',
            editable: !project.archived_at,
            selectable: false,
            dayMaxEvents: true,
            eventContent: ({ event }) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'fc-event-content';

                const marker = document.createElement('span');
                marker.className = 'fc-event-marker';
                marker.setAttribute('aria-hidden', 'true');
                marker.textContent = event.extendedProps.milestone ? '◆' : event.extendedProps.statusCode === 'done' ? '✓' : '•';
                wrapper.append(marker);

                const body = document.createElement('div');
                body.className = 'fc-event-body';

                const title = document.createElement('div');
                title.className = 'fc-event-title';
                title.textContent = event.title;
                body.append(title);

                const meta = document.createElement('div');
                meta.className = 'fc-event-meta';
                meta.textContent = [event.extendedProps.status, event.extendedProps.priority, event.extendedProps.assignee].filter(Boolean).join(' · ');
                body.append(meta);
                wrapper.append(body);

                return { domNodes: [wrapper] };
            },
            eventDidMount: ({ event, el }) => {
                const { status, priority, assignee } = event.extendedProps;
                const details = [status, priority, assignee].filter(Boolean).join(' · ');
                el.setAttribute('title', details ? `${event.title} · ${details}` : event.title);
                el.setAttribute('aria-label', details ? `${event.title}, ${details}` : event.title);
                el.tabIndex = 0;
                el.addEventListener('keydown', (keyboardEvent) => {
                    if ((keyboardEvent.key !== 'Enter' && keyboardEvent.key !== ' ') || event.extendedProps.milestone) return;
                    keyboardEvent.preventDefault();
                    openTaskById(String(event.extendedProps.taskId ?? ''));
                });
            },
            headerToolbar: false,
            events,
            eventClick: openTask,
            eventDrop: updateDueDate,
            datesSet: ({ view }) => {
                calendarTitle = view.title;
                calendarView = view.type as typeof calendarView;
                try {
                    localStorage.setItem(calendarViewKey, view.type);
                } catch {
                }
            },
        };

        fullCalendar = new FullCalendar(calendarElement, options);
        fullCalendar.render();

        return () => {
            fullCalendar?.destroy();
            fullCalendar = null;
        };
    });

    $effect(() => {
        fullCalendar?.removeAllEventSources();
        if (fullCalendar) fullCalendar.addEventSource(events);
    });

</script>

{#if !embedded}
    <AppHead title={project.identifier + ' · Calendar'} />
{/if}

<div class={embedded ? 'min-w-0' : 'flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8'}>
    {#if !embedded}
    <PageHeader breadcrumbs={taskBreadcrumbs(page.props.unitContext?.active?.name ?? workspace.name, page.props.unitContext?.active?.id, toUrl(ProjectController.index(workspace.id)), project.name, toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), toUrl(TaskController.index({ workspace: workspace.id, project: project.id })), tasks.length, globalHeaderLabels.task, page.props.projectContext?.active?.icon)} showSidebarTrigger>
        {#snippet actions()}<ProjectContextNav {workspace} {project} active="calendar" taskQuery={taskQuery} /><Link href={toUrl(TaskController.index({ workspace: workspace.id, project: project.id }))} class="inline-flex h-7 items-center rounded-md border border-border/80 bg-background px-2.5 text-xs text-muted-foreground hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring sm:self-auto">Buka List</Link>{/snippet}
    </PageHeader>
    {/if}
    {#if project.archived_at}
        <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan dan Calendar bersifat read-only.</div>
    {/if}

    {#if updateError}
        <div class="rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive" role="alert">{updateError} Coba geser kembali atau buka detail Task untuk mengubah deadline.</div>
    {/if}

    <section class="ithub-calendar flex flex-col rounded-lg border border-border/80 bg-card p-3 sm:p-5" aria-label="Calendar Task">
        <div class="order-1 flex flex-col gap-3 border-b border-border/70 pb-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-center gap-2">
                <div class="flex items-center gap-1">
                    <button type="button" class="inline-flex size-9 items-center justify-center rounded-md border border-border/80 hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label="Periode sebelumnya" onclick={() => fullCalendar?.prev()}><ChevronLeft class="size-4" aria-hidden="true" /></button>
                    <button type="button" class="inline-flex size-9 items-center justify-center rounded-md border border-border/80 hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label="Periode berikutnya" onclick={() => fullCalendar?.next()}><ChevronRight class="size-4" aria-hidden="true" /></button>
                    <button type="button" class="ml-1 inline-flex min-h-9 items-center rounded-md border border-border/80 px-3 text-xs font-medium hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" onclick={() => fullCalendar?.today()}>Today</button>
                </div>
                <h2 class="min-w-0 truncate text-base font-semibold capitalize sm:text-lg">{calendarTitle}</h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex rounded-md border border-border/80 p-0.5" aria-label="Tampilan Calendar">
                    <button type="button" class="min-h-8 rounded px-2.5 text-xs {calendarView === 'dayGridMonth' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}" aria-pressed={calendarView === 'dayGridMonth'} onclick={() => changeCalendarView('dayGridMonth')}>Bulan</button>
                    <button type="button" class="min-h-8 rounded px-2.5 text-xs {calendarView === 'timeGridWeek' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}" aria-pressed={calendarView === 'timeGridWeek'} onclick={() => changeCalendarView('timeGridWeek')}>Minggu</button>
                    <button type="button" class="min-h-8 rounded px-2.5 text-xs {calendarView === 'listWeek' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}" aria-pressed={calendarView === 'listWeek'} onclick={() => changeCalendarView('listWeek')}>List</button>
                </div>
                <Sheet bind:open={filterOpen}>
                    <SheetTrigger asChild>
                        {#snippet children(props)}<button type="button" class="inline-flex min-h-9 items-center gap-1.5 rounded-md border border-border/80 px-2.5 text-xs font-medium hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" {...props}><Filter class="size-3.5" aria-hidden="true" />Filter</button>{/snippet}
                    </SheetTrigger>
                    <SheetContent side="right" class="w-full sm:max-w-sm"><SheetHeader><SheetTitle>Filter Calendar</SheetTitle></SheetHeader><div class="mt-5 grid gap-2"><Link href={calendarUrl({ preset: undefined, status: undefined, priority: undefined, assignee: undefined, milestone: undefined, no_deadline: undefined })} class="min-h-11 rounded-md border px-3 py-3 text-sm hover:bg-muted" onclick={() => { filterOpen = false; }}>Semua Task</Link><Link href={calendarUrl({ preset: 'week' })} class="min-h-11 rounded-md border px-3 py-3 text-sm hover:bg-muted" onclick={() => { filterOpen = false; }}>Minggu ini</Link><Link href={calendarUrl({ preset: 'overdue' })} class="min-h-11 rounded-md border px-3 py-3 text-sm hover:bg-muted" onclick={() => { filterOpen = false; }}>Terlambat</Link><Link href={calendarUrl({ preset: 'no_deadline' })} class="min-h-11 rounded-md border px-3 py-3 text-sm hover:bg-muted" onclick={() => { filterOpen = false; }}>Tanpa deadline</Link><Link href={calendarUrl({ preset: 'blocked' })} class="min-h-11 rounded-md border px-3 py-3 text-sm hover:bg-muted" onclick={() => { filterOpen = false; }}>Blocked</Link></div></SheetContent>
                </Sheet>
                <Sheet bind:open={optionsOpen}>
                    <SheetTrigger asChild>
                        {#snippet children(props)}<button type="button" class="inline-flex min-h-9 items-center gap-1.5 rounded-md border border-border/80 px-2.5 text-xs font-medium hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" {...props}><SlidersHorizontal class="size-3.5" aria-hidden="true" />Options</button>{/snippet}
                    </SheetTrigger>
                    <SheetContent side="right" class="w-full sm:max-w-sm"><SheetHeader><SheetTitle>Options Calendar</SheetTitle></SheetHeader><div class="mt-5 grid gap-3"><label class="flex min-h-11 items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm"><span>Tampilkan milestone</span><input type="checkbox" class="size-4" checked={showMilestones} onchange={toggleMilestones} /></label><label class="flex min-h-11 items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm"><span>Tampilkan workload</span><input type="checkbox" class="size-4" checked={showWorkload} onchange={toggleWorkload} /></label></div></SheetContent>
                </Sheet>
                <button type="button" class="inline-flex min-h-9 items-center gap-1.5 rounded-md border border-border/80 px-2.5 text-xs font-medium hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-pressed={showWorkload} onclick={toggleWorkload}><BarChart3 class="size-3.5" aria-hidden="true" />Insights</button>
            </div>
        </div>

        <div class="order-3 mt-4 grid gap-0 border-y border-border/70 sm:grid-cols-4" aria-label="Ringkasan Calendar">
            <div class="px-3 py-2.5 sm:border-r sm:border-border/70"><p class="text-[11px] text-muted-foreground">Terjadwal</p><p class="mt-0.5 text-sm font-semibold">{scheduledTasks.length}</p></div>
            <div class="px-3 py-2.5 sm:border-r sm:border-border/70"><p class="text-[11px] text-muted-foreground">Terlambat</p><p class="mt-0.5 text-sm font-semibold {overdueTasks.length > 0 ? 'text-destructive' : ''}">{overdueTasks.length}</p></div>
            <div class="px-3 py-2.5 sm:border-r sm:border-border/70"><p class="text-[11px] text-muted-foreground">7 hari ke depan</p><p class="mt-0.5 text-sm font-semibold">{dueThisWeekTasks.length}</p></div>
            <div class="px-3 py-2.5"><p class="text-[11px] text-muted-foreground">Tanpa deadline</p><p class="mt-0.5 text-sm font-semibold {noDeadlineTasks.length > 0 ? 'text-amber-700 dark:text-amber-300' : ''}">{noDeadlineTasks.length}</p></div>
        </div>

        {#if showWorkload && workload.length > 0}
            {@const maxWorkload = Math.max(...workload.map((item) => item.total))}
            <section class="order-4 mt-4 rounded-md border border-border/70 bg-background p-3 sm:p-4" aria-labelledby="calendar-workload-heading">
                <div class="flex flex-wrap items-baseline justify-between gap-2"><div><h2 id="calendar-workload-heading" class="text-sm font-semibold">Beban per PIC</h2><p class="mt-1 text-xs text-muted-foreground">Task aktif yang tampil pada Calendar ini.</p></div><span class="text-xs text-muted-foreground">{workload.reduce((total, item) => total + item.total, 0)} Task aktif</span></div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    {#each workload as item (item.key)}
                        <div class="min-w-0">
                            <div class="flex items-center justify-between gap-3 text-xs"><span class="truncate font-medium">{item.name}</span><span class="shrink-0 text-muted-foreground">{item.total}</span></div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-muted" role="progressbar" aria-label={`Beban ${item.name}`} aria-valuemin="0" aria-valuemax={maxWorkload} aria-valuenow={item.total}><div class="h-full rounded-full bg-primary" style={`width: ${(item.total / maxWorkload) * 100}%`}></div></div>
                            <div class="mt-1 flex gap-3 text-[11px] text-muted-foreground">{#if item.dueSoon}<span>{item.dueSoon} minggu ini</span>{/if}{#if item.overdue}<span class="text-destructive">{item.overdue} terlambat</span>{/if}{#if !item.dueSoon && !item.overdue}<span>Tanpa risiko waktu</span>{/if}</div>
                        </div>
                    {/each}
                </div>
            </section>
        {/if}

        {#if tasks.length === 0}
            <div class="order-2 mt-4 flex min-h-48 items-center justify-center rounded-md border border-dashed px-6 py-10 text-center">
                <div class="max-w-sm"><CalendarDays class="mx-auto size-8 text-muted-foreground" aria-hidden="true" /><h2 class="mt-3 text-sm font-semibold">Belum ada Task yang cocok</h2><p class="mt-1 text-sm text-muted-foreground">Coba ubah filter atau buat Task dengan deadline untuk mulai menggunakan Calendar.</p></div>
            </div>
        {:else}
            <div class="order-2 mt-4" bind:this={calendarElement}></div>
        {/if}

        {#if attentionTasks.length > 0 || noDeadlineTasks.length > 0}
            <div class="order-5 mt-5 grid gap-5 border-t border-border/70 pt-5 xl:grid-cols-2">
                {#if attentionTasks.length > 0}
                    <section aria-labelledby="calendar-attention-heading">
                        <div class="flex items-center gap-2"><CircleAlert class="size-4 text-amber-600 dark:text-amber-300" aria-hidden="true" /><h2 id="calendar-attention-heading" class="text-sm font-semibold">Perlu perhatian</h2></div>
                        <div class="mt-3 grid gap-2">
                            {#each attentionTasks as task (task.id)}
                                <Link href={calendarUrl({ task: task.id })} preserveScroll class="flex min-h-11 items-center justify-between gap-3 rounded-md border border-border/70 px-3 py-2 text-sm hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <span class="min-w-0"><span class="font-mono text-[11px] text-muted-foreground">{task.identifier}</span><span class="mt-0.5 block truncate font-medium">{task.title}</span></span>
                                    <span class="shrink-0 text-xs text-muted-foreground">{task.due_date ? formatDueDate(task.due_date) : task.status_label}</span>
                                </Link>
                            {/each}
                        </div>
                    </section>
                {/if}

                {#if noDeadlineTasks.length > 0}
                    <section aria-labelledby="calendar-unscheduled-heading">
                        <div class="flex items-center gap-2"><ListTodo class="size-4 text-muted-foreground" aria-hidden="true" /><h2 id="calendar-unscheduled-heading" class="text-sm font-semibold">Belum dijadwalkan <span class="font-normal text-muted-foreground">· {noDeadlineTasks.length}</span></h2></div>
                        <div class="mt-3 grid gap-2">
                            {#each noDeadlineTasks.slice(0, 6) as task (task.id)}
                                <Link href={calendarUrl({ task: task.id })} preserveScroll class="flex min-h-11 items-center justify-between gap-3 rounded-md border border-dashed border-border/70 px-3 py-2 text-sm hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <span class="min-w-0"><span class="font-mono text-[11px] text-muted-foreground">{task.identifier}</span><span class="mt-0.5 block truncate font-medium">{task.title}</span></span>
                                    <span class="flex shrink-0 items-center gap-1 text-xs text-muted-foreground"><Users class="size-3.5" aria-hidden="true" />{task.assignee?.name ?? 'Belum ada PIC'}</span>
                                </Link>
                            {/each}
                        </div>
                        {#if noDeadlineTasks.length > 6}<p class="mt-2 text-xs text-muted-foreground">Menampilkan 6 dari {noDeadlineTasks.length} Task.</p>{/if}
                    </section>
                {/if}
            </div>
        {/if}

        <div class="order-6 mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-border/70 pt-3 text-xs text-muted-foreground" aria-label="Legenda status Task">
            <span class="font-medium text-foreground">Status</span>
            <span class="inline-flex items-center gap-2"><span class="size-2 rounded-full bg-primary" aria-hidden="true"></span>Aktif</span>
            <span class="inline-flex items-center gap-2"><span class="size-2 rounded-full bg-muted-foreground" aria-hidden="true"></span>Selesai</span>
            <span class="inline-flex items-center gap-2"><span class="size-2 rounded-full bg-destructive" aria-hidden="true"></span>Blocked atau Canceled</span>
            <span class="inline-flex items-center gap-2"><span class="size-2 rounded-full bg-amber-500" aria-hidden="true"></span>Terlambat</span>
            <span class="inline-flex items-center gap-2"><span class="size-2 rotate-45 bg-accent" aria-hidden="true"></span>Milestone</span>
        </div>
        <p class="order-7 mt-3 text-xs text-muted-foreground">Klik event atau Task untuk membuka detail. Task yang dapat diubah bisa digeser ke tanggal baru. Gunakan detail Task untuk mengubah deadline dengan keyboard.</p>
    </section>
</div>

<style>
    :global(.ithub-calendar .fc) {
        --fc-border-color: var(--border);
        --fc-page-bg-color: transparent;
        --fc-neutral-bg-color: color-mix(in oklch, var(--muted) 35%, transparent);
        --fc-list-event-hover-bg-color: color-mix(in oklch, var(--muted) 50%, transparent);
        --fc-today-bg-color: color-mix(in oklch, var(--primary) 8%, transparent);
        --fc-event-border-color: var(--primary);
        --fc-event-bg-color: var(--card);
        --fc-event-text-color: var(--foreground);
        color: var(--foreground);
    }

    :global(.ithub-calendar .fc-toolbar) {
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    :global(.ithub-calendar .fc-toolbar-chunk) {
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    :global(.ithub-calendar .fc-button) {
        border-color: var(--border);
        background: var(--background);
        color: var(--foreground);
        box-shadow: none;
        border-radius: 0.375rem;
        padding: 0.45rem 0.7rem;
        text-transform: none;
    }

    :global(.ithub-calendar .fc-button:hover),
    :global(.ithub-calendar .fc-button:focus) {
        border-color: var(--ring);
        background: var(--muted);
        color: var(--foreground);
    }

    :global(.ithub-calendar .fc-button-primary:not(:disabled).fc-button-active) {
        border-color: var(--primary);
        background: var(--primary);
        color: var(--primary-foreground);
    }

    :global(.ithub-calendar .fc-event) {
        cursor: pointer;
        border-radius: 0.375rem;
        border-width: 1px;
        border-left-width: 3px;
        padding: 0.2rem 0.35rem;
        font-size: 0.75rem;
        line-height: 1.25rem;
        box-shadow: 0 1px 2px color-mix(in oklch, var(--foreground) 8%, transparent);
    }

    :global(.ithub-calendar .fc-event:focus-visible) {
        outline: 2px solid var(--ring);
        outline-offset: 2px;
    }

    :global(.ithub-calendar .fc-event-content) {
        display: flex;
        align-items: flex-start;
        gap: 0.35rem;
        min-width: 0;
    }

    :global(.ithub-calendar .fc-event-marker) {
        flex: 0 0 auto;
        font-size: 0.85rem;
        line-height: 1.25rem;
    }

    :global(.ithub-calendar .fc-event-body) {
        min-width: 0;
    }

    :global(.ithub-calendar .fc-event-title),
    :global(.ithub-calendar .fc-event-meta) {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    :global(.ithub-calendar .fc-event-meta) {
        font-size: 0.65rem;
        line-height: 1rem;
        opacity: 0.82;
    }

    :global(.ithub-calendar .fc-daygrid-day-number) {
        padding: 0.5rem;
        color: var(--muted-foreground);
        font-size: 0.75rem;
    }

    :global(.ithub-calendar .fc-col-header-cell-cushion) {
        padding: 0.65rem 0.25rem;
        color: var(--muted-foreground);
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    :global(.ithub-calendar .task-status-done) {
        --fc-event-bg-color: var(--card);
        --fc-event-border-color: var(--muted-foreground);
        --fc-event-text-color: var(--foreground);
    }

    :global(.ithub-calendar .fc-event-marker) {
        color: var(--primary);
    }

    :global(.ithub-calendar .task-status-done .fc-event-marker) {
        color: var(--muted-foreground);
    }

    :global(.ithub-calendar .task-status-blocked .fc-event-marker),
    :global(.ithub-calendar .task-status-canceled .fc-event-marker),
    :global(.ithub-calendar .task-overdue .fc-event-marker) {
        color: var(--destructive);
    }

    :global(.ithub-calendar .task-status-blocked),
    :global(.ithub-calendar .task-status-canceled) {
        --fc-event-bg-color: var(--card);
        --fc-event-border-color: var(--destructive);
        --fc-event-text-color: var(--foreground);
    }

    :global(.ithub-calendar .task-overdue:not(.task-status-done):not(.task-status-canceled)) {
        --fc-event-bg-color: var(--card);
        --fc-event-border-color: var(--destructive);
    }

    :global(.ithub-calendar .project-milestone) {
        --fc-event-bg-color: color-mix(in oklch, var(--accent) 35%, var(--card));
        --fc-event-border-color: var(--accent-foreground);
        --fc-event-text-color: var(--accent-foreground);
        border-style: dashed;
    }

    :global(.ithub-calendar .fc-toolbar-title) {
        font-size: 1rem;
        font-weight: 600;
    }

    @media (max-width: 640px) {
        :global(.ithub-calendar .fc-toolbar) {
            flex-direction: column;
            align-items: stretch;
            gap: 0.75rem;
        }

        :global(.ithub-calendar .fc-toolbar-chunk) {
            justify-content: space-between;
        }

        :global(.ithub-calendar .fc-toolbar-chunk:last-child) {
            justify-content: stretch;
        }

        :global(.ithub-calendar .fc-toolbar-chunk:last-child .fc-button-group),
        :global(.ithub-calendar .fc-toolbar-chunk:last-child .fc-button) {
            flex: 1;
        }

        :global(.ithub-calendar .fc-toolbar-chunk:last-child .fc-button) {
            padding-inline: 0.4rem;
        }
    }
</style>
