<script lang="ts">
    import type { Snippet } from 'svelte';
    import AppContent from '@/components/AppContent.svelte';
    import AppShell from '@/components/AppShell.svelte';
    import AppSidebar from '@/components/AppSidebar.svelte';
    import AppSidebarHeader from '@/components/AppSidebarHeader.svelte';
    import { Toaster } from '@/components/ui/sonner';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
        children,
    }: {
        breadcrumbs?: BreadcrumbItem[];
        children?: Snippet;
    } = $props();
</script>

<AppShell variant="sidebar">
    <div class="flex h-full min-h-0 w-full overflow-hidden bg-background">
        <div class="flex h-full min-h-0 w-full flex-col overflow-hidden bg-muted md:border md:border-border/80 md:shadow-lg">
            <AppSidebarHeader {breadcrumbs} />
            <div class="flex min-h-0 flex-1">
            <AppSidebar>
                <AppContent variant="sidebar" class="min-w-0 flex-1 overflow-x-clip bg-card">
                    {@render children?.()}
                </AppContent>
            </AppSidebar>
            </div>
        </div>
    </div>
    <Toaster />
</AppShell>
