<script module lang="ts">
    import { index } from '@/routes/workspaces/projects';

    export const layout = {
        breadcrumbs: [{ title: 'Project', href: index('') }],
    };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import { createPaginatedRowModel, createTable, createTableState, rowPaginationFeature, tableFeatures } from '@tanstack/svelte-table';
    import type { ColumnDef } from '@tanstack/svelte-table';
    import ArrowUpRight from 'carbon-icons-svelte/lib/ArrowUpRight.svelte';
    import Building2 from 'carbon-icons-svelte/lib/Building.svelte';
    import CalendarDays from 'carbon-icons-svelte/lib/Calendar.svelte';
    import Check from 'carbon-icons-svelte/lib/Checkmark.svelte';
    import CheckCircle2 from 'carbon-icons-svelte/lib/CheckmarkFilled.svelte';
    import ChevronDown from 'carbon-icons-svelte/lib/ChevronDown.svelte';
    import ChevronUp from 'carbon-icons-svelte/lib/ChevronUp.svelte';
    import ChevronLeft from 'carbon-icons-svelte/lib/ChevronLeft.svelte';
    import ChevronRight from 'carbon-icons-svelte/lib/ChevronRight.svelte';
    import CircleAlert from 'carbon-icons-svelte/lib/WarningAlt.svelte';
    import Archive from 'carbon-icons-svelte/lib/Archive.svelte';
    import Folder from 'carbon-icons-svelte/lib/Folder.svelte';
    import FolderKanban from 'carbon-icons-svelte/lib/FolderTree.svelte';
    import Eye from 'carbon-icons-svelte/lib/View.svelte';
    import ListFilter from 'carbon-icons-svelte/lib/Filter.svelte';
    import Plus from 'carbon-icons-svelte/lib/Add.svelte';
    import Search from 'carbon-icons-svelte/lib/Search.svelte';
    import Reset from 'carbon-icons-svelte/lib/Reset.svelte';
    import UserRound from 'carbon-icons-svelte/lib/UserAvatarFilled.svelte';
    import ArrowUpDown from 'carbon-icons-svelte/lib/ArrowsVertical.svelte';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import ProjectProgressSegments from '@/components/ProjectProgressSegments.svelte';
    import ProjectArchiveController from '@/actions/App/Http/Controllers/ProjectArchiveController';
    import AppHead from '@/components/AppHead.svelte';
    import CreateProjectDialog from '@/components/CreateProjectDialog.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import PageBreadcrumb from '@/components/PageBreadcrumb.svelte';
    import DataTable from '@/components/DataTable.svelte';
    import { Button } from '@/components/ui/button';
    import { Badge } from '@/components/ui/badge';
    import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import { toUrl } from '@/lib/utils';
    import type { ProjectSummary } from '@/types';
    import { globalHeaderLabels, projectsIndexBreadcrumbs } from '@/lib/page-header';

    type Workspace = { id: string; name: string };
    type Unit = { id: string; name: string } | null;
    type SortKey = 'name' | 'status' | 'tasks' | 'lead' | 'unit' | 'visibility' | 'target_date';
    type SortDirection = 'asc' | 'desc';

    let {
        workspace,
        unit,
        projects,
        showArchived,
        createProjectOpen: initialCreateProjectOpen,
        units: formUnits,
        members: formMembers,
        statuses: formStatuses,
        visibilities: formVisibilities,
    }: {
        workspace: Workspace;
        unit: Unit;
        projects: ProjectSummary[];
        showArchived: boolean;
        createProjectOpen: boolean;
        units: { id: string; name: string }[];
        members: { id: number; name: string; email: string }[];
        statuses: { value: string; label: string }[];
        visibilities: { value: string; label: string }[];
    } = $props();
    let createProjectOpen = $state(false);

    $effect(() => {
        createProjectOpen = initialCreateProjectOpen;
    });
    let search = $state('');
    let projectFilter = $state('');
    let statusFilter = $state('all');
    let progressFilter = $state('all');
    let leadFilter = $state('all');
    let leadFilterSearch = $state('');
    let unitFilter = $state('all');
    let unitFilterSearch = $state('');
    let visibilityFilter = $state('all');
    let targetFilter = $state('all');
    let sortKey = $state<SortKey>('name');
    let sortDirection = $state<SortDirection>('asc');
    let expandedProgressProjectId = $state<string | null>(null);

    const projectFeatures = tableFeatures({
        rowPaginationFeature,
        paginatedRowModel: createPaginatedRowModel(),
    });
    const projectColumns: ColumnDef<typeof projectFeatures, ProjectSummary>[] = [
        { accessorKey: 'name', header: 'Project' },
        { accessorKey: 'status_label', header: 'Status' },
        { accessorKey: 'tasks_count', header: 'Progress Task' },
        { accessorFn: (project) => project.project_lead?.name ?? 'Belum ditentukan', id: 'lead', header: 'Lead' },
        { accessorFn: (project) => project.unit?.name ?? 'Belum ditentukan', id: 'unit', header: 'Unit' },
        { accessorKey: 'visibility_label', header: 'Visibility' },
        { accessorKey: 'target_date', header: 'Target' },
        { id: 'actions', header: 'Aksi' },
    ];
    const projectHeaderIcons = {
        name: FolderKanban,
        status_label: CircleAlert,
        tasks_count: CheckCircle2,
        lead: UserRound,
        unit: Building2,
        visibility_label: Eye,
        target_date: CalendarDays,
        actions: ArrowUpRight,
    };
    const sortKeyByColumn: Record<string, SortKey> = {
        name: 'name',
        status_label: 'status',
        tasks_count: 'tasks',
        lead: 'lead',
        unit: 'unit',
        visibility_label: 'visibility',
        target_date: 'target_date',
    };
    const [projectPagination, setProjectPagination] = createTableState({ pageIndex: 0, pageSize: 10 });

    const statuses = $derived(Array.from(new Set(projects.map((project) => project.status))));
    const visibilities = $derived(Array.from(new Set(projects.map((project) => project.visibility))));
    const leads = $derived(Array.from(new Set(projects.map((project) => project.project_lead?.name).filter((name): name is string => Boolean(name)))));
    const units = $derived(Array.from(new Set(projects.map((project) => project.unit?.name).filter((name): name is string => Boolean(name)))));
    const filteredLeads = $derived(leads.filter((lead) => lead.toLowerCase().includes(leadFilterSearch.trim().toLowerCase())));
    const filteredUnits = $derived(units.filter((projectUnit) => projectUnit.toLowerCase().includes(unitFilterSearch.trim().toLowerCase())));
    const filteredProjects = $derived.by(() => {
        const term = search.trim().toLowerCase();
        const projectTerm = projectFilter.trim().toLowerCase();
        const result = projects.filter((project) => {
            const matchesSearch = !term || [project.name, project.identifier, project.project_lead?.name, project.unit?.name]
                .filter(Boolean)
                .some((value) => value!.toLowerCase().includes(term));
            const matchesProject = !projectTerm || [project.name, project.identifier].some((value) => value.toLowerCase().includes(projectTerm));
            const matchesStatus = statusFilter === 'all' || project.status === statusFilter;
            const matchesProgress = progressFilter === 'all'
                || (progressFilter === 'complete' && progress(project) === 100)
                || (progressFilter === 'incomplete' && progress(project) < 100)
                || (progressFilter === 'attention' && (project.overdue_tasks_count > 0 || project.blocked_tasks_count > 0));
            const matchesLead = leadFilter === 'all' || project.project_lead?.name === leadFilter;
            const matchesUnit = unitFilter === 'all' || project.unit?.name === unitFilter;
            const matchesVisibility = visibilityFilter === 'all' || project.visibility === visibilityFilter;
            const matchesTarget = targetFilter === 'all'
                || (targetFilter === 'set' && project.target_date !== null)
                || (targetFilter === 'none' && project.target_date === null);

            return matchesSearch && matchesProject && matchesStatus && matchesProgress && matchesLead && matchesUnit && matchesVisibility && matchesTarget;
        });

        return result.sort((left, right) => {
            const leftValue = sortKey === 'name' ? left.name
                : sortKey === 'status' ? left.status_label
                    : sortKey === 'tasks' ? left.tasks_count
                        : sortKey === 'lead' ? left.project_lead?.name ?? ''
                            : sortKey === 'unit' ? left.unit?.name ?? ''
                                : sortKey === 'visibility' ? left.visibility_label
                                    : left.target_date ?? '';
            const rightValue = sortKey === 'name' ? right.name
                : sortKey === 'status' ? right.status_label
                    : sortKey === 'tasks' ? right.tasks_count
                        : sortKey === 'lead' ? right.project_lead?.name ?? ''
                            : sortKey === 'unit' ? right.unit?.name ?? ''
                                : sortKey === 'visibility' ? right.visibility_label
                                    : right.target_date ?? '';
            const comparison = typeof leftValue === 'number' && typeof rightValue === 'number'
                ? leftValue - rightValue
                : String(leftValue).localeCompare(String(rightValue));

            return sortDirection === 'asc' ? comparison : -comparison;
        });
    });
    const projectTable = createTable({
        features: projectFeatures,
        columns: projectColumns,
        state: {
            get pagination() {
                return projectPagination();
            },
        },
        onPaginationChange: setProjectPagination,
        get data() {
            return filteredProjects;
        },
        getRowId: (project) => project.id,
    });
    const progress = (project: ProjectSummary): number => project.tasks_count > 0
        ? Math.round((project.completed_tasks_count / project.tasks_count) * 100)
        : 0;
    const statusClass = (status: string): string => ({
        active: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300',
        draft: 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900 dark:bg-violet-950/40 dark:text-violet-300',
        archived: 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300',
    }[status] ?? 'border-border bg-muted text-muted-foreground');
    const visibilityClass = (visibility: string): string => ({
        unit: 'text-blue-700 dark:text-blue-300',
        workspace: 'text-emerald-700 dark:text-emerald-300',
        private: 'text-violet-700 dark:text-violet-300',
    }[visibility] ?? 'text-muted-foreground');

    function resetFilters(): void {
        search = '';
        projectFilter = '';
        statusFilter = 'all';
        progressFilter = 'all';
        leadFilter = 'all';
        leadFilterSearch = '';
        unitFilter = 'all';
        unitFilterSearch = '';
        visibilityFilter = 'all';
        targetFilter = 'all';
        sortKey = 'name';
        sortDirection = 'asc';
    }

    function sortColumn(key: SortKey): void {
        if (sortKey === key) {
            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            return;
        }

        sortKey = key;
        sortDirection = 'asc';
    }

    function filterActive(columnId: string): boolean {
        return columnId === 'name' ? projectFilter !== ''
            : columnId === 'status_label' ? statusFilter !== 'all'
                : columnId === 'tasks_count' ? progressFilter !== 'all'
                    : columnId === 'lead' ? leadFilter !== 'all'
                        : columnId === 'unit' ? unitFilter !== 'all'
                            : columnId === 'visibility_label' ? visibilityFilter !== 'all'
                                : columnId === 'target_date' && targetFilter !== 'all';
    }

    function toggleProgress(projectId: string): void {
        expandedProgressProjectId = expandedProgressProjectId === projectId ? null : projectId;
    }
