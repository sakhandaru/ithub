<script lang="ts">
    import { onMount } from 'svelte';
    import { Link, page, router } from '@inertiajs/svelte';
    import Search from '@lucide/svelte/icons/search';
    import LayoutDashboard from '@lucide/svelte/icons/layout-dashboard';
    import Bell from '@lucide/svelte/icons/bell';
    import FolderKanban from '@lucide/svelte/icons/folder-kanban';
    import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { dashboard, search } from '@/routes';
    import { index as notificationsIndex } from '@/routes/notifications';
    import { index as projectsIndex } from '@/routes/workspaces/projects';
    import { toUrl } from '@/lib/utils';

    let open = $state(false);
    let query = $state('');
    const workspace = $derived(page.props.workspaceContext?.active);

    onMount(() => {
        const handleShortcut = (event: KeyboardEvent): void => {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                open = true;
                query = '';
            }
        };
        window.addEventListener('keydown', handleShortcut);
        return () => window.removeEventListener('keydown', handleShortcut);
    });

    function submitSearch(event: SubmitEvent): void {
        event.preventDefault();
        if (!query.trim()) return;
        open = false;
        router.visit(toUrl(search({ query: { q: query.trim() } })));
    }
</script>

<Dialog bind:open onOpenChange={(value) => { open = value; }}>
    <DialogContent class="max-w-lg">
        <DialogTitle>Cari dan buka</DialogTitle>
        <DialogDescription>Cari Task atau Project, atau langsung buka area kerja.</DialogDescription>
        <form class="relative mt-4" onsubmit={submitSearch}>
            <Search class="pointer-events-none absolute left-3 top-3 size-4 text-muted-foreground" aria-hidden="true" />
            <Input bind:value={query} class="min-h-11 pl-9" placeholder="Cari Task atau Project" autofocus />
        </form>
        <div class="mt-4 grid gap-2">
            <Link href={toUrl(dashboard())} onclick={() => { open = false; }} class="flex min-h-11 items-center gap-3 rounded-md border px-3 text-sm hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-ring"><LayoutDashboard class="size-4" aria-hidden="true" />Dashboard</Link>
            {#if workspace}<Link href={toUrl(projectsIndex({ workspace: workspace.id }))} onclick={() => { open = false; }} class="flex min-h-11 items-center gap-3 rounded-md border px-3 text-sm hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-ring"><FolderKanban class="size-4" aria-hidden="true" />Semua Project</Link>{/if}
            <Link href={toUrl(notificationsIndex())} onclick={() => { open = false; }} class="flex min-h-11 items-center gap-3 rounded-md border px-3 text-sm hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-ring"><Bell class="size-4" aria-hidden="true" />Notifikasi</Link>
        </div>
    </DialogContent>
</Dialog>
