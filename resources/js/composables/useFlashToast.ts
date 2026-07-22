import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { toast } from 'vue-sonner';

interface FlashData {
    success?: string | null;
    error?: string | null;
    warning?: string | null;
    info?: string | null;
    toast?: { type: 'success' | 'info' | 'warning' | 'error'; message: string } | null;
}

export function useFlashToast(): void {
    const page = usePage();

    watch(
        () => ({ ...(page.props.flash as FlashData) }),
        (flash) => {
            if (!flash) return;

            if (flash.toast?.message) {
                const type = flash.toast.type || 'success';
                toast[type](flash.toast.message);
            } else if (flash.success) {
                toast.success(flash.success);
            } else if (flash.error) {
                toast.error(flash.error);
            } else if (flash.warning) {
                toast.warning(flash.warning);
            } else if (flash.info) {
                toast.info(flash.info);
            }
        },
    );
}
