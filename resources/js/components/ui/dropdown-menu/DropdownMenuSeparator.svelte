<script lang="ts">
    import { DropdownMenu as DropdownMenuPrimitive } from 'bits-ui';
    import type { ComponentProps } from 'svelte';

    let { ...rest }: ComponentProps<typeof DropdownMenuPrimitive.Separator> = $props();
</script>

<DropdownMenuPrimitive.Separator class="my-1 h-px bg-border" {...rest} />
