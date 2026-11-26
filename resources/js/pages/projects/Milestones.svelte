<script module lang="ts">
    import { index } from '@/routes/workspaces/projects/milestones';

    export const layout = {
        breadcrumbs: [{ title: 'Milestone', href: index(['', '']) }],
    };
</script>

<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import { createPaginatedRowModel, createTable, createTableState, rowPaginationFeature, tableFeatures } from '@tanstack/svelte-table';
    import type { ColumnDef } from '@tanstack/svelte-table';
    import Add from 'carbon-icons-svelte/lib/Add.svelte';
    import Search from 'carbon-icons-svelte/lib/Search.svelte';
    import Calendar from 'carbon-icons-svelte/lib/Calendar.svelte';
    import CheckmarkOutline from 'carbon-icons-svelte/lib/CheckmarkOutline.svelte';
    import Flag from 'carbon-icons-svelte/lib/Flag.svelte';
    import Trash2 from 'carbon-icons-svelte/lib/TrashCan.svelte';
    import ChevronDown from 'carbon-icons-svelte/lib/ChevronDown.svelte';
    import ChevronUp from 'carbon-icons-svelte/lib/ChevronUp.svelte';
    import ChevronLeft from 'carbon-icons-svelte/lib/ChevronLeft.svelte';
    import ChevronRight from 'carbon-icons-svelte/lib/ChevronRight.svelte';
    import CheckCircle2 from 'carbon-icons-svelte/lib/CheckmarkFilled.svelte';
    import Edit from 'carbon-icons-svelte/lib/Edit.svelte';
    import MilestoneController from '@/actions/App/Http/Controllers/MilestoneController';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import AppHead from '@/components/AppHead.svelte';
    import DataTable from '@/components/DataTable.svelte';
    import InputError from '@/components/InputError.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import { formatDate } from '@/lib/format-date';
    import { projectBreadcrumbs } from '@/lib/page-header';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null; archived: boolean };
    type Milestone = { id: string; name: string; target_date: string | null; release_id: string | null; tasks_count: number; completed_tasks_count: number; progress: number };

    type Release = { id: string; name: string };
    let { workspace, project, milestones, releases, canManage }: { workspace: Workspace; project: Project; milestones: Milestone[]; releases: Release[]; canManage: boolean } = $props();
    let search = $state('');
    let createOpen = $state(false);
    let editingMilestone = $state<Milestone | null>(null);
    let milestoneName = $state('');
    let milestoneTargetDate = $state('');
    let selectedRelease = $state('');
    let sortKey = $state<'name' | 'target_date' | 'progress' | 'tasks_count'>('target_date');
    let sortDirection = $state<'asc' | 'desc'>('asc');
    const progressSegmentCount = 30;
    const milestoneFeatures = tableFeatures({
        rowPaginationFeature,
        paginatedRowModel: createPaginatedRowModel(),
    });
    const milestoneColumns: ColumnDef<typeof milestoneFeatures, Milestone>[] = [
        { accessorKey: 'name', header: 'Milestone' },
        { accessorKey: 'target_date', header: 'Target' },
        { accessorKey: 'progress', header: 'Progress' },
        { accessorKey: 'tasks_count', header: 'Task' },
        { id: 'actions', header: 'Aksi' },
    ];
    const milestoneHeaderIcons = { name: Flag, target_date: Calendar, progress: CheckmarkOutline, tasks_count: CheckmarkOutline, actions: Edit };
    const filteredMilestones = $derived.by(() => [...milestones]
        .filter((milestone) => milestone.name.toLowerCase().includes(search.trim().toLowerCase()))
        .sort((left, right) => {
            const leftValue = left[sortKey] ?? '';
            const rightValue = right[sortKey] ?? '';
            const comparison = typeof leftValue === 'number' && typeof rightValue === 'number'
                ? leftValue - rightValue
                : String(leftValue).localeCompare(String(rightValue));

            return sortDirection === 'asc' ? comparison : -comparison;
        }));
    const [milestonePagination, setMilestonePagination] = createTableState({ pageIndex: 0, pageSize: 10 });
    const milestoneTable = createTable({
        features: milestoneFeatures,
        columns: milestoneColumns,
        state: {
            get pagination() {
                return milestonePagination();
            },
        },
        onPaginationChange: setMilestonePagination,
        get data() {
            return filteredMilestones;
        },
        getRowId: (milestone) => milestone.id,
    });

    function confirmRemoval(event: SubmitEvent, name: string): void {
        if (!window.confirm(`Hapus milestone ${name}?`)) {
            event.preventDefault();
        }
    }

    function resetCreateForm(): void {
        createOpen = false;
        editingMilestone = null;
        milestoneName = '';
        milestoneTargetDate = '';
        selectedRelease = '';
    }

    function openCreate(): void {
        editingMilestone = null;
        milestoneName = '';
        milestoneTargetDate = '';
        selectedRelease = '';
        createOpen = true;
    }

    function openEdit(milestone: Milestone): void {
        editingMilestone = milestone;
        milestoneName = milestone.name;
        milestoneTargetDate = milestone.target_date ?? '';
        selectedRelease = milestone.release_id ?? '';
        createOpen = true;
    }

    function isOverdue(targetDate: string | null): boolean {
        return targetDate !== null && targetDate < new Date().toISOString().slice(0, 10);
    }

    function sortMilestones(key: typeof sortKey): void {
        if (sortKey === key) {
            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            return;
        }

        sortKey = key;
        sortDirection = 'asc';
    }

    function sortIcon(key: typeof sortKey): typeof ChevronDown {
        return sortKey === key && sortDirection === 'desc' ? ChevronDown : ChevronUp;
    }
