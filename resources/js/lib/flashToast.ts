import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

interface SharedFlash {
    success?: string | null;
    error?: string | null;
    warning?: string | null;
    info?: string | null;
    toast?: FlashToast | null;
}

export function initializeFlashToast(): void {
    router.on('navigate', (event) => {
        const pageProps = (event as any).detail?.page?.props;
        const flash = pageProps?.flash as SharedFlash | undefined;

        if (!flash) {
            return;
        }

        if (flash.toast?.message) {
            const type = flash.toast.type || 'success';
            if (typeof (toast as any)[type] === 'function') {
                (toast as any)[type](flash.toast.message);
            }
        } else if (flash.success) {
            toast.success(flash.success);
        } else if (flash.error) {
            toast.error(flash.error);
        } else if (flash.warning) {
            toast.warning(flash.warning);
        } else if (flash.info) {
            toast.info(flash.info);
        }
    });
}
