<script module lang="ts">
    export type ActivityEntity = {
        type: string;
        label: string;
        href: string | null;
    };

    export type ActivityItem = {
        id: string;
        action: string;
        created_at: string;
        ip_address?: string | null;
        entity: ActivityEntity;
        actor: { id: number; name: string; avatar?: string | null } | null;
    };

    export function matchesActivity(activity: ActivityItem, search: string): boolean {
        const term = search.trim().toLowerCase();

        return !term || [activity.action, activity.entity.label, activity.entity.type, activity.actor?.name, activity.ip_address]
            .filter(Boolean)
            .some((value) => value!.toLowerCase().includes(term));
    }
</script>

<script lang="ts">
    import { createPaginatedRowModel, createTable, createTableState, rowPaginationFeature, tableFeatures } from '@tanstack/svelte-table';
    import type { ColumnDef } from '@tanstack/svelte-table';
    import ActivityIcon from 'carbon-icons-svelte/lib/Activity.svelte';
    import Calendar from 'carbon-icons-svelte/lib/Calendar.svelte';
    import Check from 'carbon-icons-svelte/lib/Checkmark.svelte';
    import ChevronDown from 'carbon-icons-svelte/lib/ChevronDown.svelte';
    import ChevronUp from 'carbon-icons-svelte/lib/ChevronUp.svelte';
    import ChevronLeft from 'carbon-icons-svelte/lib/ChevronLeft.svelte';
    import ChevronRight from 'carbon-icons-svelte/lib/ChevronRight.svelte';
    import CheckCircle2 from 'carbon-icons-svelte/lib/CheckmarkFilled.svelte';
    import ListFilter from 'carbon-icons-svelte/lib/Filter.svelte';
    import Folder from 'carbon-icons-svelte/lib/Folder.svelte';
    import { Link } from '@inertiajs/svelte';
    import UserRound from 'carbon-icons-svelte/lib/UserAvatarFilled.svelte';
    import DataTable from '@/components/DataTable.svelte';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import { formatDateTime } from '@/lib/format-date';

    let { activities, timeZone, includeIp = false }: { activities: ActivityItem[]; timeZone?: string; includeIp?: boolean } = $props();
    type SortKey = 'created_at' | 'actor' | 'activity' | 'entity' | 'ip_address';
    type SortDirection = 'asc' | 'desc';
    let sortKey = $state<SortKey | null>(null);
    let sortDirection = $state<SortDirection>('desc');
    let actorFilter = $state('all');
    let activityFilter = $state('all');
    let entityFilter = $state('all');
    let ipFilter = $state('all');
    const activityFeatures = tableFeatures({
        rowPaginationFeature,
        paginatedRowModel: createPaginatedRowModel(),
    });
    const activityColumns = $derived([
        { accessorKey: 'created_at', header: 'Waktu' },
        { accessorFn: (activity: ActivityItem) => activity.actor?.name ?? 'Sistem', id: 'actor', header: 'Pelaku' },
        { accessorKey: 'action', id: 'activity', header: 'Aktivitas' },
        { accessorKey: 'entity', header: 'Entitas' },
        ...(includeIp ? [{ accessorKey: 'ip_address', header: 'IP' }] : []),
    ] as ColumnDef<typeof activityFeatures, ActivityItem>[]);
    const activityHeaderIcons = {
        created_at: Calendar,
        actor: UserRound,
        activity: ActivityIcon,
        entity: Folder,
        ip_address: ActivityIcon,
    };
    const actorOptions = $derived([...new Set(activities.map((activity) => activity.actor?.name ?? 'Sistem'))].sort());
    const activityOptions = $derived([...new Set(activities.map((activity) => activity.action))].sort());
    const entityOptions = $derived([...new Set(activities.map((activity) => activity.entity.type))].sort());
    const filteredActivities = $derived(activities.filter((activity) =>
        (actorFilter === 'all' || (activity.actor?.name ?? 'Sistem') === actorFilter)
        && (activityFilter === 'all' || activity.action === activityFilter)
        && (entityFilter === 'all' || activity.entity.type === entityFilter)
        && (ipFilter === 'all' || (ipFilter === 'set' ? Boolean(activity.ip_address) : !activity.ip_address)),
    ));
    const sortedActivities = $derived.by(() => {
        if (!sortKey) {
            return filteredActivities;
        }

        const activeSortKey = sortKey;

        return [...filteredActivities].sort((left, right) => {
        const leftValue = activitySortValue(left, activeSortKey);
        const rightValue = activitySortValue(right, activeSortKey);
        const result = leftValue.localeCompare(rightValue, undefined, { numeric: true, sensitivity: 'base' });

        return sortDirection === 'asc' ? result : -result;
        });
    });
    const [activityPagination, setActivityPagination] = createTableState({ pageIndex: 0, pageSize: 10 });
    const activityTable = createTable({
        features: activityFeatures,
        get columns() {
            return activityColumns;
        },
        get data() {
            return sortedActivities;
        },
        state: {
            get pagination() {
                return activityPagination();
            },
        },
        onPaginationChange: setActivityPagination,
        getRowId: (activity) => activity.id,
    });

    function moduleLabel(action: string): string {
        return action.split('.')[0] ?? action;
    }

    function actionLabel(action: string): string {
        const value = action.split('.').slice(1).join(' ').replaceAll('_', ' ');

        return value ? value.charAt(0).toUpperCase() + value.slice(1) : '—';
    }

    function activityLabel(action: string): string {
        return `${moduleLabel(action)} · ${actionLabel(action)}`;
    }

    function activitySortValue(activity: ActivityItem, key: SortKey): string {
        return key === 'created_at' ? activity.created_at
            : key === 'actor' ? activity.actor?.name ?? 'Sistem'
            : key === 'activity' ? `${moduleLabel(activity.action)} ${actionLabel(activity.action)}`
            : key === 'entity' ? `${activity.entity.type} ${activity.entity.label}`
            : activity.ip_address ?? '';
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
        return columnId === 'actor' ? actorFilter !== 'all'
            : columnId === 'activity' ? activityFilter !== 'all'
                : columnId === 'entity' ? entityFilter !== 'all'
                    : columnId === 'ip_address' && ipFilter !== 'all';
    }
