<script lang="ts">
    import { Dialog as DialogPrimitive } from 'bits-ui';
    import type { ComponentProps } from 'svelte';
    import { cn } from '@/lib/utils';

    let { class: className = '', children, ...rest }: ComponentProps<typeof DialogPrimitive.Content> = $props();
</script>

<DialogPrimitive.Portal>
    <DialogPrimitive.Overlay class="fixed inset-0 z-50 bg-black/50" />
    <DialogPrimitive.Content
        class={cn('fixed left-1/2 top-1/2 z-50 w-full max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-lg border bg-background p-6 shadow-lg', className)}
        {...rest}
    >
        {@render children?.()}
    </DialogPrimitive.Content>
</DialogPrimitive.Portal>