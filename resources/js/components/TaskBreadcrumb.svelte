<script lang="ts">
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import PageBreadcrumb from '@/components/PageBreadcrumb.svelte';
    import { globalHeaderLabels } from '@/lib/page-header';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string };
    type Project = { id: string; name: string; icon?: string | null; emoji?: string | null };

    let { workspace, project, count }: { workspace: Workspace; project: Project; count?: number } = $props();
</script>

<PageBreadcrumb
    items={[
        { label: project.name, href: toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), icon: 'folder' },
        { label: globalHeaderLabels.task, href: toUrl(TaskController.index({ workspace: workspace.id, project: project.id })), icon: 'tasks', count },
    ]}
    showSidebarTrigger
/> 
