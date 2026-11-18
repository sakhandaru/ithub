<script lang="ts">
    import { Dialog as DialogPrimitive } from 'bits-ui';
    import type { ComponentProps } from 'svelte';

    let { children, ...rest }: ComponentProps<typeof DialogPrimitive.Title> = $props();
</script>

<DialogPrimitive.Title class="text-lg font-semibold leading-none tracking-tight" {...rest}>
    {@render children?.()}
</DialogPrimitive.Title>
