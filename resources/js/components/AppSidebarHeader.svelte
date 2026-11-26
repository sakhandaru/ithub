<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import Search from 'carbon-icons-svelte/lib/Search.svelte';
    import ArrowLeft from 'carbon-icons-svelte/lib/ArrowLeft.svelte';
    import ArrowRight from 'carbon-icons-svelte/lib/ArrowRight.svelte';
    import Bell from 'carbon-icons-svelte/lib/Notification.svelte';
    import ChevronRight from 'carbon-icons-svelte/lib/ChevronRight.svelte';
    import Moon from 'carbon-icons-svelte/lib/Moon.svelte';
    import Renew from 'carbon-icons-svelte/lib/Renew.svelte';
    import Sun from 'carbon-icons-svelte/lib/Sun.svelte';
    import Logout from 'carbon-icons-svelte/lib/Logout.svelte';
    import UserAvatar from 'carbon-icons-svelte/lib/UserAvatar.svelte';
    import CommandPalette from '@/components/CommandPalette.svelte';
    import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
    import { formatDate } from '@/lib/format-date';
    import AppLogo from '@/components/AppLogo.svelte';
    import { dashboard, logout } from '@/routes';
    import { edit as editProfile } from '@/routes/profile';
    import { index as notificationsIndex } from '@/routes/notifications';
    import { toUrl } from '@/lib/utils';
    import { themeState } from '@/lib/theme.svelte';
    import { getInitials } from '@/lib/initials';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
    }: {
        breadcrumbs?: BreadcrumbItem[];
    } = $props();

    const area = $derived(page.url.startsWith('/settings') ? 'Pengaturan' : 'Pekerjaan');
    const auth = $derived(page.props.auth);
    const activeUnitRole = $derived(page.props.unitContext?.active?.role_label);
    const unreadCount = $derived(page.props.notifications?.unread_count ?? 0);
    const notificationItems = $derived(page.props.notifications?.items ?? []);
    const workspaceTimezone = $derived(page.props.unitContext?.active ? page.props.workspaceContext?.active?.timezone : undefined);
    let notificationOpen = $state(false);
    let profileMenuOpen = $state(false);
    let searchOpen = $state(false);
    const theme = themeState();
    const isDark = $derived(theme.resolvedAppearance() === 'dark');

    function navigateBack(): void {
        window.history.back();
    }

    function navigateForward(): void {
        window.history.forward();
    }

    function hardRefresh(): void {
        window.location.reload();
    }

    function toggleTheme(): void {
        theme.updateAppearance(isDark ? 'light' : 'dark');
    }

    onMount(() => {
        const closeOnOutsideClick = (event: MouseEvent): void => {
            if (!(event.target instanceof Element) || !event.target.closest('[data-notification-popover]')) {
                notificationOpen = false;
            }
            if (!(event.target instanceof Element) || !event.target.closest('[data-profile-menu]')) {
                profileMenuOpen = false;
            }
        };
        const closeOnEscape = (event: KeyboardEvent): void => {
            if (event.key === 'Escape') {
                notificationOpen = false;
                profileMenuOpen = false;
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

<header class="relative flex h-10 shrink-0 items-center gap-2 bg-muted px-3 sm:gap-3 sm:px-4">
    <div class="flex min-w-0 items-center gap-2 sm:gap-2.5">
        <Link href={toUrl(dashboard())} class="hidden shrink-0 items-center rounded-md py-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:ml-9 sm:flex" aria-label="Beranda ithub">
            <AppLogo />
        </Link>
        <span class="text-sm font-medium sm:hidden">{area}</span>
    </div>
    <div class="hidden min-w-0 flex-1 items-center justify-center gap-2 sm:flex">
        <div class="flex h-8 shrink-0 items-center gap-0.5 rounded-md border border-border/70 bg-background/80 p-0.5">
            <button type="button" class="inline-flex size-6 items-center justify-center rounded-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label="Kembali" title="Kembali" onclick={navigateBack}><ArrowLeft class="size-3.5" aria-hidden="true" /></button>
            <button type="button" class="inline-flex size-6 items-center justify-center rounded-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label="Maju" title="Maju" onclick={navigateForward}><ArrowRight class="size-3.5" aria-hidden="true" /></button>
        </div>
        <button type="button" class="h-8 w-[min(30rem,38vw)] shrink rounded-md border border-border/70 bg-background/80 px-3 text-left text-sm text-muted-foreground hover:bg-background hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label="Cari" onclick={() => { searchOpen = true; }}>
            <span class="flex h-full items-center gap-2"><Search class="size-4 shrink-0" aria-hidden="true" /><span class="flex-1">Cari</span><kbd class="rounded border border-border px-1.5 py-0.5 text-[10px] font-normal text-muted-foreground">⌘K</kbd></span>
        </button>
        <div class="flex h-8 shrink-0 items-center gap-0.5 rounded-md border border-border/70 bg-background/80 p-0.5">
            <button type="button" class="inline-flex size-6 items-center justify-center rounded-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label="Muat ulang halaman" title="Muat ulang halaman" onclick={hardRefresh}><Renew class="size-3.5" aria-hidden="true" /></button>
            <button type="button" class="inline-flex size-6 items-center justify-center rounded-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label={isDark ? 'Gunakan mode terang' : 'Gunakan mode gelap'} title={isDark ? 'Mode terang' : 'Mode gelap'} onclick={toggleTheme}>{#if isDark}<Sun class="size-3.5" aria-hidden="true" />{:else}<Moon class="size-3.5" aria-hidden="true" />{/if}</button>
        </div>
    </div>
    <nav class="ml-auto flex items-center gap-1" aria-label="Akses cepat">
        <button type="button" class="inline-flex min-h-8 items-center gap-2 rounded-md px-2 text-sm text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:hidden sm:px-2.5" aria-label="Cari" onclick={() => { searchOpen = true; }}>
            <Search class="size-4" aria-hidden="true" />
        </button>
        <div class="relative" data-notification-popover>
            <button
                type="button"
                class="relative inline-flex size-7 items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {notificationOpen ? 'bg-muted text-foreground' : ''}"
                aria-label={unreadCount > 0 ? `Notifikasi, ${unreadCount} belum dibaca` : 'Notifikasi'}
                aria-expanded={notificationOpen}
                aria-controls="notification-popover"
                aria-haspopup="dialog"
                onclick={() => { notificationOpen = !notificationOpen; }}
            >
                <Bell class="size-4.5" aria-hidden="true" />
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
        <div class="relative" data-profile-menu>
            <button
                type="button"
                class="relative inline-flex size-7 items-center justify-center rounded-full p-0.5 text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                aria-label="Menu akun"
                aria-expanded={profileMenuOpen}
                aria-controls="profile-menu"
                onclick={() => { profileMenuOpen = !profileMenuOpen; notificationOpen = false; }}
            >
                <Avatar class="size-6 overflow-hidden rounded-full">
                    {#if auth.user?.avatar}
                        <AvatarImage src={auth.user.avatar} alt={auth.user.name} />
                    {/if}
                    <AvatarFallback class="rounded-full bg-neutral-200 text-xs font-semibold text-black dark:bg-neutral-700 dark:text-white">
                        {getInitials(auth.user?.name ?? '')}
                    </AvatarFallback>
                </Avatar>
            </button>
            {#if profileMenuOpen && auth.user}
                <div id="profile-menu" role="menu" aria-label="Menu akun" class="absolute right-0 top-[calc(100%+0.5rem)] z-50 w-56 overflow-hidden rounded-xl border border-border bg-popover p-1 text-popover-foreground shadow-lg">
                    <div class="flex items-center gap-2 px-3 py-2.5">
                        <Avatar class="size-8 overflow-hidden rounded-full">
                            {#if auth.user.avatar}<AvatarImage src={auth.user.avatar} alt={auth.user.name} />{/if}
                            <AvatarFallback class="rounded-full bg-neutral-200 text-xs font-semibold text-black dark:bg-neutral-700 dark:text-white">{getInitials(auth.user.name)}</AvatarFallback>
                        </Avatar>
                        <div class="min-w-0 text-left">
                            <p class="truncate text-sm font-medium">{auth.user.name}</p>
                            <p class="truncate text-xs text-muted-foreground">{auth.user.email}</p>
                            {#if activeUnitRole}<p class="truncate text-xs text-muted-foreground">{activeUnitRole}</p>{/if}
                        </div>
                    </div>
                    <div class="my-1 h-px bg-border"></div>
                    <Link href={toUrl(editProfile())} prefetch class="flex min-h-9 items-center gap-2 rounded-md px-3 text-sm hover:bg-muted focus-visible:bg-muted" role="menuitem" onclick={() => { profileMenuOpen = false; }}>
                        <UserAvatar class="size-4" aria-hidden="true" />
                        Profil
                    </Link>
                    <Link href={logout()} as="button" class="flex min-h-9 w-full items-center gap-2 rounded-md px-3 text-left text-sm hover:bg-muted focus-visible:bg-muted" role="menuitem" onclick={() => { profileMenuOpen = false; }}>
                        <Logout class="size-4" aria-hidden="true" />
                        Keluar
                    </Link>
                </div>
            {/if}
        </div>
    </nav>
</header>
<CommandPalette bind:open={searchOpen} />
