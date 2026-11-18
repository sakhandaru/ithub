<script lang="ts">
    import { Collapsible as CollapsiblePrimitive } from 'bits-ui';
    import type { ComponentProps } from 'svelte';

    let {
        children,
        ...rest
    }: ComponentProps<typeof CollapsiblePrimitive.Trigger> = $props();
</script>

<CollapsiblePrimitive.Trigger data-slot="collapsible-trigger" {...rest}>
    {@render children?.()}
</CollapsiblePrimitive.Trigger>