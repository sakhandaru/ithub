<script lang="ts">
    import { Dialog as DialogPrimitive } from 'bits-ui';
    import type { ComponentProps, Snippet } from 'svelte';

    let {
        open = $bindable(false),
        onOpenChange,
        children,
        ...rest
    }: Omit<ComponentProps<typeof DialogPrimitive.Root>, 'children' | 'open' | 'onOpenChange'> & {
        open?: boolean;
        onOpenChange?: (open: boolean) => void;
        children?: Snippet;
    } = $props();
</script>

<DialogPrimitive.Root bind:open {onOpenChange} {...rest}>
    {@render children?.()}
</DialogPrimitive.Root>