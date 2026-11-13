<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import Breadcrumbs from '@/components/Breadcrumbs.svelte';
    import { SidebarTrigger } from '@/components/ui/sidebar';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
    }: {
        breadcrumbs?: BreadcrumbItem[];
    } = $props();

    const area = $derived(page.url.startsWith('/settings') ? 'Pengaturan' : 'Pekerjaan');
</script>

<header
    class="flex h-12 shrink-0 items-center gap-2 border-b border-sidebar-border px-2 sm:px-3"
>
    <div class="flex min-w-0 items-center gap-2">
        <SidebarTrigger class="size-10 sm:size-8" />
        <span class="text-sm font-medium sm:hidden">{area}</span>
        {#if breadcrumbs && breadcrumbs.length > 0}
            <div class="hidden min-w-0 sm:block">
                <Breadcrumbs {breadcrumbs} />
            </div>
        {/if}
    </div>
</header>