</script>

<AppHead title="Project" />

<div class="flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader>
        {#snippet leading()}
            <PageBreadcrumb items={projectsIndexBreadcrumbs(unit?.name ?? globalHeaderLabels.unit, unit?.id, showArchived, projects.length)} showSidebarTrigger />
        {/snippet}
        {#snippet actions()}
            <div class="flex min-h-7 flex-wrap items-center justify-end gap-1.5">
                <label class="relative h-7 w-52 sm:w-60">
                    <span class="sr-only">Cari Project</span>
                    <Search class="pointer-events-none absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                    <input bind:value={search} type="search" placeholder="Cari Project..." class="h-7 w-full rounded-md border border-border/80 bg-background pl-8 pr-8 text-xs outline-none transition-colors placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-ring/20" />
                    <button type="button" class="absolute right-1 top-1/2 inline-flex size-6 -translate-y-1/2 items-center justify-center rounded-sm text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label="Reset filter dan pencarian" title="Reset filter dan pencarian" onclick={resetFilters}>
                        <Reset class="size-3.5" aria-hidden="true" />
                    </button>
                </label>
                <Button variant="outline" class="h-7 shrink-0 gap-1.5 border-border/80 bg-background px-2.5 text-xs text-muted-foreground" asChild>
                    {#snippet children(props)}
                        <Link href={toUrl(ProjectController.index(workspace.id, { query: showArchived ? {} : { archived: 1 } }))} {...props}>
                            {#if showArchived}<Folder class="size-3.5" aria-hidden="true" />{:else}<Archive class="size-3.5" aria-hidden="true" />{/if}
                            <span>{showArchived ? 'Project aktif' : 'Project arsip'}</span>
                        </Link>
                    {/snippet}
                </Button>
                {#if !showArchived}
                    <Button variant="outline" class="h-7 shrink-0 gap-1.5 border-border/80 bg-background px-2.5 text-xs text-muted-foreground" onclick={() => { createProjectOpen = true; }}>
                        <Plus class="size-3.5" aria-hidden="true" />
                        <span>Tambah Project</span>
                    </Button>
                {/if}
            </div>
        {/snippet}
    </PageHeader>

    {#if projects.length === 0}
        <section class="flex min-h-64 items-center justify-center rounded-lg border border-dashed border-border/80 bg-card px-6 py-12 text-center">
            <div class="max-w-md">
                <FolderKanban class="mx-auto size-8 text-muted-foreground" aria-hidden="true" />
                <h2 class="mt-4 text-sm font-semibold">{showArchived ? 'Belum ada Project arsip' : 'Belum ada Project'}</h2>
                <p class="mt-1 text-sm leading-6 text-muted-foreground">{showArchived ? 'Project yang diarsipkan akan muncul di sini.' : 'Buat Project pertama untuk mulai mengelompokkan Task.'}</p>
            </div>
        </section>
    {:else}
        <section class="overflow-hidden rounded-lg border border-border/70 bg-card" aria-label="Daftar Project">
            {#if filteredProjects.length === 0}
                <div class="flex min-h-48 flex-col items-center justify-center px-6 py-12 text-center">
                    <Search class="size-6 text-muted-foreground" aria-hidden="true" />
                    <p class="mt-3 text-sm font-medium">Project tidak ditemukan</p>
                    <p class="mt-1 text-sm text-muted-foreground">Coba ubah kata kunci atau filter.</p>
                    <button type="button" class="mt-3 text-sm font-medium text-primary hover:underline" onclick={resetFilters}>Reset filter</button>
                </div>
            {:else}
                <DataTable table={projectTable} caption="Daftar Project" headerIcons={projectHeaderIcons} wrapperClass="overflow-hidden" tableClass="w-full min-w-[72rem] border-collapse text-left text-xs" headerClass="border-b border-border/70 bg-muted/25 px-3 py-2.5 text-xs font-medium text-muted-foreground first:border-l-0 border-l border-border/60">
                    {#snippet headerActions(columnId)}
                        {@const columnSortKey = sortKeyByColumn[columnId]}
                        {#if columnSortKey}
                            <button type="button" class="inline-flex size-5 items-center justify-center rounded-sm text-muted-foreground hover:bg-muted hover:text-foreground {sortKey === columnSortKey ? 'text-foreground' : ''}" aria-label={`Urutkan ${columnId}`} title={sortKey === columnSortKey ? `Urutan ${sortDirection === 'asc' ? 'naik' : 'turun'}` : 'Urutkan'} onclick={(event) => { event.stopPropagation(); sortColumn(columnSortKey); }}>
                                {#if sortKey === columnSortKey && sortDirection === 'desc'}<ChevronDown class="size-3" aria-hidden="true" />{:else}<ChevronUp class="size-3" aria-hidden="true" />{/if}
                            </button>
                        {/if}
                        {#if columnId !== 'actions'}
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    {#snippet children(props)}
                                        <button {...props} type="button" class="inline-flex size-5 items-center justify-center rounded-sm text-muted-foreground hover:bg-muted hover:text-foreground {filterActive(columnId) ? 'text-foreground' : ''}" aria-label={`Filter ${columnId}`} title="Filter">
                                            <ListFilter class="size-3" aria-hidden="true" />
                                        </button>
                                    {/snippet}
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="start" class="w-52 p-1">
                                    {#if columnId === 'name'}
                                        <label class="grid gap-1 text-xs font-medium">Cari Project<input bind:value={projectFilter} class="h-8 rounded-md border border-input bg-background px-2 text-xs font-normal outline-none focus:border-primary focus:ring-2 focus:ring-ring/20" placeholder="Nama atau identifier" /></label>
                                    {:else if columnId === 'status_label'}
                                        <DropdownMenuItem class={statusFilter === 'all' ? 'bg-muted' : ''} onclick={() => statusFilter = 'all'}>{#if statusFilter === 'all'}<Check class="size-3.5" />{/if}Semua status</DropdownMenuItem>
                                        {#each statuses as status}<DropdownMenuItem class={statusFilter === status ? 'bg-muted' : ''} onclick={() => statusFilter = status}>{#if statusFilter === status}<Check class="size-3.5" />{/if}{projects.find((project) => project.status === status)?.status_label ?? status}</DropdownMenuItem>{/each}
                                    {:else if columnId === 'tasks_count'}
                                        <DropdownMenuItem class={progressFilter === 'all' ? 'bg-muted' : ''} onclick={() => progressFilter = 'all'}>{#if progressFilter === 'all'}<Check class="size-3.5" />{/if}Semua progress</DropdownMenuItem>
                                        <DropdownMenuItem class={progressFilter === 'complete' ? 'bg-muted' : ''} onclick={() => progressFilter = 'complete'}>{#if progressFilter === 'complete'}<Check class="size-3.5" />{/if}Selesai 100%</DropdownMenuItem>
                                        <DropdownMenuItem class={progressFilter === 'incomplete' ? 'bg-muted' : ''} onclick={() => progressFilter = 'incomplete'}>{#if progressFilter === 'incomplete'}<Check class="size-3.5" />{/if}Belum selesai</DropdownMenuItem>
                                        <DropdownMenuItem class={progressFilter === 'attention' ? 'bg-muted' : ''} onclick={() => progressFilter = 'attention'}>{#if progressFilter === 'attention'}<Check class="size-3.5" />{/if}Perlu perhatian</DropdownMenuItem>
                                    {:else if columnId === 'lead'}
                                        {#if leads.length > 5}<input bind:value={leadFilterSearch} class="mb-1 h-8 w-full rounded-md border border-input bg-background px-2 text-xs outline-none focus:border-primary focus:ring-2 focus:ring-ring/20" placeholder="Cari Lead..." onclick={(event) => event.stopPropagation()} />{/if}
                                        <DropdownMenuItem class={leadFilter === 'all' ? 'bg-muted' : ''} onclick={() => leadFilter = 'all'}>{#if leadFilter === 'all'}<Check class="size-3.5" />{/if}Semua Lead</DropdownMenuItem>
                                        {#each filteredLeads as lead}<DropdownMenuItem class={leadFilter === lead ? 'bg-muted' : ''} onclick={() => leadFilter = lead}>{#if leadFilter === lead}<Check class="size-3.5" />{/if}{lead}</DropdownMenuItem>{/each}
                                    {:else if columnId === 'unit'}
                                        {#if units.length > 5}<input bind:value={unitFilterSearch} class="mb-1 h-8 w-full rounded-md border border-input bg-background px-2 text-xs outline-none focus:border-primary focus:ring-2 focus:ring-ring/20" placeholder="Cari Unit..." onclick={(event) => event.stopPropagation()} />{/if}
                                        <DropdownMenuItem class={unitFilter === 'all' ? 'bg-muted' : ''} onclick={() => unitFilter = 'all'}>{#if unitFilter === 'all'}<Check class="size-3.5" />{/if}Semua Unit</DropdownMenuItem>
                                        {#each filteredUnits as projectUnit}<DropdownMenuItem class={unitFilter === projectUnit ? 'bg-muted' : ''} onclick={() => unitFilter = projectUnit}>{#if unitFilter === projectUnit}<Check class="size-3.5" />{/if}{projectUnit}</DropdownMenuItem>{/each}
                                    {:else if columnId === 'visibility_label'}
                                        <DropdownMenuItem class={visibilityFilter === 'all' ? 'bg-muted' : ''} onclick={() => visibilityFilter = 'all'}>{#if visibilityFilter === 'all'}<Check class="size-3.5" />{/if}Semua visibility</DropdownMenuItem>
                                        {#each visibilities as visibility}<DropdownMenuItem class={visibilityFilter === visibility ? 'bg-muted' : ''} onclick={() => visibilityFilter = visibility}>{#if visibilityFilter === visibility}<Check class="size-3.5" />{/if}{projects.find((project) => project.visibility === visibility)?.visibility_label ?? visibility}</DropdownMenuItem>{/each}
                                    {:else if columnId === 'target_date'}
                                        <DropdownMenuItem class={targetFilter === 'all' ? 'bg-muted' : ''} onclick={() => targetFilter = 'all'}>{#if targetFilter === 'all'}<Check class="size-3.5" />{/if}Semua target</DropdownMenuItem>
                                        <DropdownMenuItem class={targetFilter === 'set' ? 'bg-muted' : ''} onclick={() => targetFilter = 'set'}>{#if targetFilter === 'set'}<Check class="size-3.5" />{/if}Ada target</DropdownMenuItem>
                                        <DropdownMenuItem class={targetFilter === 'none' ? 'bg-muted' : ''} onclick={() => targetFilter = 'none'}>{#if targetFilter === 'none'}<Check class="size-3.5" />{/if}Tanpa target</DropdownMenuItem>
                                    {/if}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        {/if}
                    {/snippet}
                    {#snippet children(row)}
                        {@const project = row.original}
                        {@const completion = progress(project)}
                        <tr class="group border-b border-border/60 transition-colors last:border-b-0 hover:bg-muted/25">
                            <td class="px-3 py-2.5"><Link href={toUrl(ProjectController.show({ workspace: workspace.id, project: project.id }))} class="group/link flex min-h-8 min-w-56 items-center gap-2 rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><FolderKanban class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" /><span class="min-w-0"><span class="block truncate text-sm font-medium group-hover/link:text-primary group-hover/link:underline">{project.name}</span><span class="block font-mono text-xs text-muted-foreground">{project.identifier}</span></span></Link></td>
                            <td class="border-l border-border/60 px-3 py-2.5"><Badge variant="outline" class={`min-h-7 rounded-full px-2.5 py-0.5 text-xs leading-4 ${statusClass(project.status)}`}><span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>{project.status_label}</Badge></td>
                            <td class="min-w-60 border-l border-border/60 px-3 py-2.5">
                                {#if expandedProgressProjectId === project.id}
                                    <button type="button" class="inline-flex items-center gap-3 text-left text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-expanded="true" aria-label={`Tutup detail progress ${project.name}`} onclick={() => toggleProgress(project.id)}>
                                        <span class="font-medium">{project.completed_tasks_count}/{project.tasks_count}</span>
                                        {#if project.overdue_tasks_count > 0}<span class="inline-flex items-center gap-1 text-red-600 dark:text-red-400"><CircleAlert class="size-3.5" aria-hidden="true" />{project.overdue_tasks_count} terlambat</span>{/if}
                                        {#if project.blocked_tasks_count > 0}<span class="inline-flex items-center gap-1 text-orange-600 dark:text-orange-400"><CircleAlert class="size-3.5" aria-hidden="true" />{project.blocked_tasks_count} blocked</span>{/if}
                                    </button>
                                {:else}
                                    <button type="button" class="inline-flex items-center gap-2 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-expanded="false" aria-label={`Lihat progress ${project.name}`} onclick={() => toggleProgress(project.id)}>
                                        <ProjectProgressSegments completed={project.completed_tasks_count} total={project.tasks_count} />
                                        <span class="w-10 shrink-0 text-right text-sm font-semibold text-muted-foreground">{completion}%</span>
                                    </button>
                                {/if}
                            </td>
                            <td class="border-l border-border/60 px-3 py-2.5">
                                {#if project.project_lead}
                                    <span class="inline-flex min-h-7 max-w-44 items-center gap-1.5 rounded-full border border-border/70 px-2 py-0.5 text-xs leading-4 text-muted-foreground">
                                        <UserAvatar user={project.project_lead} size="size-4" label={false} />
                                        <span class="truncate">{project.project_lead.name}</span>
                                    </span>
                                {:else}
                                    <span class="text-[11px] text-muted-foreground">Belum ditentukan</span>
                                {/if}
                            </td>
                            <td class="border-l border-border/60 px-3 py-2.5"><span class="inline-flex min-h-7 max-w-44 items-center rounded-full border border-border/70 px-2.5 py-0.5 text-xs leading-4 text-muted-foreground"><span class="truncate">{project.unit?.name ?? '—'}</span></span></td>
                            <td class="border-l border-border/60 px-3 py-2.5"><span class="inline-flex min-h-7 items-center gap-1.5 rounded-full border border-border/70 px-2.5 py-0.5 text-xs font-medium leading-4 {visibilityClass(project.visibility)}"><span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>{project.visibility_label}</span></td>
                            <td class="border-l border-border/60 px-3 py-2.5 text-muted-foreground"><div class="flex items-center gap-1.5 whitespace-nowrap text-xs"><CalendarDays class="size-3.5" aria-hidden="true" />{project.target_date ?? 'Tanpa target'}</div></td>
                            <td class="border-l border-border/60 px-3 py-2.5 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <Link href={toUrl(ProjectController.show({ workspace: workspace.id, project: project.id }))} class="inline-flex size-7 items-center justify-center rounded-full border border-border/70 text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label={`Buka ${project.name}`} title="Buka Project"><ArrowUpRight class="size-3.5" aria-hidden="true" /></Link>
                                    {#if showArchived}
                                        <Form {...ProjectArchiveController.restore.form({ workspace: workspace.id, project: project.id })} options={{ preserveScroll: true }}>
                                            {#snippet children({ processing })}
                                                <button type="submit" class="inline-flex size-7 items-center justify-center rounded-full border border-border/70 text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50" aria-label={`Pulihkan ${project.name}`} title="Pulihkan Project" disabled={processing}><Reset class="size-3.5" aria-hidden="true" /></button>
                                            {/snippet}
                                        </Form>
                                    {/if}
                                </div>
                            </td>
                        </tr>
                    {/snippet}
                    {#snippet footer()}
                        {@const pagination = projectPagination()}
                        {@const firstProject = filteredProjects.length === 0 ? 0 : pagination.pageIndex * pagination.pageSize + 1}
                        {@const lastProject = Math.min((pagination.pageIndex + 1) * pagination.pageSize, filteredProjects.length)}
                        <div class="flex flex-col gap-2 border-t border-border/70 px-3 py-2 text-[11px] text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                            <span>Menampilkan {firstProject}–{lastProject} dari {filteredProjects.length} Project</span>
                            <div class="flex items-center gap-2">
                                <span class="hidden items-center gap-1.5 sm:inline-flex"><CheckCircle2 class="size-3.5" aria-hidden="true" /> Data diperbarui dari Project</span>
                                <Select value={String(pagination.pageSize)} onValueChange={(value) => projectTable.setPageSize(Number(value))}>
                                    <SelectTrigger class="h-8 min-w-[118px] px-2 text-xs" aria-label="Project per halaman">
                                        {pagination.pageSize} / halaman
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="10">10 / halaman</SelectItem>
                                        <SelectItem value="25">25 / halaman</SelectItem>
                                        <SelectItem value="50">50 / halaman</SelectItem>
                                    </SelectContent>
                                </Select>
                                <span class="whitespace-nowrap">{pagination.pageIndex + 1} / {projectTable.getPageCount()}</span>
                                <button type="button" class="inline-flex size-8 items-center justify-center rounded-md border border-border/80 hover:bg-muted disabled:pointer-events-none disabled:opacity-40" aria-label="Halaman sebelumnya" disabled={!projectTable.getCanPreviousPage()} onclick={() => projectTable.previousPage()}>
                                    <ChevronLeft class="size-4" aria-hidden="true" />
                                </button>
                                <button type="button" class="inline-flex size-8 items-center justify-center rounded-md border border-border/80 hover:bg-muted disabled:pointer-events-none disabled:opacity-40" aria-label="Halaman berikutnya" disabled={!projectTable.getCanNextPage()} onclick={() => projectTable.nextPage()}>
                                    <ChevronRight class="size-4" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                    {/snippet}
                </DataTable>
            {/if}
        </section>
    {/if}
</div>

<CreateProjectDialog
    bind:open={createProjectOpen}
    {workspace}
    units={formUnits}
    members={formMembers}
    statuses={formStatuses}
    visibilities={formVisibilities}
/>
