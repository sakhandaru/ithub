<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import BriefcaseBusiness from '@lucide/svelte/icons/briefcase-business';
    import FolderKanban from '@lucide/svelte/icons/folder-kanban';
    import LayoutDashboard from '@lucide/svelte/icons/layout-dashboard';
    import Palette from '@lucide/svelte/icons/palette';
    import Settings from '@lucide/svelte/icons/settings';
    import ShieldCheck from '@lucide/svelte/icons/shield-check';
    import UserRound from '@lucide/svelte/icons/user-round';
    import type { Snippet } from 'svelte';
    import NavUser from '@/components/NavUser.svelte';
    import { Sidebar, useSidebar } from '@/components/ui/sidebar';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import { edit as editAppearance } from '@/routes/appearance';
    import { edit as editProfile } from '@/routes/profile';
    import { edit as editSecurity } from '@/routes/security';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const url = currentUrlState();
    const { isMobile, setOpenMobile } = useSidebar();
    const isSettings = $derived(url.currentUrl.startsWith('/settings'));

    function closeMobileNavigation(): void {
        if ($isMobile) {
            setOpenMobile(false);
        }
    }
</script>

<Sidebar collapsible="icon" variant="sidebar" class="overflow-hidden">
    <div class="flex h-full min-w-0">
        <nav
            class="flex w-14 shrink-0 flex-col items-center border-r border-sidebar-border bg-sidebar py-2"
            aria-label="Area utama"
        >
            <Link
                href={toUrl(dashboard())}
                class="mb-3 flex size-11 items-center justify-center rounded-md text-sm font-bold tracking-tight text-sidebar-foreground hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                aria-label="Beranda sitop"
                onclick={closeMobileNavigation}
            >
                ST
            </Link>

            <div class="flex w-full flex-1 flex-col items-center gap-1">
                <Link
                    href={toUrl(dashboard())}
                    class="relative flex size-11 items-center justify-center rounded-md hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isSettings
                        ? 'text-muted-foreground'
                        : 'bg-sidebar-accent text-sidebar-foreground'}"
                    aria-label="Pekerjaan"
                    aria-current={!isSettings ? 'page' : undefined}
                    title="Pekerjaan"
                    onclick={closeMobileNavigation}
                >
                    {#if !isSettings}
                        <span
                            class="absolute inset-y-2 left-0 w-0.5 rounded-r bg-sidebar-foreground"
                            aria-hidden="true"
                        ></span>
                    {/if}
                    <BriefcaseBusiness class="size-5" aria-hidden="true" />
                    <span class="sr-only">Pekerjaan</span>
                </Link>
            </div>

            <Link
                href={toUrl(editProfile())}
                class="relative flex size-11 items-center justify-center rounded-md hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isSettings
                    ? 'bg-sidebar-accent text-sidebar-foreground'
                    : 'text-muted-foreground'}"
                aria-label="Pengaturan"
                aria-current={isSettings ? 'page' : undefined}
                title="Pengaturan"
                onclick={closeMobileNavigation}
            >
                {#if isSettings}
                    <span
                        class="absolute inset-y-2 left-0 w-0.5 rounded-r bg-sidebar-foreground"
                        aria-hidden="true"
                    ></span>
                {/if}
                <Settings class="size-5" aria-hidden="true" />
                <span class="sr-only">Pengaturan</span>
            </Link>
        </nav>

        <div
            class="flex min-w-0 flex-1 flex-col bg-sidebar group-data-[collapsible=icon]:hidden"
        >
            <div class="flex h-12 shrink-0 items-center border-b border-sidebar-border px-4">
                <span class="truncate text-sm font-semibold tracking-tight">sitop</span>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-3 py-4">
                {#if isSettings}
                    <p class="px-2 pb-2 text-xs font-medium text-muted-foreground">
                        Pengaturan akun
                    </p>
                    <nav class="grid gap-1" aria-label="Pengaturan akun">
                        <Link
                            href={toUrl(editProfile())}
                            class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {url.isCurrentUrl(
                                editProfile(),
                                url.currentUrl,
                            )
                                ? 'bg-sidebar-accent font-medium'
                                : 'text-muted-foreground'}"
                            aria-current={url.isCurrentUrl(editProfile(), url.currentUrl)
                                ? 'page'
                                : undefined}
                            onclick={closeMobileNavigation}
                        >
                            <UserRound class="size-4" aria-hidden="true" />
                            Profil
                        </Link>
                        <Link
                            href={toUrl(editSecurity())}
                            class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {url.isCurrentUrl(
                                editSecurity(),
                                url.currentUrl,
                            )
                                ? 'bg-sidebar-accent font-medium'
                                : 'text-muted-foreground'}"
                            aria-current={url.isCurrentUrl(editSecurity(), url.currentUrl)
                                ? 'page'
                                : undefined}
                            onclick={closeMobileNavigation}
                        >
                            <ShieldCheck class="size-4" aria-hidden="true" />
                            Keamanan
                        </Link>
                        <Link
                            href={toUrl(editAppearance())}
                            class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {url.isCurrentUrl(
                                editAppearance(),
                                url.currentUrl,
                            )
                                ? 'bg-sidebar-accent font-medium'
                                : 'text-muted-foreground'}"
                            aria-current={url.isCurrentUrl(editAppearance(), url.currentUrl)
                                ? 'page'
                                : undefined}
                            onclick={closeMobileNavigation}
                        >
                            <Palette class="size-4" aria-hidden="true" />
                            Tampilan
                        </Link>
                    </nav>
                {:else}
                    <p class="px-2 pb-2 text-xs font-medium text-muted-foreground">
                        Pekerjaan
                    </p>
                    <nav class="grid gap-1" aria-label="Pekerjaan">
                        <Link
                            href={toUrl(dashboard())}
                            class="flex min-h-10 items-center gap-3 rounded-md bg-sidebar-accent px-2.5 text-sm font-medium focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                            aria-current="page"
                            onclick={closeMobileNavigation}
                        >
                            <LayoutDashboard class="size-4" aria-hidden="true" />
                            Beranda
                        </Link>
                    </nav>

                    <div class="mt-7">
                        <p class="px-2 pb-2 text-xs font-medium text-muted-foreground">
                            Workspace
                        </p>
                        <div class="flex items-start gap-3 px-2.5 py-2 text-muted-foreground">
                            <FolderKanban class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                            <p class="text-xs leading-5">Belum ada project.</p>
                        </div>
                    </div>
                {/if}
            </div>

            <div class="border-t border-sidebar-border p-2">
                <NavUser />
            </div>
        </div>
    </div>
</Sidebar>
{@render children?.()}
