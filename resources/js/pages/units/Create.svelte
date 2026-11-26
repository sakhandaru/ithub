<script module lang="ts">
    import { create } from '@/routes/units';

    export const layout = {
        breadcrumbs: [{ title: 'Buat Unit', href: create() }],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import UnitOnboardingController from '@/actions/App/Http/Controllers/UnitOnboardingController';
    import AppHead from '@/components/AppHead.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
</script>

<AppHead title="Buat Unit" />

<div class="mx-auto flex w-full max-w-2xl flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader
        title="Buat Unit pertama"
        description="Unit menjadi ruang organisasi untuk mengelola Project dan pekerjaan tim Anda."
    />

    <section class="rounded-lg border bg-card p-5 sm:p-6">
        <Form {...UnitOnboardingController.store.form()} class="grid gap-6">
            {#snippet children({ errors, processing })}
                <div class="grid gap-2">
                    <Label for="name">Nama Unit</Label>
                    <Input id="name" name="name" required maxlength={80} autocomplete="organization" placeholder="Contoh: Operasional TI" />
                    <p class="text-xs leading-5 text-muted-foreground">Nama Unit dapat diubah dan ditambah lagi setelah setup selesai.</p>
                    <InputError message={errors.name} />
                </div>

                <div class="flex justify-end">
                    <Button type="submit" class="min-h-11" disabled={processing}>
                        {processing ? 'Membuat Unit...' : 'Buat Unit'}
                    </Button>
                </div>
            {/snippet}
        </Form>
    </section>
</div>
