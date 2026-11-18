<script lang="ts">
    import { DropdownMenu as DropdownMenuPrimitive } from 'bits-ui';
    import type { ComponentProps, Snippet } from 'svelte';

    let { asChild = false, children, ...rest }: Omit<ComponentProps<typeof DropdownMenuPrimitive.Trigger>, 'children'> & {
        asChild?: boolean;
        children?: Snippet<[Record<string, any>]>;
    } = $props();
</script>

{#if asChild}
    <DropdownMenuPrimitive.Trigger {...rest}>
        {#snippet child({ props })}
            {@render children?.({ ...props, onClick: props.onclick })}
        {/snippet}
    </DropdownMenuPrimitive.Trigger>
{:else}
    <DropdownMenuPrimitive.Trigger {...rest}>
        {@render children?.({})}
    </DropdownMenuPrimitive.Trigger>
{/if}
