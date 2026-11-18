<script lang="ts">
    import { DropdownMenu as DropdownMenuPrimitive } from 'bits-ui';
    import type { ComponentProps } from 'svelte';
    import { cn } from '@/lib/utils';

    let { class: className = '', children, ...rest }: ComponentProps<typeof DropdownMenuPrimitive.Content> = $props();
</script>

<DropdownMenuPrimitive.Portal>
    <DropdownMenuPrimitive.Content
        class={cn('bg-popover text-popover-foreground data-[state=open]:animate-in data-[state=closed]:animate-out z-50 min-w-48 rounded-md border p-1 shadow-md', className)}
        {...rest}
    >
        {@render children?.()}
    </DropdownMenuPrimitive.Content>
</DropdownMenuPrimitive.Portal>
