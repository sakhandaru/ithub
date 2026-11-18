<script lang="ts">
    import { Dialog as DialogPrimitive } from 'bits-ui';
    import type { ComponentProps, Snippet } from 'svelte';

    let { asChild = false, children, ...rest }: Omit<ComponentProps<typeof DialogPrimitive.Close>, 'children'> & {
        asChild?: boolean;
        children?: Snippet<[Record<string, any>]>;
    } = $props();
</script>

{#if asChild}
    <DialogPrimitive.Close {...rest}>
        {#snippet child({ props })}
            {@render children?.({ ...props, onClick: props.onclick })}
        {/snippet}
    </DialogPrimitive.Close>
{:else}
    <DialogPrimitive.Close {...rest}>
        {@render children?.({})}
    </DialogPrimitive.Close>
{/if}
