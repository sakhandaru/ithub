<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Activity from 'carbon-icons-svelte/lib/Activity.svelte';
    import ChevronRight from 'carbon-icons-svelte/lib/ChevronRight.svelte';
    import Folder from 'carbon-icons-svelte/lib/Folder.svelte';
    import UserRound from 'carbon-icons-svelte/lib/UserAvatarFilled.svelte';
    import CalendarDays from 'carbon-icons-svelte/lib/Calendar.svelte';
    import ChartGantt from 'carbon-icons-svelte/lib/ChartNetwork.svelte';
    import ListTodo from 'carbon-icons-svelte/lib/Task.svelte';
    import Grid from 'carbon-icons-svelte/lib/Grid.svelte';
    import Document from 'carbon-icons-svelte/lib/Document.svelte';
    import Flag from 'carbon-icons-svelte/lib/Flag.svelte';
    import RefreshCw from 'carbon-icons-svelte/lib/Renew.svelte';
    import Add from 'carbon-icons-svelte/lib/Add.svelte';
    import LayoutDashboard from 'carbon-icons-svelte/lib/Dashboard.svelte';
    import BarChart3 from 'carbon-icons-svelte/lib/ChartBar.svelte';
    import Archive from 'carbon-icons-svelte/lib/Archive.svelte';
    import { SidebarTrigger, useSidebar } from '@/components/ui/sidebar';

    export type IconName = 'activity' | 'calendar' | 'folder' | 'gantt' | 'none' | 'tasks' | 'user' | 'modules' | 'archive' | 'dashboard' | 'analytics' | 'files' | 'milestones' | 'updates' | 'releases';
    export type BreadcrumbItem = { label: string; href?: string; count?: number; icon?: IconName; projectIcon?: string };

    let { items, showSidebarTrigger = false }: { items: BreadcrumbItem[]; showSidebarTrigger?: boolean } = $props();
    const { state: sidebarState } = useSidebar();
</script>

<div class="page-breadcrumb flex min-w-0 items-center gap-0 text-[13px]">
    {#if showSidebarTrigger && $sidebarState === 'collapsed'}
        <SidebarTrigger class="size-6 rounded-md text-muted-foreground hover:bg-transparent hover:text-foreground" />
        <ChevronRight class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
    {/if}
    {#each items as item, index (item.label)}
        {#if index > 0}<ChevronRight class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{/if}
        {@const isCurrent = index === items.length - 1}
        {#if item.href && !isCurrent}
            <Link href={item.href} class="inline-flex min-h-6 min-w-0 items-center gap-1 rounded-md px-1 py-0.5 font-medium text-foreground hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                {#if item.projectIcon}<span class="text-sm leading-none" aria-hidden="true">{item.projectIcon}</span>{:else if item.icon === 'folder'}<Folder class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'archive'}<Archive class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'activity'}<Activity class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'dashboard'}<LayoutDashboard class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'analytics'}<BarChart3 class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'calendar'}<CalendarDays class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'gantt'}<ChartGantt class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'tasks'}<ListTodo class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'user'}<UserRound class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'modules'}<Grid class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'files'}<Document class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'milestones'}<Flag class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'updates'}<RefreshCw class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'releases'}<Add class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{/if}
                <span class="whitespace-nowrap">{item.label}</span>
                {#if item.count !== undefined}<span class="rounded-full bg-primary/10 px-1.5 py-0.5 text-[10px] font-medium leading-none text-primary">{item.count}</span>{/if}
            </Link>
        {:else}
            <span class="inline-flex min-h-6 min-w-0 items-center gap-1 rounded-md px-1 py-0.5 {isCurrent ? 'text-muted-foreground' : 'font-medium text-foreground'}">
                {#if item.projectIcon}<span class="text-sm leading-none" aria-hidden="true">{item.projectIcon}</span>{:else if item.icon === 'folder'}<Folder class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'archive'}<Archive class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'activity'}<Activity class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'dashboard'}<LayoutDashboard class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'analytics'}<BarChart3 class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'calendar'}<CalendarDays class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'gantt'}<ChartGantt class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'tasks'}<ListTodo class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'user'}<UserRound class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'modules'}<Grid class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'files'}<Document class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'milestones'}<Flag class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'updates'}<RefreshCw class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{:else if item.icon === 'releases'}<Add class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />{/if}
                <span class="whitespace-nowrap">{item.label}</span>
                {#if item.count !== undefined}<span class="rounded-full bg-primary/10 px-1.5 py-0.5 text-[10px] font-medium leading-none text-primary">{item.count}</span>{/if}
            </span>
        {/if}
    {/each}
</div>

<style>
    :global(.page-breadcrumb) {
        overflow-x: auto;
        white-space: nowrap;
    }

    :global(.page-breadcrumb > a),
    :global(.page-breadcrumb > span) {
        flex-shrink: 0;
    }

</style>
