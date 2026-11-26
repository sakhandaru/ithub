<script lang="ts">
    import type { Snippet } from 'svelte';
    import PageBreadcrumb from '@/components/PageBreadcrumb.svelte';
    import type { BreadcrumbItem } from '@/components/PageBreadcrumb.svelte';

    let { leading, actions, breadcrumbs, title, description, showSidebarTrigger = true, variant = 'compact' }: { leading?: Snippet; actions?: Snippet; breadcrumbs?: BreadcrumbItem[]; title?: string; description?: string; showSidebarTrigger?: boolean; variant?: 'compact' | 'content' } = $props();
</script>

<header class={variant === 'content' ? 'sticky top-0 z-20 flex flex-col gap-3 border-b border-border/80 bg-card pb-5 sm:flex-row sm:items-end sm:justify-between' : `sticky top-0 z-20 -mx-4 -mt-5 flex h-11 min-w-0 shrink-0 flex-col gap-1 border-b border-border/80 bg-card px-4 sm:-mx-6 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:-mx-8 lg:px-8`}>
    <div class="flex min-w-0 items-center gap-2">
        {#if breadcrumbs}
            <PageBreadcrumb items={breadcrumbs} {showSidebarTrigger} />
        {:else if leading}
            {@render leading()}
        {/if}

        {#if title}
            <div class="min-w-0">
                <h1 class={variant === 'content' ? 'text-xl font-semibold tracking-tight' : 'truncate text-[13px] font-semibold'}>{title}</h1>
                {#if description}<p class={variant === 'content' ? 'mt-1 text-sm text-muted-foreground' : 'truncate text-[11px] text-muted-foreground'}>{description}</p>{/if}
            </div>
        {/if}
    </div>

    {#if actions}
        <div class="flex min-w-0 flex-wrap items-center gap-1 sm:justify-end">
            {@render actions()}
        </div>
    {/if}
</header>
