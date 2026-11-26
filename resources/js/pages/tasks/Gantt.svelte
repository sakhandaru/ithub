<script module lang="ts">
    import { gantt as ganttRoute } from '@/routes/workspaces/projects';
    export const layout = { breadcrumbs: [{ title: 'Gantt', href: ganttRoute(['', '']) }] };
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import ChartGantt from 'carbon-icons-svelte/lib/ChartNetwork.svelte';
    import CheckmarkFilled from 'carbon-icons-svelte/lib/CheckmarkFilled.svelte';
    import ChevronDown from 'carbon-icons-svelte/lib/ChevronDown.svelte';
    import ChevronRight from 'carbon-icons-svelte/lib/ChevronRight.svelte';
    import ChevronLeft from 'carbon-icons-svelte/lib/ChevronLeft.svelte';
    import Folder from 'carbon-icons-svelte/lib/Folder.svelte';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import ProjectContextNav from '@/components/ProjectContextNav.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { globalHeaderLabels, taskBreadcrumbs } from '@/lib/page-header';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null };
    type Module = { id: string; name: string; description: string | null };
    type Milestone = { id: string; name: string; target_date: string | null };
    type Relation = { id: string; type: string; type_label: string; related_task: { id: string; identifier: string; title: string } };
    type Task = { id: string; identifier: string; title: string; status: string; status_label: string; assignee: { id: number; name: string } | null; start_date: string | null; due_date: string | null; created_at: string; module: Module | null; milestone: Milestone | null; relations?: Relation[] };
    type Row = { id: string; kind: 'project' | 'module' | 'task' | 'milestone'; label: string; detail?: string; status?: string; statusLabel?: string; assignee?: string; start: string; end: string; progress: number; depth: number; count?: number; parent?: string; editable?: boolean; relations?: Relation[] };

    let { workspace, project, tasks, milestones, modules = [], editableTaskIds = [], taskQuery = {}, embedded = false }: { workspace: Workspace; project: Project; tasks: Task[]; milestones: Milestone[]; modules?: Module[]; editableTaskIds?: string[]; taskQuery?: Record<string, string | number | undefined>; embedded?: boolean } = $props();
    const dayMs = 86400000;
    const dateOnly = (value: string): string => value.slice(0, 10);
    const toDate = (value: string): Date => new Date(`${value}T00:00:00Z`);
    const iso = (date: Date): string => date.toISOString().slice(0, 10);
    const nextDate = (value: string): string => iso(new Date(toDate(value).getTime() + dayMs));
    const progressFor = (status: string): number => status === 'done' ? 100 : status === 'review' ? 80 : status === 'in_progress' ? 50 : 0;
    const shortDate = (value: string): string => { const date = toDate(value); return `${String(date.getUTCDate()).padStart(2, '0')}/${String(date.getUTCMonth() + 1).padStart(2, '0')}`; };
    let collapsed = $state<Record<string, boolean>>({});
    let viewMode = $state<'day' | 'week' | 'month' | 'quarter' | 'custom'>('month');
    let anchorDate = $state(dateOnly(new Date().toISOString()));
    let customStart = $state(dateOnly(new Date().toISOString()));
    let customEnd = $state(dateOnly(new Date(Date.now() + dayMs * 14).toISOString()));
    let timelineMode = $state<'task' | 'module' | 'milestone'>('task');
    let groupBy = $state<'module' | 'milestone' | 'none'>('none');
    let showDependencies = $state(true);
    let dragState = $state<{ id: string; originX: number; delta: number; moved: boolean } | null>(null);
    let suppressClick = $state(false);
    let ganttScroll = $state<HTMLDivElement>();
    let viewportWidth = $state(0);

    $effect(() => {
        timelineMode = groupBy === 'none' ? 'task' : groupBy;
    });

    $effect(() => {
        if (!ganttScroll) return;
        const scrollElement = ganttScroll;
        const updateViewportWidth = (): void => { viewportWidth = scrollElement.clientWidth; };
        const observer = new ResizeObserver(updateViewportWidth);
        observer.observe(scrollElement);
        updateViewportWidth();
        return () => observer.disconnect();
    });

    const datedTasks = $derived(tasks.filter((task) => task.due_date));
    const range = $derived.by(() => {
        if (viewMode === 'custom') return { start: toDate(customStart), end: toDate(customEnd) };
        const anchor = toDate(anchorDate);
        if (viewMode === 'day') return { start: anchor, end: anchor };
        if (viewMode === 'week') {
            const mondayOffset = (anchor.getUTCDay() + 6) % 7;
            const start = new Date(anchor.getTime() - mondayOffset * dayMs);
            return { start, end: new Date(start.getTime() + dayMs * 27) };
        }
        if (viewMode === 'quarter') {
            const quarterStartMonth = Math.floor(anchor.getUTCMonth() / 3) * 3;
            const start = new Date(Date.UTC(anchor.getUTCFullYear(), quarterStartMonth, 1));
            const end = new Date(Date.UTC(anchor.getUTCFullYear(), quarterStartMonth + 3, 0));
            return { start, end };
        }
        const start = new Date(Date.UTC(anchor.getUTCFullYear(), anchor.getUTCMonth(), 1));
        const end = new Date(Date.UTC(anchor.getUTCFullYear(), anchor.getUTCMonth() + 2, 0));
        return { start, end };
    });
    const unitDays = $derived(viewMode === 'day' || viewMode === 'week' || viewMode === 'month' || viewMode === 'custom' ? 1 : 7);
    const units = $derived.by(() => { const result: Date[] = []; for (let cursor = new Date(range.start); cursor <= range.end; cursor = new Date(cursor.getTime() + dayMs * unitDays)) result.push(cursor); return result; });
    const today = dateOnly(new Date().toISOString());
    const unitContainsToday = (unit: Date): boolean => toDate(today) >= unit && toDate(today) < new Date(unit.getTime() + dayMs * unitDays);

    function taskRow(task: Task, parent?: string): Row | null {
        if (!task.due_date) return null;
        const start = dateOnly(task.start_date ?? task.created_at);
        const end = dateOnly(task.due_date);
        return { id: task.id, kind: 'task', label: `${task.identifier} · ${task.title}`, detail: task.module?.name, status: task.status, statusLabel: task.status_label, assignee: task.assignee?.name ?? 'Belum ada PIC', start, end, progress: progressFor(task.status), depth: 2, parent, editable: editableTaskIds.includes(task.id), relations: task.relations ?? [] };
    }

    const rows = $derived.by(() => {
        const result: Row[] = [];
        const projectTasks = datedTasks.filter((task) => !task.module);
        if (timelineMode === 'module') {
            const groups = modules.map((module) => ({ id: `module-${module.id}`, label: module.name, tasks: datedTasks.filter((task) => task.module?.id === module.id) }));
            for (const group of groups.filter((item) => item.tasks.length)) {
                const starts = group.tasks.map((task) => dateOnly(task.start_date ?? task.created_at)).sort();
                const ends = group.tasks.map((task) => dateOnly(task.due_date as string)).sort();
                result.push({ id: group.id, kind: 'module', label: group.label, start: starts[0], end: ends.at(-1) as string, progress: Math.round(group.tasks.filter((task) => task.status === 'done').length / group.tasks.length * 100), depth: 0, count: group.tasks.length });
            }
        } else {
            const groups = timelineMode === 'task'
                ? modules.map((module) => ({ id: `module-${module.id}`, label: module.name, tasks: datedTasks.filter((task) => task.module?.id === module.id) }))
                : milestones.map((milestone) => ({ id: `milestone-group-${milestone.id}`, label: milestone.name, tasks: datedTasks.filter((task) => task.milestone?.id === milestone.id) }));
            for (const group of groups.filter((item) => item.tasks.length)) {
                const starts = group.tasks.map((task) => dateOnly(task.start_date ?? task.created_at)).sort();
                const ends = group.tasks.map((task) => dateOnly(task.due_date as string)).sort();
                result.push({ id: group.id, kind: 'module', label: group.label, start: starts[0], end: ends.at(-1) as string, progress: Math.round(group.tasks.filter((task) => task.status === 'done').length / group.tasks.length * 100), depth: 0, count: group.tasks.length });
                if (!collapsed[group.id]) for (const task of group.tasks) { const row = taskRow(task, group.id); if (row) result.push(row); }
            }
            const ungroupedTasks = timelineMode === 'task' ? projectTasks : datedTasks.filter((task) => !task.milestone);
            for (const task of ungroupedTasks) { const row = taskRow(task); if (row) result.push(row); }
        }
        return result;
    });

    const offset = (value: string): number => Math.max(0, Math.floor((toDate(value).getTime() - range.start.getTime()) / dayMs / unitDays));
    const width = (row: Row): number => Math.max(1, Math.ceil((Math.min(toDate(nextDate(row.end)).getTime(), range.end.getTime() + dayMs) - Math.max(toDate(row.start).getTime(), range.start.getTime())) / dayMs / unitDays));
    const left = (row: Row): number => offset(row.start);
    const taskHref = (id: string): string => toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query: { ...taskQuery, view: 'gantt', task: id } }));
    const barLabel = (row: Row): string => row.kind === 'task' ? `${row.label} · ${shortDate(row.start)} – ${shortDate(row.end)}` : `${row.label} · ${row.progress}%`;
    const rowIndex = (id: string): number => rows.findIndex((row) => row.id === id);
    const dependencyEdges = $derived.by(() => rows.flatMap((row) => {
        if (row.kind !== 'task' || !row.relations) return [];
        return row.relations.filter((relation) => relation.type === 'blocks' && rowIndex(relation.related_task.id) !== -1).map((relation) => ({
            from: row,
            to: rows[rowIndex(relation.related_task.id)],
        }));
    }));
    const nameColumnWidth = $derived(viewportWidth <= 640 ? 176 : viewportWidth <= 900 ? 224 : 288);
    const availableTimelineWidth = $derived(Math.max(0, viewportWidth - nameColumnWidth));
    const visibleUnits = $derived(viewMode === 'week' ? 7 : viewMode === 'month' ? 30 : viewMode === 'quarter' ? 13 : 1);
    const visibleUnitWidth = $derived(Math.max(28, availableTimelineWidth / visibleUnits));
    const timelineMin = $derived(Math.max(availableTimelineWidth, units.length * visibleUnitWidth));
    const timelineMilestones = $derived(milestones.filter((milestone) => milestone.target_date));
    const formatRange = (date: Date): string => date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' });
    function moveRange(direction: -1 | 1): void {
        if (viewMode === 'custom') {
            const rangeSpan = toDate(customEnd).getTime() - toDate(customStart).getTime();
            customStart = dateOnly(new Date(toDate(customStart).getTime() + rangeSpan * direction).toISOString());
            customEnd = dateOnly(new Date(toDate(customEnd).getTime() + rangeSpan * direction).toISOString());

            return;
        }
        const span = viewMode === 'day' ? new Date(toDate(anchorDate).getTime() + dayMs * direction) : viewMode === 'month' ? new Date(Date.UTC(toDate(anchorDate).getUTCFullYear(), toDate(anchorDate).getUTCMonth() + direction, 1)) : viewMode === 'quarter' ? new Date(Date.UTC(toDate(anchorDate).getUTCFullYear(), toDate(anchorDate).getUTCMonth() + direction * 3, 1)) : new Date(toDate(anchorDate).getTime() + dayMs * 7 * direction);
        anchorDate = dateOnly(span.toISOString());
    }
    function collapseAll(): void {
        const next: Record<string, boolean> = {};
        for (const row of rows) if (row.kind === 'module') next[row.id] = true;
        collapsed = next;
    }
    function fitProject(): void {
        const dates = datedTasks.flatMap((task) => [dateOnly(task.start_date ?? task.created_at), dateOnly(task.due_date as string)]);
        const milestoneDates = timelineMilestones.map((milestone) => dateOnly(milestone.target_date as string));
        const allDates = [...dates, ...milestoneDates].sort();
        if (allDates.length === 0) return;
        customStart = allDates[0];
        customEnd = allDates.at(-1) as string;
        viewMode = 'custom';
    }
    function startDrag(event: PointerEvent, row: Row): void {
        if (row.kind !== 'task' || !row.editable || event.button !== 0) return;
        dragState = { id: row.id, originX: event.clientX, delta: 0, moved: false };
    }
    function handlePointerMove(event: PointerEvent): void {
        if (!dragState) return;
        const delta = Math.round((event.clientX - dragState.originX) / visibleUnitWidth) * unitDays;
        dragState = { ...dragState, delta, moved: Math.abs(event.clientX - dragState.originX) > 4 };
    }
    function finishDrag(): void {
        if (!dragState) return;
        const current = dragState;
        dragState = null;
        if (!current.moved || current.delta === 0) return;
        const row = rows.find((item) => item.id === current.id);
        if (!row) return;
        rescheduleRow(row, current.delta);
    }
    function rescheduleRow(row: Row, delta: number): void {
        const start = dateOnly(new Date(toDate(row.start).getTime() + delta * dayMs).toISOString());
        const end = dateOnly(new Date(toDate(row.end).getTime() + delta * dayMs).toISOString());
        suppressClick = true;
        router.patch(toUrl(TaskController.update({ workspace: workspace.id, project: project.id, task: row.id })), { start_date: start, due_date: end }, { preserveScroll: true, onFinish: () => { window.setTimeout(() => { suppressClick = false; }, 0); } });
    }
    function handleBarKeydown(event: KeyboardEvent, row: Row): void {
        if (row.kind !== 'task' || !row.editable || !['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
        event.preventDefault();
        rescheduleRow(row, event.key === 'ArrowRight' ? 1 : -1);
    }
    function toggle(row: Row): void { collapsed[row.id] = !collapsed[row.id]; }
</script>

<svelte:window onpointermove={handlePointerMove} onpointerup={finishDrag} />

{#if !embedded}<AppHead title={`${project.identifier} · Gantt`} />{/if}
<div class={embedded ? 'min-w-0' : 'flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8'}>
    {#if rows.length > 0}<div class="gantt-legend" aria-label="Legenda timeline"><span><i class="legend-progress"></i>Progress</span><span><i class="legend-remaining"></i>Sisa durasi</span><span><i class="legend-today"></i>Hari ini</span><span class="legend-scale-label">Detail</span><button type="button" class="legend-scale" aria-pressed={viewMode === 'day'} onclick={() => { viewMode = 'day'; }}>Hari</button><button type="button" class="legend-scale" aria-pressed={viewMode === 'quarter'} onclick={() => { viewMode = 'quarter'; }}>Kuartal</button><button type="button" class="legend-toggle" onclick={fitProject}>Sesuaikan project</button><button type="button" class="legend-toggle" aria-pressed={showDependencies} onclick={() => { showDependencies = !showDependencies; }}>{showDependencies ? 'Sembunyikan relasi' : 'Tampilkan relasi'}</button></div>{/if}
    {#if !embedded}<PageHeader breadcrumbs={taskBreadcrumbs(page.props.unitContext?.active?.name ?? workspace.name, page.props.unitContext?.active?.id, toUrl(ProjectController.index(workspace.id)), project.name, toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), toUrl(TaskController.index({ workspace: workspace.id, project: project.id })), tasks.length, globalHeaderLabels.task, page.props.projectContext?.active?.icon)} showSidebarTrigger>{#snippet actions()}<ProjectContextNav {workspace} {project} active="gantt" taskQuery={taskQuery} />{/snippet}</PageHeader>{/if}
    {#if project.archived_at}<div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan dan Gantt bersifat read-only.</div>{/if}
    {#if rows.length === 0}<section class="flex min-h-64 items-center justify-center rounded-lg border border-dashed border-border/80 bg-card px-6 py-12 text-center"><div><ChartGantt class="mx-auto size-8 text-muted-foreground" aria-hidden="true" /><h2 class="mt-4 text-sm font-semibold">Belum ada item timeline</h2><p class="mt-1 text-sm text-muted-foreground">Tambahkan deadline pada Task atau target pada milestone.</p></div></section>{:else}
        <section class="gantt-shell overflow-hidden rounded-lg border border-border/80 bg-card" aria-label="Gantt Project">
            <div class="gantt-toolbar flex flex-wrap items-center justify-between gap-3 border-b border-border/70 px-3 py-2.5"><div class="flex min-w-0 items-center gap-2"><span class="truncate text-sm font-semibold">{project.name}</span><span class="hidden text-xs text-muted-foreground sm:inline">{formatRange(range.start)} – {formatRange(range.end)}</span></div><div class="flex flex-wrap items-center gap-1.5"><div class="flex rounded-md border p-0.5" aria-label="Kelompokkan berdasarkan"><button type="button" class={`min-h-8 rounded px-2.5 text-xs ${groupBy === 'module' ? 'bg-muted font-medium' : 'text-muted-foreground'}`} aria-pressed={groupBy === 'module'} onclick={() => { groupBy = 'module'; }}>Modul</button><button type="button" class={`min-h-8 rounded px-2.5 text-xs ${groupBy === 'milestone' ? 'bg-muted font-medium' : 'text-muted-foreground'}`} aria-pressed={groupBy === 'milestone'} onclick={() => { groupBy = 'milestone'; }}>Milestone</button><button type="button" class={`min-h-8 rounded px-2.5 text-xs ${groupBy === 'none' ? 'bg-muted font-medium' : 'text-muted-foreground'}`} aria-pressed={groupBy === 'none'} onclick={() => { groupBy = 'none'; }}>Semua</button></div><button type="button" aria-label="Periode sebelumnya" class="grid size-9 place-items-center rounded-md border text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" onclick={() => moveRange(-1)}><ChevronLeft class="size-4" /></button><button type="button" class="rounded-md border px-2.5 py-1.5 text-xs font-medium hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" onclick={() => { anchorDate = dateOnly(new Date().toISOString()); viewMode = 'week'; }}>Hari ini</button><button type="button" aria-label="Periode berikutnya" class="grid size-9 place-items-center rounded-md border text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" onclick={() => moveRange(1)}><ChevronRight class="size-4" /></button><div class="ml-1 flex rounded-md border p-0.5" aria-label="Skala Gantt"><button type="button" class={`min-h-8 rounded px-2.5 text-xs ${viewMode === 'week' ? 'bg-muted font-medium' : 'text-muted-foreground'}`} aria-pressed={viewMode === 'week'} onclick={() => { viewMode = 'week'; }}>Minggu</button><button type="button" class={`min-h-8 rounded px-2.5 text-xs ${viewMode === 'month' ? 'bg-muted font-medium' : 'text-muted-foreground'}`} aria-pressed={viewMode === 'month'} onclick={() => { viewMode = 'month'; }}>Bulan</button><button type="button" class={`min-h-8 rounded px-2.5 text-xs ${viewMode === 'custom' ? 'bg-muted font-medium' : 'text-muted-foreground'}`} aria-pressed={viewMode === 'custom'} onclick={() => { viewMode = 'custom'; }}>Kustom</button></div><button type="button" class="hidden rounded-md border px-2.5 py-1.5 text-xs hover:bg-muted sm:inline-flex" onclick={collapseAll}>Tutup semua</button>{#if viewMode === 'custom'}<label class="flex items-center gap-1 text-xs text-muted-foreground">Dari <input aria-label="Tanggal mulai timeline" type="date" bind:value={customStart} class="h-8 rounded border border-input bg-background px-1.5 text-xs" /></label><label class="flex items-center gap-1 text-xs text-muted-foreground">Sampai <input aria-label="Tanggal akhir timeline" type="date" bind:value={customEnd} class="h-8 rounded border border-input bg-background px-1.5 text-xs" /></label>{/if}</div></div>
            <div class="gantt-scroll overflow-auto" bind:this={ganttScroll}><div class="gantt-canvas relative" style={`--timeline-min: ${timelineMin}px`}>
            {#if showDependencies}<svg class="dependency-layer" aria-hidden="true" style={`left: 18rem; top: 4rem; width: ${units.length * visibleUnitWidth}px; height: ${rows.length * 60}px`} viewBox={`0 0 ${units.length * visibleUnitWidth} ${rows.length * 60}`} preserveAspectRatio="none">{#each dependencyEdges as edge}<path d={`M ${left(edge.from) * visibleUnitWidth + width(edge.from) * visibleUnitWidth} ${rowIndex(edge.from.id) * 60 + 30} C ${(left(edge.from) + width(edge.from) + 1) * visibleUnitWidth} ${rowIndex(edge.from.id) * 60 + 30}, ${(left(edge.to) - 1) * visibleUnitWidth} ${rowIndex(edge.to.id) * 60 + 30}, ${left(edge.to) * visibleUnitWidth} ${rowIndex(edge.to.id) * 60 + 30}`} fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-dasharray="4 4" marker-end="url(#dependency-arrow)" />{/each}</svg>{/if}
            <svg class="dependency-defs" aria-hidden="true" width="0" height="0"><defs><marker id="dependency-arrow" markerWidth="6" markerHeight="6" refX="5" refY="3" orient="auto"><path d="M0,0 L6,3 L0,6" fill="none" stroke="currentColor" stroke-width="1" /></marker></defs></svg>
            {#each timelineMilestones as milestone (milestone.id)}<div class="milestone-marker" aria-label={`Milestone ${milestone.name}, target ${shortDate(milestone.target_date as string)}`} style={`left: calc(18rem + ${Math.max(0, offset(dateOnly(milestone.target_date as string))) * visibleUnitWidth}px); height: ${rows.length * 60}px`}><span class="milestone-marker-label">{milestone.name}<small>{shortDate(milestone.target_date as string)}</small></span></div>{/each}
            <div class="gantt-grid" style={`--timeline-min: ${timelineMin}px`}>
                <div class="name-head">Nama</div><div class="timeline-head"><div class="month-label">{range.start.toLocaleDateString('id-ID', { month: 'long', year: 'numeric', timeZone: 'UTC' })}</div><div class="day-head" style={`grid-template-columns: repeat(${units.length}, 1fr)`}>{#each units as unit}<span class:today={unitContainsToday(unit)}>{unit.toLocaleDateString('id-ID', { weekday: viewMode === 'day' || viewMode === 'week' ? 'short' : undefined, day: 'numeric', month: viewMode !== 'week' && viewMode !== 'day' ? 'short' : undefined, timeZone: 'UTC' })}</span>{/each}</div></div>
                {#each rows as row (row.id)}
                    <div class:summary-row={row.kind !== 'task' && row.kind !== 'milestone'} class="name-cell" style={`padding-left: ${12 + row.depth * 20}px`}>{#if row.kind === 'project' || row.kind === 'module'}<button type="button" class="flex size-6 items-center justify-center rounded hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label={`${collapsed[row.id] ? 'Buka' : 'Tutup'} ${row.label}`} onclick={() => toggle(row)}>{#if collapsed[row.id]}<ChevronRight class="size-3.5" />{:else}<ChevronDown class="size-3.5" />{/if}</button><Folder class="size-4 shrink-0 text-white" aria-hidden="true" />{:else if row.kind === 'task'}<CheckmarkFilled class="size-4 shrink-0 text-white" aria-hidden="true" />{:else}<span class="size-2 shrink-0 rounded-full bg-white" aria-hidden="true"></span>{/if}<div class="min-w-0"><p class={`truncate ${row.kind === 'task' ? 'text-sm' : 'text-sm font-semibold'}`}>{row.label}</p>{#if row.kind === 'task'}<p class="truncate text-[11px] text-muted-foreground">{row.assignee} · {row.statusLabel}</p>{/if}</div>{#if row.count}<span class="text-xs text-muted-foreground">{row.count}</span>{/if}</div>
                    <div class="timeline-cell"><div class="day-grid" style={`grid-template-columns: repeat(${units.length}, 1fr)`}>{#each units as unit}<span class:today={unitContainsToday(unit)}></span>{/each}</div><Link href={suppressClick ? '#' : row.kind === 'task' ? taskHref(row.id) : ''} aria-label={barLabel(row)} title={row.kind === 'task' ? `${barLabel(row)}${row.editable ? ' · Geser untuk menjadwalkan ulang' : ''}` : barLabel(row)} class={`gantt-bar ${row.kind === 'project' ? 'project-bar' : row.kind === 'module' ? 'module-bar' : ''} status-${row.status ?? ''} ${row.editable ? 'is-editable' : ''} ${dragState?.id === row.id ? 'is-dragging' : ''}`} style={`left: ${left(row) / units.length * 100}%; width: ${width(row) / units.length * 100}%;`} onpointerdown={(event) => startDrag(event, row)} onkeydown={(event) => handleBarKeydown(event, row)}><span class="bar-progress" style={`width: ${row.progress}%`}></span><span class="bar-content"><span class="bar-label">{row.label}</span><span class="bar-meta">{row.kind === 'task' ? `${row.statusLabel} · ${row.assignee}` : `${row.progress}%`}</span></span></Link></div>
                {/each}
            </div></div></div>
        </section>
    {/if}
</div>

<style>
    .gantt-shell { --gantt-blue: #60a5fa; --gantt-blue-strong: #3b82f6; --gantt-blue-light: #bfdbfe; }
    .gantt-canvas { width: 100%; min-width: calc(18rem + var(--timeline-min, 0px)); }
    .gantt-grid { display: grid; width: 100%; grid-template-columns: 18rem minmax(0, 1fr); min-width: calc(18rem + var(--timeline-min, 0px)); grid-auto-rows: 3.75rem; }
    .dependency-layer { position: absolute; z-index: 1; overflow: visible; color: color-mix(in oklch, var(--primary) 55%, var(--muted-foreground)); pointer-events: none; }
    .dependency-defs { position: absolute; }
    .name-head, .timeline-head { position: sticky; top: 0; z-index: 2; background: var(--card); border-bottom: 1px solid var(--border); }
    .name-head { grid-column: 1; padding: 1rem; font-size: .75rem; font-weight: 600; }
    .timeline-head { grid-column: 2; min-width: var(--timeline-min, 0px); }
    .month-label { height: 2rem; padding: .55rem .75rem; font-size: .7rem; color: var(--muted-foreground); border-bottom: 1px solid var(--border); }
    .day-head, .day-grid { display: grid; }
    .day-head { height: 2rem; }
    .day-head span { padding: .55rem .5rem; font-size: .65rem; color: var(--muted-foreground); border-right: 1px solid color-mix(in oklch, var(--border) 55%, transparent); text-align: center; }
    .name-cell, .timeline-cell { border-bottom: 1px solid color-mix(in oklch, var(--border) 70%, transparent); }
    .name-head, .name-cell { position: sticky; left: 0; z-index: 3; background: var(--card); }
    .name-head { z-index: 4; }
    .name-cell { display: flex; align-items: center; gap: .35rem; padding-right: .75rem; min-width: 0; }
    .timeline-cell { position: relative; overflow: hidden; }
    .day-grid { position: absolute; inset: 0; }
    .day-grid span { border-right: 1px solid color-mix(in oklch, var(--border) 45%, transparent); }
    .day-grid span.today, .day-head span.today { background: color-mix(in oklch, var(--gantt-blue) 12%, transparent); }
    .summary-row { background: color-mix(in oklch, var(--muted) 22%, transparent); }
    :global(.gantt-bar) { position: absolute; z-index: 2; top: 1rem; height: 1.75rem; overflow: hidden; border: 1px solid var(--gantt-blue-light); border-radius: .3rem; background: var(--gantt-blue); color: #0f172a; font-size: .68rem; line-height: 1; text-decoration: none; transition: background-color .15s ease, border-color .15s ease, transform .15s ease; }
    :global(.gantt-bar:hover) { background: #93c5fd; border-color: #dbeafe; }
    :global(.gantt-bar.is-editable) { cursor: grab; }
    :global(.gantt-bar.is-dragging) { cursor: grabbing; opacity: .8; transform: translateY(-1px); }
    .bar-progress { position: absolute; inset: 0 auto 0 0; background: rgb(0 0 0 / .12); }
    .bar-content { position: relative; display: flex; min-width: 0; align-items: center; gap: .45rem; height: 100%; padding: 0 .45rem; }
    .bar-label { min-width: 0; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; font-weight: 600; }
    .bar-meta { display: none; }
    :global(.gantt-bar.project-bar) { background: #60a5fa; color: #0f172a; border-color: var(--gantt-blue-light); }
    :global(.gantt-bar.project-bar .bar-progress) { background: rgb(0 0 0 / .12); }
    :global(.gantt-bar.module-bar) { background: #60a5fa; color: #0f172a; border-color: var(--gantt-blue-light); }
    :global(.gantt-bar.module-bar .bar-progress) { background: rgb(0 0 0 / .12); }
    :global(.project-bar .bar-label), :global(.module-bar .bar-label) { font-weight: 600; }
    :global(.gantt-bar.status-backlog), :global(.gantt-bar.status-todo), :global(.gantt-bar.status-in_progress), :global(.gantt-bar.status-review), :global(.gantt-bar.status-done), :global(.gantt-bar.status-blocked), :global(.gantt-bar.status-canceled) { background: #60a5fa; border-color: var(--gantt-blue-light); color: #0f172a; }
    :global(.gantt-bar.status-backlog .bar-progress), :global(.gantt-bar.status-todo .bar-progress), :global(.gantt-bar.status-in_progress .bar-progress), :global(.gantt-bar.status-review .bar-progress), :global(.gantt-bar.status-done .bar-progress), :global(.gantt-bar.status-blocked .bar-progress), :global(.gantt-bar.status-canceled .bar-progress) { background: rgb(0 0 0 / .12); }
    .milestone-marker { position: absolute; top: 4rem; z-index: 1; width: 1px; border-left: 1px dashed color-mix(in oklch, var(--gantt-blue-light) 78%, var(--border)); pointer-events: none; }
    .milestone-marker::before { position: absolute; top: -.35rem; left: -.3rem; width: .65rem; height: .65rem; transform: rotate(45deg); border: 1px solid var(--gantt-blue-light); background: var(--card); box-shadow: 0 0 10px color-mix(in oklch, var(--gantt-blue) 40%, transparent); content: ''; }
    .milestone-marker-label { position: absolute; top: -.2rem; left: .65rem; display: grid; gap: .1rem; min-width: max-content; padding: .2rem .35rem; border: 1px solid color-mix(in oklch, var(--gantt-blue-light) 38%, var(--border)); border-radius: .25rem; background: color-mix(in oklch, var(--gantt-blue) 12%, var(--card)); color: var(--foreground); font-size: .65rem; font-weight: 600; box-shadow: 0 0 12px color-mix(in oklch, var(--gantt-blue) 14%, transparent); }
    .milestone-marker-label small { color: var(--muted-foreground); font-size: .6rem; font-weight: 500; }
    .gantt-legend { display: flex; flex-wrap: wrap; gap: .75rem; padding: .5rem .75rem; border-bottom: 1px solid var(--border); color: var(--muted-foreground); font-size: .65rem; }
    .gantt-legend span { display: inline-flex; align-items: center; gap: .35rem; }
    .legend-toggle { margin-left: auto; border: 0; background: transparent; color: var(--muted-foreground); font-size: .65rem; }
    .legend-toggle:hover { color: var(--foreground); text-decoration: underline; }
    .legend-scale-label { margin-left: .25rem; color: var(--foreground); font-weight: 600; }
    .legend-scale { border: 1px solid var(--border); border-radius: .25rem; padding: .2rem .4rem; background: transparent; color: var(--muted-foreground); font-size: .65rem; }
    .legend-scale[aria-pressed="true"] { background: color-mix(in oklch, var(--gantt-blue) 16%, transparent); color: var(--foreground); }
    .gantt-legend i { display: inline-block; width: .55rem; height: .55rem; border-radius: .15rem; background: #60a5fa; }
    .gantt-legend .legend-progress { background: rgb(0 0 0 / .12); }
    .gantt-legend .legend-today { background: color-mix(in oklch, var(--gantt-blue) 12%, transparent); border: 1px solid color-mix(in oklch, var(--gantt-blue) 35%, var(--border)); }
    @media (max-width: 900px) { .gantt-grid { grid-template-columns: 14rem minmax(var(--timeline-min, 0px), 1fr); min-width: calc(14rem + var(--timeline-min, 0px)); } .name-head, .name-cell { width: 14rem; } }
    @media (max-width: 640px) { .gantt-grid { grid-template-columns: 11rem minmax(var(--timeline-min, 0px), 1fr); min-width: calc(11rem + var(--timeline-min, 0px)); } .name-head, .name-cell { width: 11rem; } .bar-meta { display: none; } }
</style>
