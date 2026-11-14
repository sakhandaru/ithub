<script lang="ts">
    let {
        class: className = '',
        ...rest
    }: {
        class?: string;
        [key: string]: unknown;
    } = $props();
</script>

<img src="/logo-light.svg" alt="ithub" class={`h-auto w-full object-contain dark:hidden ${className}`} {...rest} />
<img src="/logo-dark.svg" alt="" aria-hidden="true" class={`hidden h-auto w-full object-contain dark:block ${className}`} {...rest} />
