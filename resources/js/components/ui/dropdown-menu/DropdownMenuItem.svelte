<script lang="ts">
    import { DropdownMenu as DropdownMenuPrimitive } from 'bits-ui';
    import type { ComponentProps, Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    let { asChild = false, class: className = '', children, ...rest }: Omit<ComponentProps<typeof DropdownMenuPrimitive.Item>, 'children'> & {
        asChild?: boolean;
        children?: Snippet<[Record<string, any>]>;
    } = $props();

    const classes = () => cn(
        'flex w-full cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground',
        className,
    );
</script>

{#if asChild}
    <DropdownMenuPrimitive.Item class={classes()} {...rest}>
        {#snippet child({ props })}
            {@render children?.({ ...props, onClick: props.onclick })}
        {/snippet}
    </DropdownMenuPrimitive.Item>
{:else}
    <DropdownMenuPrimitive.Item class={classes()} {...rest}>
        {@render children?.({})}
    </DropdownMenuPrimitive.Item>
{/if}
