<script lang="ts">
    import { cn } from '@/lib/utils';
    import LoaderCircle from 'carbon-icons-svelte/lib/Renew.svelte';

    let {
        class: className = '',
        ...rest
    }: {
        class?: string;
        [key: string]: unknown;
    } = $props();
</script>

<LoaderCircle class={cn('size-4 animate-spin', className)} {...rest} />
