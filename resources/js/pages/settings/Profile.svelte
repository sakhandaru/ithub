<script module lang="ts">
    import { edit } from '@/routes/profile';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Pengaturan profil',
                href: edit(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
    import AppHead from '@/components/AppHead.svelte';
    import DeleteUser from '@/components/DeleteUser.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { send } from '@/routes/verification';

    const user = $derived(page.props.auth.user);
</script>

<AppHead title="Pengaturan profil" />

<h1 class="sr-only">Pengaturan profil</h1>

<section class="rounded-lg border border-border/70 bg-card p-4 sm:p-5" aria-label="Profil">
    <Heading
        variant="small"
        title="Profil"
        description="Informasi dasar yang digunakan untuk akun Anda."
    />

    <div class="mt-5 flex items-center gap-3 border-t border-border/70 pt-5">
        <UserAvatar user={user} size="size-8" label={false} />
        <div class="min-w-0"><p class="truncate text-sm font-medium">{user.name}</p><p class="truncate text-xs text-muted-foreground">{user.email}</p></div>
    </div>

    <Form
        {...ProfileController.update.form()}
        class="mt-5 grid gap-4 border-t border-border/70 pt-5"
        options={{ preserveScroll: true }}
    >
        {#snippet children({ errors, processing })}
            <div class="grid gap-2">
                <Label for="avatar">Foto profil</Label>
                <Input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="h-9 cursor-pointer text-sm" />
                <p class="text-xs text-muted-foreground">JPG, PNG, atau WebP hingga 2 MB.</p>
                <InputError class="mt-2" message={errors.avatar} />
            </div>

            <div class="grid gap-2">
                <Label for="name">Nama</Label>
                <Input
                    id="name"
                    name="name"
                    class="mt-1 block w-full"
                    value={user.name}
                    required
                    autocomplete="name"
                    placeholder="Nama lengkap"
                />
                <InputError class="mt-2" message={errors.name} />
            </div>

            <div class="grid gap-2">
                <Label for="email">Alamat email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    class="mt-1 block w-full"
                    value={user.email}
                    required
                    autocomplete="username"
                    placeholder="Alamat email"
                />
                <InputError class="mt-2" message={errors.email} />
            </div>

            {#if Boolean(page.props.mustVerifyEmail) && !user.email_verified_at}
                <div>
                    <p class="-mt-4 text-sm text-muted-foreground">
                        Alamat email Anda belum diverifikasi.
                        <TextLink href={send()} as="button">
                            Kirim ulang email verifikasi.
                        </TextLink>
                    </p>

                    {#if page.props.status === 'verification-link-sent'}
                        <div class="mt-2 text-sm font-medium text-green-600">
                            Link verifikasi baru sudah dikirim ke alamat email Anda.
                        </div>
                    {/if}
                </div>
            {/if}

            <div class="flex items-center justify-end border-t border-border/70 pt-4">
                <Button
                    type="submit"
                    disabled={processing}
                    data-test="update-profile-button">Simpan perubahan</Button
                >
            </div>
        {/snippet}
    </Form>
</section>

<DeleteUser />
