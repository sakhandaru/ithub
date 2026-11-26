<script lang="ts">
    import { onMount } from 'svelte';

    let { completed, total, segments = 30 }: { completed: number; total: number; segments?: number } = $props();

    let container: HTMLSpanElement;
    let segmentCount = $state(30);
    const progress = $derived(total > 0 ? Math.min(100, Math.max(0, Math.round((completed / total) * 100))) : 0);

    onMount(() => {
        const resizeObserver = new ResizeObserver(([entry]) => {
            const barWidth = 4;
            const gap = 2;
            const availableWidth = entry.contentRect.width;

            segmentCount = Math.max(1, Math.floor((availableWidth + gap) / (barWidth + gap)));
        });

        resizeObserver.observe(container);

        return () => resizeObserver.disconnect();
    });
</script>

<span bind:this={container} class="flex min-w-0 w-full gap-0.5 overflow-hidden" role="img" aria-label={`Progress ${progress}%`}>
    {#each Array.from({ length: segmentCount }) as _, index}
        <span class="h-6 w-1 shrink-0 rounded-sm {index < Math.round((progress / 100) * segmentCount) ? 'bg-emerald-500' : 'bg-muted'}" aria-hidden="true"></span>
    {/each}
</span>
