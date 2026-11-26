<script module lang="ts">
    import { cycles } from '@/routes/workspaces/projects';

    export const layout = {
        breadcrumbs: [{ title: 'Siklus', href: cycles(['', '']) }],
    };
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import RotateCcw from 'carbon-icons-svelte/lib/Renew.svelte';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import AppHead from '@/components/AppHead.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import { projectBreadcrumbs } from '@/lib/page-header';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null };

    let { workspace, project }: { workspace: Workspace; project: Project } = $props();
</script>

<AppHead title={`${project.identifier} · Siklus`} />

<div class="flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader breadcrumbs={projectBreadcrumbs(page.props.unitContext?.active?.name ?? workspace.name, page.props.unitContext?.active?.id, toUrl(ProjectController.index(workspace.id)), project.name, toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), 'cycles', undefined, page.props.projectContext?.active?.icon)} />

    <div class="mx-auto w-full max-w-5xl">
        {#if project.archived_at}
            <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan. Siklus bersifat read-only.</div>
        {/if}

        <section class="rounded-lg border border-dashed border-border/80 px-6 py-14 text-center">
            <RotateCcw class="mx-auto size-8 text-muted-foreground" aria-hidden="true" />
            <h2 class="mt-3 text-sm font-semibold">Belum ada Siklus</h2>
            <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">Siklus belum dibuat untuk Project ini. Pengelolaan siklus akan tersedia di halaman ini.</p>
        </section>
    </div>
</div>
