<script lang="ts">
    import { DropdownMenu as DropdownMenuPrimitive } from 'bits-ui';
    import type { ComponentProps } from 'svelte';
    import { cn } from '@/lib/utils';

    let { class: className = '', children, ...rest }: ComponentProps<typeof DropdownMenuPrimitive.GroupHeading> = $props();
</script>

<DropdownMenuPrimitive.GroupHeading class={cn('px-2 py-1.5 text-sm font-semibold', className)} {...rest}>
    {@render children?.()}
</DropdownMenuPrimitive.GroupHeading>
