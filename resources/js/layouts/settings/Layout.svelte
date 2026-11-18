<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import UserRound from 'carbon-icons-svelte/lib/UserAvatarFilled.svelte';
    import ShieldCheck from 'carbon-icons-svelte/lib/Security.svelte';
    import SunMedium from 'carbon-icons-svelte/lib/Sun.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { toUrl } from '@/lib/utils';
    import { edit as editAppearance } from '@/routes/appearance';
    import { edit as editProfile } from '@/routes/profile';
    import { edit as editSecurity } from '@/routes/security';
    import type { NavItem } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const sidebarNavItems: NavItem[] = [
        {
            title: 'Profil',
            href: editProfile(),
            icon: UserRound,
        },
        {
            title: 'Keamanan',
            href: editSecurity(),
            icon: ShieldCheck,
        },
        {
            title: 'Tampilan',
            href: editAppearance(),
            icon: SunMedium,
        },
    ];

    const url = currentUrlState();
</script>

<div class="flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader title="Pengaturan" description="Kelola profil, keamanan, dan preferensi tampilan akun Anda." />

    <div class="grid min-w-0 gap-6 lg:grid-cols-[13rem_minmax(0,42rem)] lg:items-start lg:gap-10">
        <aside class="min-w-0">
            <nav class="flex gap-1 overflow-x-auto lg:grid lg:gap-1" aria-label="Pengaturan akun">
                {#each sidebarNavItems as item (toUrl(item.href))}
                    <Link href={toUrl(item.href)} class="inline-flex min-h-10 shrink-0 items-center gap-2 rounded-md px-3 text-sm transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {url.isCurrentUrl(item.href, url.currentUrl) ? 'bg-accent font-medium text-accent-foreground' : 'text-muted-foreground'}">
                        <item.icon class="size-4" />
                        {item.title}
                    </Link>
                {/each}
            </nav>
        </aside>

        <main class="min-w-0 space-y-5">
            {@render children?.()}
        </main>
    </div>
</div>
