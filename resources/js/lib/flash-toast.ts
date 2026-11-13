import { router } from '@inertiajs/svelte';
import { toast } from 'svelte-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        toast[data.type](data.message);
    });

    router.on('networkError', (event) => {
        event.preventDefault();
        toast.error('Koneksi bermasalah. Periksa jaringan Anda lalu coba lagi.');
    });

    router.on('httpException', (event) => {
        event.preventDefault();
        const status = (event as CustomEvent).detail?.response?.status;
        const message = status === 403
            ? 'Anda tidak memiliki izin untuk melakukan tindakan ini.'
            : status === 404
                ? 'Data yang diminta tidak ditemukan atau sudah tidak tersedia.'
                : 'Server gagal memproses permintaan. Coba lagi.';

        toast.error(message);
    });
}
