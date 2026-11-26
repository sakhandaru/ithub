<script module lang="ts">
    import { edit as editAppearance } from '@/routes/appearance';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Pengaturan tampilan',
                href: editAppearance(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppearanceTabs from '@/components/AppearanceTabs.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
</script>

<AppHead title="Pengaturan tampilan" />

<h1 class="sr-only">Pengaturan tampilan</h1>

<section class="rounded-lg border border-border/70 bg-card p-4 sm:p-5" aria-label="Tampilan">
    <Heading
        variant="small"
        title="Tampilan"
        description="Pilih tema yang nyaman untuk bekerja."
    />
    <div class="mt-5 border-t border-border/70 pt-5"><AppearanceTabs /></div>
</section>
