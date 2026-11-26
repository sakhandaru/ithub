<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import FolderAdd from 'carbon-icons-svelte/lib/FolderAdd.svelte';
    import TaskAdd from 'carbon-icons-svelte/lib/TaskAdd.svelte';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import WorkspaceSwitcher from '@/components/WorkspaceSwitcher.svelte';
    import { toUrl } from '@/lib/utils';

    let { compact = false, onNavigate }: { compact?: boolean; onNavigate?: () => void } = $props();
    const unitContext = $derived(page.props.unitContext);
    const projectContext = $derived(page.props.projectContext);
    const projectHref = $derived(unitContext.active ? toUrl(ProjectController.create(unitContext.active.workspace_id)) : undefined);
    const taskHref = $derived(projectContext.active ? toUrl(TaskController.index({ workspace: projectContext.active.workspace_id, project: projectContext.active.id }, { query: { create: '1' } })) : undefined);
</script>

{#if compact}
    <div class="grid w-full justify-items-center gap-1" aria-label="Aksi cepat">
        <WorkspaceSwitcher compact />
        {#if projectHref}
            <Link href={projectHref} class="inline-flex size-10 items-center justify-center rounded-md text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-foreground focus-visible:ring-2 focus-visible:ring-sidebar-ring" aria-label="Tambah Project" title="Tambah Project" onclick={onNavigate}><FolderAdd class="size-5" aria-hidden="true" /></Link>
        {:else}
            <span class="inline-flex size-10 items-center justify-center rounded-md text-muted-foreground/50" aria-disabled="true" title="Pilih Unit terlebih dahulu"><FolderAdd class="size-5" aria-hidden="true" /></span>
        {/if}
        {#if taskHref}
            <Link href={taskHref} class="inline-flex size-10 items-center justify-center rounded-md text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-foreground focus-visible:ring-2 focus-visible:ring-sidebar-ring" aria-label="Tambah Task" title="Tambah Task" onclick={onNavigate}><TaskAdd class="size-5" aria-hidden="true" /></Link>
        {:else}
            <span class="inline-flex size-10 items-center justify-center rounded-md text-muted-foreground/50" aria-disabled="true" title="Buka Project terlebih dahulu"><TaskAdd class="size-5" aria-hidden="true" /></span>
        {/if}
    </div>
{:else}
    <div class="grid w-full gap-1.5">
        <div class="flex h-9 w-full min-w-0 items-center rounded-md border border-sidebar-border/70 bg-sidebar-accent/30 p-0.5"><WorkspaceSwitcher /></div>
        <div class="grid grid-cols-2 gap-1.5">
            {#if projectHref}
                <Link href={projectHref} class="inline-flex min-h-9 min-w-0 items-center justify-center gap-1.5 rounded-md border border-sidebar-border/70 px-2 text-xs font-medium text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-foreground focus-visible:ring-2 focus-visible:ring-sidebar-ring" onclick={onNavigate}><FolderAdd class="size-4 shrink-0" aria-hidden="true" /><span class="truncate">Project</span></Link>
            {:else}
                <span class="inline-flex min-h-9 min-w-0 items-center justify-center gap-1.5 rounded-md border border-sidebar-border/70 px-2 text-xs text-muted-foreground/60" aria-disabled="true"><FolderAdd class="size-4 shrink-0" aria-hidden="true" /><span class="truncate">Project</span></span>
            {/if}
            {#if taskHref}
                <Link href={taskHref} class="inline-flex min-h-9 min-w-0 items-center justify-center gap-1.5 rounded-md border border-sidebar-border/70 px-2 text-xs font-medium text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-foreground focus-visible:ring-2 focus-visible:ring-sidebar-ring" onclick={onNavigate}><TaskAdd class="size-4 shrink-0" aria-hidden="true" /><span class="truncate">Task</span></Link>
            {:else}
                <span class="inline-flex min-h-9 min-w-0 items-center justify-center gap-1.5 rounded-md border border-sidebar-border/70 px-2 text-xs text-muted-foreground/60" aria-disabled="true"><TaskAdd class="size-4 shrink-0" aria-hidden="true" /><span class="truncate">Task</span></span>
            {/if}
        </div>
    </div>
{/if}
