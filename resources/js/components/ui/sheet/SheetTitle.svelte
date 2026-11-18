<script lang="ts">
    import { Dialog as DialogPrimitive } from 'bits-ui';
    import type { ComponentProps } from 'svelte';
    import { cn } from '@/lib/utils';

    let { class: className = '', children, ...rest }: ComponentProps<typeof DialogPrimitive.Title> = $props();
</script>

<DialogPrimitive.Title class={cn('text-lg font-semibold text-foreground', className)} {...rest}>
    {@render children?.()}
</DialogPrimitive.Title>
