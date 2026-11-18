<script lang="ts">
    import { Dialog as DialogPrimitive } from 'bits-ui';
    import type { ComponentProps } from 'svelte';

    let { children, ...rest }: ComponentProps<typeof DialogPrimitive.Description> = $props();
</script>

<DialogPrimitive.Description class="text-sm text-muted-foreground" {...rest}>
    {@render children?.()}
</DialogPrimitive.Description>
