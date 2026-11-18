<script lang="ts">
    import { DropdownMenu as DropdownMenuPrimitive } from 'bits-ui';
    import type { ComponentProps } from 'svelte';

    let { children, ...rest }: ComponentProps<typeof DropdownMenuPrimitive.Group> = $props();
</script>

<DropdownMenuPrimitive.Group {...rest}>
    {@render children?.()}
</DropdownMenuPrimitive.Group>
