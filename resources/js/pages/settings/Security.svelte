<script module lang="ts">
    import { edit } from '@/routes/security';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Pengaturan keamanan',
                href: edit(),
            },
        ],
    };
</script>

<script lang="ts">
    import {
        Form ,
        page,
    } from '@inertiajs/svelte';
    import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import ManagePasskeys from '@/components/ManagePasskeys.svelte';
    import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.svelte';
    const canManagePasskeys = $derived(Boolean(page.props.canManagePasskeys));
    const passkeys = $derived(
        (Array.isArray(page.props.passkeys)
            ? page.props.passkeys
            : []) as ManagePasskeysProps['passkeys'],
    );

    let { passwordRules }: { passwordRules: string } = $props();
</script>

<AppHead title="Pengaturan keamanan" />

<h1 class="sr-only">Pengaturan keamanan</h1>

<section class="rounded-lg border border-border/70 bg-card p-4 sm:p-5" aria-label="Kata sandi">
    <Heading
        variant="small"
        title="Kata sandi"
        description="Gunakan kata sandi yang panjang dan unik untuk menjaga akun tetap aman."
    />

    <Form
        {...SecurityController.update.form()}
        class="grid gap-4"
        options={{ preserveScroll: true }}
        resetOnSuccess
        resetOnError={['password', 'password_confirmation', 'current_password']}
    >
        {#snippet children({ errors, processing })}
            <div class="grid gap-2">
                <Label for="current_password">Kata sandi saat ini</Label>
                <PasswordInput
                    id="current_password"
                    name="current_password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    placeholder="Kata sandi saat ini"
                />
                <InputError message={errors.current_password} />
            </div>

            <div class="grid gap-2">
                <Label for="password">Kata sandi baru</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Kata sandi baru"
                    passwordrules={passwordRules}
                />
                <InputError message={errors.password} />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Konfirmasi kata sandi</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Konfirmasi kata sandi"
                    passwordrules={passwordRules}
                />
                <InputError message={errors.password_confirmation} />
            </div>

            <div class="flex items-center justify-end border-t border-border/70 pt-4">
                <Button
                    type="submit"
                    disabled={processing}
                    data-test="update-password-button"
                >
                    Simpan kata sandi
                </Button>
            </div>
        {/snippet}
    </Form>
</section>


<ManagePasskeys {canManagePasskeys} {passkeys} />
