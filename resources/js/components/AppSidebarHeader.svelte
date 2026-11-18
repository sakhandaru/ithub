<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import Search from 'carbon-icons-svelte/lib/Search.svelte';
    import Bell from 'carbon-icons-svelte/lib/Notification.svelte';
    import ChevronRight from 'carbon-icons-svelte/lib/ChevronRight.svelte';
    import CommandPalette from '@/components/CommandPalette.svelte';
    import { formatDate } from '@/lib/format-date';
    import AppLogo from '@/components/AppLogo.svelte';
    import WorkspaceSwitcher from '@/components/WorkspaceSwitcher.svelte';
    import { dashboard, search } from '@/routes';
    import { index as notificationsIndex } from '@/routes/notifications';
    import { toUrl } from '@/lib/utils';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
    }: {
        breadcrumbs?: BreadcrumbItem[];
    } = $props();

    const area = $derived(page.url.startsWith('/settings') ? 'Pengaturan' : 'Pekerjaan');
    const unreadCount = $derived(page.props.notifications?.unread_count ?? 0);
    const notificationItems = $derived(page.props.notifications?.items ?? []);
    const workspaceTimezone = $derived(page.props.unitContext?.active ? page.props.workspaceContext?.active?.timezone : undefined);
    let notificationOpen = $state(false);

    onMount(() => {
        const closeOnOutsideClick = (event: MouseEvent): void => {
            if (!(event.target instanceof Element) || !event.target.closest('[data-notification-popover]')) {
                notificationOpen = false;
            }
        };
        const closeOnEscape = (event: KeyboardEvent): void => {
            if (event.key === 'Escape') {
                notificationOpen = false;
            }
        };

        document.addEventListener('click', closeOnOutsideClick);
        document.addEventListener('keydown', closeOnEscape);

        return () => {
            document.removeEventListener('click', closeOnOutsideClick);
            document.removeEventListener('keydown', closeOnEscape);
        };
    });
</script>

<header
    class="relative flex h-11 shrink-0 items-center gap-2.5 bg-muted px-2 sm:px-3"
>
    <div class="flex min-w-0 items-center gap-2">
        <Link href={toUrl(dashboard())} class="hidden shrink-0 items-center rounded-md py-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:flex" aria-label="Beranda ithub">
            <AppLogo />
        </Link>
        <div class="min-w-0 max-w-48 flex-1 sm:flex-none">
            <WorkspaceSwitcher />
        </div>
        <span class="text-sm font-medium sm:hidden">{area}</span>
    </div>
    <Link
        href={toUrl(search())}
        class="absolute left-1/2 hidden h-8 w-[min(32rem,42vw)] -translate-x-1/2 items-center gap-2 rounded-md border border-border/80 bg-background/70 px-3 text-sm text-muted-foreground hover:bg-background hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:flex"
        aria-current={page.url.startsWith('/search') ? 'page' : undefined}
        aria-label="Cari"
    >
        <Search class="size-4 shrink-0" aria-hidden="true" />
        <span class="flex-1">Cari</span>
        <kbd class="rounded border border-border px-1.5 py-0.5 text-[10px] font-normal text-muted-foreground">⌘K</kbd>
    </Link>
    <nav class="ml-auto flex items-center gap-1" aria-label="Akses cepat">
        <Link
            href={toUrl(search())}
            class="inline-flex min-h-8 items-center gap-2 rounded-md px-2 text-sm text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:hidden sm:px-2.5"
            aria-current={page.url.startsWith('/search') ? 'page' : undefined}
            aria-label="Cari"
        >
            <Search class="size-4" aria-hidden="true" />
            <span class="hidden sm:inline">Cari</span>
            <kbd class="hidden rounded border border-border px-1.5 py-0.5 text-[10px] font-normal text-muted-foreground sm:inline">⌘K</kbd>
        </Link>
        <div class="relative" data-notification-popover>
            <button
                type="button"
                class="relative inline-flex min-h-8 items-center justify-center rounded-md px-2 text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:px-2.5 {notificationOpen ? 'bg-muted text-foreground' : ''}"
                aria-label={unreadCount > 0 ? `Notifikasi, ${unreadCount} belum dibaca` : 'Notifikasi'}
                aria-expanded={notificationOpen}
                aria-controls="notification-popover"
                aria-haspopup="dialog"
                onclick={() => { notificationOpen = !notificationOpen; }}
            >
                <Bell class="size-5" aria-hidden="true" />
                {#if unreadCount > 0}<span class="absolute right-0.5 top-0.5 inline-flex min-w-4 translate-x-1/4 -translate-y-1/4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold leading-4 text-white ring-2 ring-background">{unreadCount > 99 ? '99+' : unreadCount}</span>{/if}
            </button>
            {#if notificationOpen}
                <div id="notification-popover" role="dialog" aria-label="Notifikasi" class="absolute right-0 top-[calc(100%+0.5rem)] z-50 w-[min(24rem,calc(100vw-1rem))] overflow-hidden rounded-xl border border-border bg-popover text-popover-foreground shadow-lg">
                <div class="px-4 pb-3 pt-4">
                    <h2 class="text-lg font-semibold tracking-tight">Notifikasi</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Aktivitas terbaru yang perlu Anda ketahui.</p>
                </div>
                <div class="h-px bg-border" aria-hidden="true"></div>
                {#if notificationItems.length === 0}
                    <div class="px-4 py-10 text-center text-sm text-muted-foreground">Belum ada notifikasi.</div>
                {:else}
                    <div class="max-h-[min(28rem,calc(100vh-10rem))] overflow-y-auto">
                        {#each notificationItems as notification (notification.id)}
                            <Link
                                href={notification.url}
                                class="group flex min-h-24 items-start gap-3 border-b border-border px-4 py-4 text-left outline-none last:border-b-0 hover:bg-muted/60 focus-visible:bg-muted/60"
                            >
                                <span class="mt-1.5 size-2 shrink-0 rounded-full {notification.read_at ? 'bg-transparent' : 'bg-blue-600'}" aria-hidden="true"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold leading-5 text-foreground">{notification.title}</span>
                                    <span class="mt-1 block text-sm leading-5 text-muted-foreground">{notification.message}</span>
                                    <span class="mt-2 block text-xs text-muted-foreground">{formatDate(notification.created_at, workspaceTimezone)}</span>
                                </span>
                                <ChevronRight class="mt-1 size-5 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                            </Link>
                        {/each}
                    </div>
                {/if}
                <div class="h-px bg-border" aria-hidden="true"></div>
                <Link href={toUrl(notificationsIndex())} onclick={() => { notificationOpen = false; }} class="flex min-h-11 items-center justify-center px-4 text-sm font-medium text-primary hover:bg-muted/60 focus-visible:bg-muted/60">Lihat semua notifikasi</Link>
                </div>
            {/if}
        </div>
    </nav>
</header>
<CommandPalette />
