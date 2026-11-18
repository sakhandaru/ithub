<script lang="ts">
    import { Checkbox as CheckboxPrimitive } from 'bits-ui';
    import type { ComponentProps } from 'svelte';
    import { cn } from '@/lib/utils';
    import Check from 'carbon-icons-svelte/lib/Checkmark.svelte';

    let {
        checked = $bindable(false),
        class: className = '',
        children,
        ...rest
    }: ComponentProps<typeof CheckboxPrimitive.Root> = $props();
</script>

<CheckboxPrimitive.Root
    bind:checked
    data-slot="checkbox"
    class={cn(
        'peer border-input data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground data-[state=checked]:border-primary focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive size-4 shrink-0 rounded-lg border shadow-xs transition-shadow outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
        className,
    )}
    {...rest}
>
    {#snippet children({ checked: isChecked })}
        {#if isChecked}
            <span data-slot="checkbox-indicator" class="grid place-content-center text-current transition-none">
                <Check class="size-3.5" />
            </span>
        {/if}
    {/snippet}
</CheckboxPrimitive.Root>
