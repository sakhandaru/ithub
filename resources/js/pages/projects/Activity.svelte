<script module lang="ts">
    import { activity } from '@/routes/workspaces/projects';

    export const layout = {
        breadcrumbs: [{ title: 'Activity Log', href: activity(['', '']) }],
    };
</script>

<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import ActivityIcon from 'carbon-icons-svelte/lib/Activity.svelte';
    import Search from 'carbon-icons-svelte/lib/Search.svelte';
    import ActivityTable, { matchesActivity } from '@/components/ActivityTable.svelte';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import AppHead from '@/components/AppHead.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import { projectBreadcrumbs } from '@/lib/page-header';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null };
    import type { ActivityItem } from '@/components/ActivityTable.svelte';

    let { workspace, project, activities }: { workspace: Workspace; project: Project; activities: ActivityItem[] } = $props();
    let search = $state('');
    const workspaceTimezone = $derived(page.props.workspaceContext?.active?.timezone);
    const filteredActivities = $derived(activities.filter((activity) => matchesActivity(activity, search)));
</script>

<AppHead title={`${project.identifier} · Activity Log`} />

<div class="flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader breadcrumbs={projectBreadcrumbs(page.props.unitContext?.active?.name ?? workspace.name, page.props.unitContext?.active?.id, toUrl(ProjectController.index(workspace.id)), project.name, toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), 'activity', activities.length, page.props.projectContext?.active?.icon)}>
        {#snippet actions()}
            <div class="flex min-h-7 flex-wrap items-center justify-end gap-1.5">
                <label class="relative h-7 w-52 sm:w-60">
                    <span class="sr-only">Cari aktivitas</span>
                    <Search class="pointer-events-none absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                    <input bind:value={search} type="search" placeholder="Cari Aktivitas..." class="h-7 w-full rounded-md border border-border/80 bg-background pl-8 pr-3 text-xs outline-none transition-colors placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-ring/20" />
                </label>
            </div>
        {/snippet}
    </PageHeader>

    {#if project.archived_at}
        <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan. Activity Log tetap dapat dilihat.</div>
    {/if}

    {#if activities.length === 0}
        <section class="rounded-lg border border-dashed border-border/80 px-6 py-14 text-center">
            <ActivityIcon class="mx-auto size-8 text-muted-foreground" aria-hidden="true" />
            <p class="mt-3 text-sm font-medium">Belum ada aktivitas Project.</p>
            <p class="mt-1 text-sm text-muted-foreground">Aktivitas perubahan Project dan Task akan muncul di sini.</p>
        </section>
    {:else}
        <ActivityTable activities={filteredActivities} timeZone={workspaceTimezone} />
    {/if}
</div>
