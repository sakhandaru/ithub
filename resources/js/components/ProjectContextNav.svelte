<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Column from 'carbon-icons-svelte/lib/Column.svelte';
    import Calendar from 'carbon-icons-svelte/lib/Calendar.svelte';
    import ChartLine from 'carbon-icons-svelte/lib/ChartLine.svelte';
    import List from 'carbon-icons-svelte/lib/List.svelte';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string };
    type Project = { id: string };
    type NavKey = 'overview' | 'tasks' | 'board' | 'calendar' | 'gantt' | 'dashboard' | 'milestone' | 'activity';

    let { workspace, project, active, taskQuery = {} }: { workspace: Workspace; project: Project; active: NavKey; taskQuery?: Record<string, string | number | undefined> } = $props();

    const items = $derived([
        { key: 'tasks' as NavKey, label: 'Task', href: toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query: { ...taskQuery, view: 'list' } })), icon: List },
        { key: 'board' as NavKey, label: 'Board', href: toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query: { ...taskQuery, view: 'board' } })), icon: Column },
        { key: 'calendar' as NavKey, label: 'Calendar', href: toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query: { ...taskQuery, view: 'calendar' } })), icon: Calendar },
        { key: 'gantt' as NavKey, label: 'Gantt', href: toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query: { ...taskQuery, view: 'gantt' } })), icon: ChartLine },
    ]);
</script>

<nav class="grid h-7 w-32 shrink-0 grid-cols-4 items-center gap-0.5 rounded-md border border-border/80 bg-background p-0.5" aria-label="Tampilan Task Project">
    {#each items as item (item.key)}
        <Link href={item.href} aria-label={item.label} title={item.label} aria-current={active === item.key ? 'page' : undefined} class="inline-flex shrink-0 items-center justify-center rounded-sm text-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {active === item.key ? 'size-5 bg-muted font-medium text-foreground' : 'size-6 text-muted-foreground'}">
            <item.icon class={active === item.key ? 'size-4' : 'size-3.5'} aria-hidden="true" />
        </Link>
    {/each}
</nav>
