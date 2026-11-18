<script lang="ts">
    import { Dialog as DialogPrimitive } from 'bits-ui';
    import type { ComponentProps } from 'svelte';
    import { cn } from '@/lib/utils';

    let {
        side = 'right',
        class: className = '',
        children,
        ...rest
    }: ComponentProps<typeof DialogPrimitive.Content> & {
        side?: 'right' | 'left' | 'top' | 'bottom';
    } = $props();

    const sideClasses: Record<string, string> = {
        right: 'inset-y-0 right-0',
        left: 'inset-y-0 left-0',
        top: 'inset-x-0 top-0',
        bottom: 'inset-x-0 bottom-0',
    };

    const sizeClasses: Record<string, string> = {
        right: 'h-full w-3/4 sm:max-w-sm',
        left: 'h-full w-3/4 sm:max-w-sm',
        top: 'h-auto',
        bottom: 'h-auto',
    };
</script>

<DialogPrimitive.Portal>
    <DialogPrimitive.Overlay class="fixed inset-0 z-50 bg-black/50" />
    <DialogPrimitive.Content
        class={cn(
            'fixed z-50 flex flex-col gap-4 overflow-y-auto border-none bg-background p-6 shadow-lg',
            sideClasses[side],
            sizeClasses[side],
            className,
        )}
        {...rest}
    >
        {@render children?.()}
    </DialogPrimitive.Content>
</DialogPrimitive.Portal>
