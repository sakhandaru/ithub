<script lang="ts">
    import { onMount } from 'svelte';
    import { Link } from '@inertiajs/svelte';
    import BarChart3 from '@lucide/svelte/icons/bar-chart-3';
    import FolderKanban from '@lucide/svelte/icons/folder-kanban';
    import LayoutDashboard from '@lucide/svelte/icons/layout-dashboard';
    import Flag from '@lucide/svelte/icons/flag';
    import Activity from '@lucide/svelte/icons/activity';
    import ListTodo from '@lucide/svelte/icons/list-todo';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import ProjectSettingsController from '@/actions/App/Http/Controllers/ProjectSettingsController';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string };
    type Project = { id: string };
    type NavKey = 'overview' | 'tasks' | 'board' | 'dashboard' | 'milestone' | 'activity';

    let { workspace, project, active, taskQuery = {} }: { workspace: Workspace; project: Project; active: NavKey; taskQuery?: Record<string, string | number | undefined> } = $props();
    let currentHash = $state('');

    onMount(() => {
        const updateHash = (): void => { currentHash = window.location.hash; };
        updateHash();
        window.addEventListener('hashchange', updateHash);
        return () => window.removeEventListener('hashchange', updateHash);
    });

    const activeKey = $derived(currentHash === '#milestones' ? 'milestone' : currentHash === '#activity-log' ? 'activity' : active);

    const items = $derived([
        { key: 'overview' as NavKey, label: 'Ikhtisar', href: toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), icon: FolderKanban },
        { key: 'tasks' as NavKey, label: 'Task', href: toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query: taskQuery })), icon: ListTodo },
        { key: 'board' as NavKey, label: 'Board', href: toUrl(TaskController.board({ workspace: workspace.id, project: project.id }, { query: taskQuery })), icon: BarChart3 },
        { key: 'dashboard' as NavKey, label: 'Dashboard', href: toUrl(ProjectController.dashboard({ workspace: workspace.id, project: project.id })), icon: LayoutDashboard },
        { key: 'milestone' as NavKey, label: 'Milestone', href: toUrl(ProjectSettingsController.show({ workspace: workspace.id, project: project.id }) + '#milestones'), icon: Flag },
        { key: 'activity' as NavKey, label: 'Activity Log', href: toUrl(ProjectController.show({ workspace: workspace.id, project: project.id }) + '#activity-log'), icon: Activity },
    ]);
</script>

<nav class="-mx-1 flex gap-1 overflow-x-auto border-b px-1 pb-1 lg:hidden" aria-label="Navigasi Project">
    {#each items as item (item.key)}
        <Link href={item.href} aria-current={activeKey === item.key ? 'page' : undefined} class="inline-flex min-h-10 shrink-0 items-center gap-2 rounded-md px-3 text-sm transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {activeKey === item.key ? 'bg-muted font-medium text-foreground' : 'text-muted-foreground'}">
            <item.icon class="size-4" aria-hidden="true" />
            {item.label}
        </Link>
    {/each}
</nav>
