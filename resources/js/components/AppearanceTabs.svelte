<script lang="ts">
    import Monitor from 'carbon-icons-svelte/lib/SettingsView.svelte';
    import Moon from 'carbon-icons-svelte/lib/Moon.svelte';
    import Sun from 'carbon-icons-svelte/lib/Sun.svelte';
    import type { Component, SvelteComponent } from 'svelte';
    import { themeState } from '@/lib/theme.svelte';
    import type { Appearance } from '@/types';

    const { appearance, updateAppearance } = themeState();

    type IconComponent =
        | Component<{ class?: string }>
        | (new (...args: any[]) => SvelteComponent<{ class?: string }>);

    const tabs: { value: Appearance; Icon: IconComponent; label: string }[] = [
        { value: 'light', Icon: Sun, label: 'Terang' },
        { value: 'dark', Icon: Moon, label: 'Gelap' },
        { value: 'system', Icon: Monitor, label: 'Sistem' },
    ];

    function handleAppearanceChange(value: Appearance) {
        updateAppearance(value);
    }
</script>

<div class="inline-flex max-w-full flex-wrap gap-1 rounded-md border border-border/80 bg-muted/40 p-1">
    {#each tabs as { value, Icon, label } (value)}
        <button
            onclick={() => handleAppearanceChange(value)}
            class="flex min-h-9 items-center rounded-md px-3.5 py-1.5 text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {appearance.value ===
            value
                ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                : 'text-muted-foreground hover:bg-background hover:text-foreground'}"
            aria-pressed={appearance.value === value}
        >
            <Icon class="-ml-1 h-4 w-4" />
            <span class="ml-1.5">{label}</span>
        </button>
    {/each}
</div>
