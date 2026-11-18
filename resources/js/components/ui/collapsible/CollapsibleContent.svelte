<script lang="ts">
    import { Collapsible as CollapsiblePrimitive } from 'bits-ui';
    import type { ComponentProps } from 'svelte';

    let {
        children,
        ...rest
    }: ComponentProps<typeof CollapsiblePrimitive.Content> = $props();
</script>

<CollapsiblePrimitive.Content data-slot="collapsible-content" {...rest}>
    {@render children?.()}
</CollapsiblePrimitive.Content>