</script>

<div class="overflow-hidden rounded-lg border border-border/70 bg-card">
    {#if activities.length === 0}
        <div class="flex min-h-40 flex-col items-center justify-center px-6 py-10 text-center">
            <p class="mt-3 text-sm font-medium">Tidak ada aktivitas</p>
            <p class="mt-1 text-sm text-muted-foreground">Coba ubah kata kunci pencarian.</p>
        </div>
    {:else}
        <DataTable table={activityTable} caption="Daftar Aktivitas" headerIcons={activityHeaderIcons} wrapperClass="overflow-hidden" tableClass={includeIp ? 'w-full min-w-[60rem] border-collapse text-left text-xs' : 'w-full min-w-[54rem] border-collapse text-left text-xs'} headerClass="border-b border-border/70 bg-muted/25 px-3 py-2.5 text-xs font-medium text-muted-foreground first:border-l-0 border-l border-border/60">
            {#snippet headerActions(columnId)}
                {#if ['created_at', 'actor', 'activity', 'entity', 'ip_address'].includes(columnId)}
                    <button type="button" class="inline-flex size-5 items-center justify-center rounded-sm text-muted-foreground hover:bg-muted hover:text-foreground {sortKey === columnId ? 'text-foreground' : ''}" aria-label={`Urutkan ${columnId}`} title={sortKey === columnId ? `Urutan ${sortDirection === 'asc' ? 'naik' : 'turun'}` : 'Urutkan'} onclick={(event) => { event.stopPropagation(); sortColumn(columnId as SortKey); }}>
                        {#if sortKey === columnId && sortDirection === 'desc'}<ChevronDown class="size-3" aria-hidden="true" />{:else}<ChevronUp class="size-3" aria-hidden="true" />{/if}
                    </button>
                {/if}
                {#if ['actor', 'activity', 'entity', 'ip_address'].includes(columnId)}
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            {#snippet children(props)}
                                <button {...props} type="button" class="inline-flex size-5 items-center justify-center rounded-sm text-muted-foreground hover:bg-muted hover:text-foreground {filterActive(columnId) ? 'text-foreground' : ''}" aria-label={`Filter ${columnId}`} title="Filter">
                                    <ListFilter class="size-3" aria-hidden="true" />
                                </button>
                            {/snippet}
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" class="max-h-72 w-56 overflow-y-auto p-1">
                            {#if columnId === 'actor'}
                                <DropdownMenuItem class={actorFilter === 'all' ? 'bg-muted' : ''} onclick={() => actorFilter = 'all'}>{#if actorFilter === 'all'}<Check class="size-3.5" />{/if}Semua pelaku</DropdownMenuItem>
                                {#each actorOptions as actor}<DropdownMenuItem class={actorFilter === actor ? 'bg-muted' : ''} onclick={() => actorFilter = actor}>{#if actorFilter === actor}<Check class="size-3.5" />{/if}{actor}</DropdownMenuItem>{/each}
                            {:else if columnId === 'activity'}
                                <DropdownMenuItem class={activityFilter === 'all' ? 'bg-muted' : ''} onclick={() => activityFilter = 'all'}>{#if activityFilter === 'all'}<Check class="size-3.5" />{/if}Semua aktivitas</DropdownMenuItem>
                                {#each activityOptions as activity}<DropdownMenuItem class={activityFilter === activity ? 'bg-muted' : ''} onclick={() => activityFilter = activity}>{#if activityFilter === activity}<Check class="size-3.5" />{/if}{activityLabel(activity)}</DropdownMenuItem>{/each}
                            {:else if columnId === 'entity'}
                                <DropdownMenuItem class={entityFilter === 'all' ? 'bg-muted' : ''} onclick={() => entityFilter = 'all'}>{#if entityFilter === 'all'}<Check class="size-3.5" />{/if}Semua entitas</DropdownMenuItem>
                                {#each entityOptions as entity}<DropdownMenuItem class={entityFilter === entity ? 'bg-muted' : ''} onclick={() => entityFilter = entity}>{#if entityFilter === entity}<Check class="size-3.5" />{/if}{entity}</DropdownMenuItem>{/each}
                            {:else}
                                <DropdownMenuItem class={ipFilter === 'all' ? 'bg-muted' : ''} onclick={() => ipFilter = 'all'}>{#if ipFilter === 'all'}<Check class="size-3.5" />{/if}Semua IP</DropdownMenuItem>
                                <DropdownMenuItem class={ipFilter === 'set' ? 'bg-muted' : ''} onclick={() => ipFilter = 'set'}>{#if ipFilter === 'set'}<Check class="size-3.5" />{/if}Ada IP</DropdownMenuItem>
                                <DropdownMenuItem class={ipFilter === 'none' ? 'bg-muted' : ''} onclick={() => ipFilter = 'none'}>{#if ipFilter === 'none'}<Check class="size-3.5" />{/if}Tanpa IP</DropdownMenuItem>
                            {/if}
                        </DropdownMenuContent>
                    </DropdownMenu>
                {/if}
            {/snippet}
            {#snippet children(row)}
                {@const item = row.original}
                <tr class="border-b border-border/60 transition-colors last:border-b-0 hover:bg-muted/25">
                    <td class="whitespace-nowrap px-3 py-2.5 text-xs text-muted-foreground"><span class="inline-flex items-center gap-1.5"><Calendar class="size-3.5" aria-hidden="true" />{formatDateTime(item.created_at, timeZone)}</span></td>
                    <td class="border-l border-border/60 px-3 py-2.5"><span class="inline-flex max-w-44 items-center gap-1.5 rounded-full border border-border/70 px-2 py-0.5 text-xs leading-4 text-muted-foreground">{#if item.actor}<UserAvatar user={item.actor} size="size-4" label={false} />{:else}<span class="flex size-4 items-center justify-center rounded-full bg-muted text-[8px]">S</span>{/if}<span class="truncate">{item.actor?.name ?? 'Sistem'}</span></span></td>
                    <td class="border-l border-border/60 px-3 py-2.5 text-xs"><span class="font-medium">{activityLabel(item.action)}</span></td>
                    <td class="border-l border-border/60 px-3 py-2.5">
                        {#if item.entity.href}
                            <Link href={item.entity.href} class="inline-flex max-w-64 items-center gap-1.5 rounded-full border border-border/70 px-2.5 py-0.5 text-xs text-muted-foreground hover:border-primary/40 hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><span class="shrink-0 font-medium text-foreground">{item.entity.type}</span><span class="truncate">{item.entity.label}</span></Link>
                        {:else}
                            <span class="inline-flex max-w-64 items-center gap-1.5 rounded-full border border-border/70 px-2.5 py-0.5 text-xs text-muted-foreground"><span class="shrink-0 font-medium text-foreground">{item.entity.type}</span><span class="truncate">{item.entity.label}</span></span>
                        {/if}
                    </td>
                    {#if includeIp}<td class="border-l border-border/60 px-3 py-2.5 font-mono text-xs text-muted-foreground">{item.ip_address ?? '—'}</td>{/if}
                </tr>
            {/snippet}
            {#snippet footer()}
                {@const pagination = activityPagination()}
                {@const firstActivity = sortedActivities.length === 0 ? 0 : pagination.pageIndex * pagination.pageSize + 1}
                {@const lastActivity = Math.min((pagination.pageIndex + 1) * pagination.pageSize, sortedActivities.length)}
                <div class="flex flex-col gap-2 border-t border-border/70 px-3 py-2 text-[11px] text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <span>Menampilkan {firstActivity}–{lastActivity} dari {sortedActivities.length} Aktivitas</span>
                    <div class="flex items-center gap-2">
                        <span class="hidden items-center gap-1.5 sm:inline-flex"><CheckCircle2 class="size-3.5" aria-hidden="true" /> Data diperbarui dari Aktivitas</span>
                        <Select value={String(pagination.pageSize)} onValueChange={(value) => activityTable.setPageSize(Number(value))}>
                            <SelectTrigger class="h-8 min-w-[118px] px-2 text-xs" aria-label="Aktivitas per halaman">{pagination.pageSize} / halaman</SelectTrigger>
                            <SelectContent>
                                <SelectItem value="10">10 / halaman</SelectItem>
                                <SelectItem value="25">25 / halaman</SelectItem>
                                <SelectItem value="50">50 / halaman</SelectItem>
                            </SelectContent>
                        </Select>
                        <span class="whitespace-nowrap">{pagination.pageIndex + 1} / {activityTable.getPageCount()}</span>
                        <button type="button" class="inline-flex size-8 items-center justify-center rounded-md border border-border/80 hover:bg-muted disabled:pointer-events-none disabled:opacity-40" aria-label="Halaman sebelumnya" disabled={!activityTable.getCanPreviousPage()} onclick={() => activityTable.previousPage()}><ChevronLeft class="size-4" aria-hidden="true" /></button>
                        <button type="button" class="inline-flex size-8 items-center justify-center rounded-md border border-border/80 hover:bg-muted disabled:pointer-events-none disabled:opacity-40" aria-label="Halaman berikutnya" disabled={!activityTable.getCanNextPage()} onclick={() => activityTable.nextPage()}><ChevronRight class="size-4" aria-hidden="true" /></button>
                    </div>
                </div>
            {/snippet}
        </DataTable>
    {/if}
</div>
