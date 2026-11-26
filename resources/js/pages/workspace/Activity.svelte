<script module lang="ts">
    import { activity } from '@/routes/workspaces';

    export const layout = {
        breadcrumbs: [{ title: 'Activity Log Workspace', href: activity(['']) }],
    };
</script>

<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import ActivityIcon from 'carbon-icons-svelte/lib/Activity.svelte';
    import Search from 'carbon-icons-svelte/lib/Search.svelte';
    import ActivityTable, { matchesActivity } from '@/components/ActivityTable.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import { unitPageBreadcrumbs } from '@/lib/page-header';

    type Workspace = { id: string; name: string; timezone: string };
    import type { ActivityItem } from '@/components/ActivityTable.svelte';

    let { workspace, activities }: { workspace: Workspace; activities: ActivityItem[] } = $props();
    let search = $state('');
    const workspaceTimezone = $derived(page.props.workspaceContext?.active?.timezone ?? workspace.timezone);
    const activeUnitName = $derived(page.props.unitContext?.active?.name ?? workspace.name);
    const filteredActivities = $derived(activities.filter((activity) => matchesActivity(activity, search)));
</script>

<AppHead title="Activity Log Workspace" />

<div class="flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader breadcrumbs={unitPageBreadcrumbs(activeUnitName, 'activity', activities.length, page.props.unitContext?.active?.id)}>
            {#snippet actions()}
                <div class="flex h-7 items-center justify-end gap-2">
                    <label class="relative h-7 w-52 sm:w-60">
                        <span class="sr-only">Cari aktivitas</span>
                        <Search class="pointer-events-none absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                        <input bind:value={search} type="search" placeholder="Cari aktivitas..." class="h-7 w-full rounded-md border border-border/80 bg-background pl-8 pr-3 text-xs outline-none transition-colors placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-ring/20" />
                    </label>
                    <span class="text-[11px] text-muted-foreground">{filteredActivities.length} dari {activities.length}</span>
                </div>
            {/snippet}
    </PageHeader>

    {#if activities.length === 0}
        <section class="rounded-lg border border-dashed border-border/80 px-6 py-14 text-center">
            <ActivityIcon class="mx-auto size-8 text-muted-foreground" aria-hidden="true" />
            <p class="mt-3 text-sm font-medium">Belum ada aktivitas Workspace.</p>
            <p class="mt-1 text-sm text-muted-foreground">Aktivitas dari Project dan Task akan muncul di sini.</p>
        </section>
    {:else}
        <ActivityTable activities={filteredActivities} timeZone={workspaceTimezone} includeIp />
    {/if}
</div>
