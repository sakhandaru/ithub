<script lang="ts">
    import { Collapsible as CollapsiblePrimitive } from 'bits-ui';
    import type { ComponentProps } from 'svelte';

    let {
        open = $bindable(false),
        children,
        ...rest
    }: ComponentProps<typeof CollapsiblePrimitive.Root> = $props();
</script>

<CollapsiblePrimitive.Root bind:open data-slot="collapsible" {...rest}>
    {@render children?.()}
</CollapsiblePrimitive.Root>