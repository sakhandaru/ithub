<script lang="ts">
    import { Dialog as DialogPrimitive } from 'bits-ui';
    import type { ComponentProps, Snippet } from 'svelte';

    let { asChild = false, children, ...rest }: Omit<ComponentProps<typeof DialogPrimitive.Trigger>, 'children'> & {
        asChild?: boolean;
        children?: Snippet<[Record<string, any>]>;
    } = $props();
</script>

{#if asChild}
    <DialogPrimitive.Trigger {...rest}>
        {#snippet child({ props })}
            {@render children?.({ ...props, onClick: props.onclick })}
        {/snippet}
    </DialogPrimitive.Trigger>
{:else}
    <DialogPrimitive.Trigger {...rest}>
        {@render children?.({})}
    </DialogPrimitive.Trigger>
{/if}
