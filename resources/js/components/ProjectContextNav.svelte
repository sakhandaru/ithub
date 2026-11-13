<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import BarChart3 from '@lucide/svelte/icons/bar-chart-3';
    import FolderKanban from '@lucide/svelte/icons/folder-kanban';
    import LayoutDashboard from '@lucide/svelte/icons/layout-dashboard';
    import ListTodo from '@lucide/svelte/icons/list-todo';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string };
    type Project = { id: string };
    type NavKey = 'overview' | 'tasks' | 'board' | 'dashboard';

    let { workspace, project, active, taskQuery = {} }: { workspace: Workspace; project: Project; active: NavKey; taskQuery?: Record<string, string | number | undefined> } = $props();

    const items = $derived([
        { key: 'overview' as NavKey, label: 'Ikhtisar', href: toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), icon: FolderKanban },
        { key: 'tasks' as NavKey, label: 'Task', href: toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query: taskQuery })), icon: ListTodo },
        { key: 'board' as NavKey, label: 'Board', href: toUrl(TaskController.board({ workspace: workspace.id, project: project.id }, { query: taskQuery })), icon: BarChart3 },
        { key: 'dashboard' as NavKey, label: 'Dashboard', href: toUrl(ProjectController.dashboard({ workspace: workspace.id, project: project.id })), icon: LayoutDashboard },
    ]);
</script>

<nav class="-mx-1 flex gap-1 overflow-x-auto border-b px-1 pb-1 lg:hidden" aria-label="Navigasi Project">
    {#each items as item (item.key)}
        <Link href={item.href} aria-current={active === item.key ? 'page' : undefined} class="inline-flex min-h-10 shrink-0 items-center gap-2 rounded-md px-3 text-sm transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {active === item.key ? 'bg-muted font-medium text-foreground' : 'text-muted-foreground'}">
            <item.icon class="size-4" aria-hidden="true" />
            {item.label}
        </Link>
    {/each}
</nav>
