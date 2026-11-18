<script lang="ts">
    import { DropdownMenu as DropdownMenuPrimitive } from 'bits-ui';
    import type { ComponentProps, Snippet } from 'svelte';

    let {
        open = $bindable(false),
        class: className = '',
        children,
        ...rest
    }: Omit<ComponentProps<typeof DropdownMenuPrimitive.Root>, 'children' | 'open'> & {
        open?: boolean;
        class?: string;
        children?: Snippet;
    } = $props();
</script>

<div class={className}>
    <DropdownMenuPrimitive.Root bind:open {...rest}>
        {@render children?.()}
    </DropdownMenuPrimitive.Root>
</div>