</script>

<AppHead title={`Milestone · ${project.identifier}`} />

<div class="flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader breadcrumbs={projectBreadcrumbs(page.props.unitContext?.active?.name ?? workspace.name, page.props.unitContext?.active?.id, toUrl(ProjectController.index(workspace.id)), project.name, toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), 'milestones', milestones.length, page.props.projectContext?.active?.icon)}>
        {#snippet actions()}
            <label class="relative h-7 w-52 sm:w-60"><span class="sr-only">Cari Milestone</span><Search class="pointer-events-none absolute left-2.5 top-1.5 size-3.5 text-muted-foreground" aria-hidden="true" /><Input bind:value={search} type="search" placeholder="Cari Milestone..." class="h-7 w-full border-border/80 bg-background pl-8 text-xs" /></label>
            {#if canManage && !project.archived}<Button variant="outline" class="h-7 shrink-0 gap-1.5 border-border/80 bg-background px-2.5 text-xs text-muted-foreground" onclick={openCreate}><Add class="size-3.5" aria-hidden="true" />Tambah Milestone</Button>{/if}
        {/snippet}
    </PageHeader>

    {#if project.archived}
        <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan. Milestone bersifat read-only.</div>
    {/if}

    <section class="overflow-hidden rounded-lg border border-border/70 bg-card" aria-label="Daftar Milestone">
        {#if filteredMilestones.length === 0}
            <div class="flex min-h-48 flex-col items-center justify-center px-6 py-12 text-center">
                <Flag class="size-7 text-muted-foreground" aria-hidden="true" />
                <p class="mt-3 text-sm font-medium">Belum ada Milestone</p>
                <p class="mt-1 text-sm text-muted-foreground">Tambahkan target penting untuk membantu mengelompokkan pekerjaan.</p>
            </div>
        {:else}
            <DataTable table={milestoneTable} caption="Daftar Milestone" headerIcons={milestoneHeaderIcons} wrapperClass="overflow-hidden" tableClass="w-full min-w-[48rem] border-collapse text-left text-xs" headerClass="border-b border-border/70 bg-muted/25 px-3 py-2.5 text-xs font-medium text-muted-foreground first:border-l-0 border-l border-border/60">
                {#snippet headerActions(columnId)}
                    {@const key = columnId as typeof sortKey}
                    {#if ['name', 'target_date', 'progress', 'tasks_count'].includes(columnId)}
                        {@const SortIcon = sortIcon(key)}
                        <button type="button" class="inline-flex size-5 items-center justify-center rounded-sm text-muted-foreground hover:bg-muted hover:text-foreground" aria-label={`Urutkan ${columnId}`} title="Urutkan" onclick={(event) => { event.stopPropagation(); sortMilestones(key); }}><SortIcon class="size-3" /></button>
                    {/if}
                {/snippet}
                {#snippet children(row)}
                    {@const milestone = row.original}
                    <tr class="group border-b border-border/60 transition-colors last:border-b-0 hover:bg-muted/25">
                        <td class="px-3 py-2.5"><div class="flex items-center gap-2"><Flag class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" /><span class="font-medium">{milestone.name}</span></div></td>
                        <td class="border-l border-border/60 px-3 py-2.5 text-muted-foreground"><span class={`inline-flex min-h-6 items-center gap-1.5 rounded-full border px-2 py-0.5 text-[11px] leading-4 ${isOverdue(milestone.target_date) && milestone.progress < 100 ? 'border-destructive/30 bg-destructive/10 text-destructive' : 'border-border/70'}`}><Calendar class="size-3" aria-hidden="true" />{milestone.target_date ? formatDate(milestone.target_date) : 'Tanpa target'}</span></td>
                        <td class="w-[24rem] max-w-[24rem] border-l border-border/60 px-3 py-2.5"><div class="inline-flex max-w-full items-center gap-2 text-left"><span class="flex shrink-0 gap-0.5 overflow-hidden" aria-hidden="true">{#each Array.from({ length: progressSegmentCount }) as _, index}<span class="h-6 w-1 shrink-0 rounded-sm {index < Math.round((milestone.progress / 100) * progressSegmentCount) ? 'bg-emerald-500' : 'bg-muted'}"></span>{/each}</span><span class="w-10 shrink-0 text-right text-sm font-semibold text-muted-foreground">{milestone.progress}%</span></div></td>
                        <td class="border-l border-border/60 px-3 py-2.5"><Badge variant="outline" class="min-h-6 rounded-full px-2 py-0.5 text-[11px] leading-4"><CheckmarkOutline class="size-3" aria-hidden="true" />{milestone.completed_tasks_count}/{milestone.tasks_count}</Badge></td>
                        <td class="border-l border-border/60 px-3 py-2.5 text-center">{#if canManage}<div class="flex items-center justify-center gap-1"><button type="button" class="inline-flex size-7 items-center justify-center rounded-full border border-border/70 text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label={`Edit ${milestone.name}`} title="Edit milestone" onclick={() => openEdit(milestone)}><Edit class="size-3.5" aria-hidden="true" /></button><Form {...MilestoneController.destroy.form({ workspace: workspace.id, project: project.id, milestone: milestone.id })} onsubmit={(event) => confirmRemoval(event, milestone.name)}><button type="submit" class="inline-flex size-7 items-center justify-center rounded-full border border-border/70 text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label={`Hapus ${milestone.name}`} title="Hapus milestone"><Trash2 class="size-3.5" aria-hidden="true" /></button></Form></div>{:else}<span class="text-muted-foreground">—</span>{/if}</td>
                    </tr>
                {/snippet}
                {#snippet footer()}
                    {@const pagination = milestonePagination()}
                    {@const firstMilestone = filteredMilestones.length === 0 ? 0 : pagination.pageIndex * pagination.pageSize + 1}
                    {@const lastMilestone = Math.min((pagination.pageIndex + 1) * pagination.pageSize, filteredMilestones.length)}
                    <div class="flex flex-col gap-2 border-t border-border/70 px-3 py-2 text-[11px] text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                        <span>Menampilkan {firstMilestone}–{lastMilestone} dari {filteredMilestones.length} Milestone</span>
                        <div class="flex items-center gap-2">
                            <span class="hidden items-center gap-1.5 sm:inline-flex"><CheckCircle2 class="size-3.5" aria-hidden="true" /> Data diperbarui dari Milestone</span>
                            <Select value={String(pagination.pageSize)} onValueChange={(value) => milestoneTable.setPageSize(Number(value))}>
                                <SelectTrigger class="h-8 min-w-[118px] px-2 text-xs" aria-label="Milestone per halaman">{pagination.pageSize} / halaman</SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="10">10 / halaman</SelectItem>
                                    <SelectItem value="25">25 / halaman</SelectItem>
                                    <SelectItem value="50">50 / halaman</SelectItem>
                                </SelectContent>
                            </Select>
                            <span class="whitespace-nowrap">{pagination.pageIndex + 1} / {milestoneTable.getPageCount()}</span>
                            <button type="button" class="inline-flex size-8 items-center justify-center rounded-md border border-border/80 hover:bg-muted disabled:pointer-events-none disabled:opacity-40" aria-label="Halaman sebelumnya" disabled={!milestoneTable.getCanPreviousPage()} onclick={() => milestoneTable.previousPage()}><ChevronLeft class="size-4" aria-hidden="true" /></button>
                            <button type="button" class="inline-flex size-8 items-center justify-center rounded-md border border-border/80 hover:bg-muted disabled:pointer-events-none disabled:opacity-40" aria-label="Halaman berikutnya" disabled={!milestoneTable.getCanNextPage()} onclick={() => milestoneTable.nextPage()}><ChevronRight class="size-4" aria-hidden="true" /></button>
                        </div>
                    </div>
                {/snippet}
            </DataTable>
        {/if}
    </section>
</div>

<Dialog bind:open={createOpen}>
    <DialogContent class="max-h-[92vh] max-w-xl overflow-y-auto border-border bg-background p-0 shadow-lg">
        <div class="px-6 pb-1 pt-5"><DialogTitle class="text-sm font-medium text-muted-foreground">Milestone:</DialogTitle><DialogDescription class="sr-only">Tentukan target penting untuk project ini.</DialogDescription></div>
        <Form {...(editingMilestone ? MilestoneController.update.form({ workspace: workspace.id, project: project.id, milestone: editingMilestone.id }) : MilestoneController.store.form({ workspace: workspace.id, project: project.id }))} class="grid gap-4 px-6 pb-5" resetOnSuccess onSuccess={resetCreateForm}>
            {#snippet children({ errors, processing })}
                <div class="grid gap-2"><Label for="milestone-name" class="sr-only">Nama milestone</Label><Input id="milestone-name" name="name" bind:value={milestoneName} maxlength={120} required placeholder="Nama milestone" class="h-auto rounded-none border-0 bg-transparent px-0 text-xl font-medium shadow-none focus-visible:ring-0" /><InputError message={errors.name} /></div>
                <div class="grid gap-3 border-t border-border/70 pt-4">
                    <div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center"><div class="flex items-center gap-2 text-sm text-muted-foreground"><Calendar class="size-4" aria-hidden="true" />Target</div><Input id="milestone-date" name="target_date" bind:value={milestoneTargetDate} type="date" class="h-8 w-auto border-0 bg-muted/30 px-2 text-sm shadow-none focus-visible:ring-0" /><InputError message={errors.target_date} /></div>
                    <div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center"><div class="flex items-center gap-2 text-sm text-muted-foreground"><Flag class="size-4" aria-hidden="true" />Rilis</div><Select value={selectedRelease} onValueChange={(value) => { selectedRelease = value; }}><SelectTrigger id="milestone-release" class="h-8 w-fit min-w-40 max-w-full justify-self-start rounded-md border border-border/70 bg-transparent px-2 text-sm shadow-none focus:ring-0"><span class={selectedRelease ? 'text-foreground' : 'text-muted-foreground'}>{releases.find((release) => release.id === selectedRelease)?.name ?? 'Tanpa rilis'}</span></SelectTrigger><SelectContent><SelectItem value="">Tanpa rilis</SelectItem>{#each releases as release (release.id)}<SelectItem value={release.id}>{release.name}</SelectItem>{/each}</SelectContent></Select><input type="hidden" name="release_id" value={selectedRelease} /><InputError message={errors.release_id} /></div>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-border/70 pt-4 sm:flex-row sm:justify-end"><Button type="button" variant="ghost" onclick={() => { resetCreateForm(); }}>Batal</Button><Button type="submit" disabled={processing}>{processing ? 'Menyimpan...' : editingMilestone ? 'Simpan perubahan' : 'Tambah Milestone'}</Button></div>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>
