<script setup lang="ts">
import { Heart } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import PublicTopNav from '@/components/PublicTopNav.vue';
import { Toaster } from '@/components/ui/sonner';
import { useFlashToast } from '@/composables/useFlashToast';

useFlashToast();

defineProps<{
    competitionName?: string;
    competitionSlug?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-[#FDFDFC] font-sans text-foreground selection:bg-amber-500 selection:text-white dark:bg-[#0a0a0a]"
    >
        <!-- Top Navigation -->
        <PublicTopNav
            :competition-name="competitionName"
            :competition-slug="competitionSlug"
        />

        <!-- Main Content Area -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <footer
            class="mt-auto border-t border-border/60 bg-muted/30 px-4 py-8 sm:px-6 lg:px-8"
        >
            <div
                class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 text-center text-xs text-muted-foreground md:flex-row md:text-left"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="flex size-6 items-center justify-center rounded-md bg-amber-500/10 text-amber-500"
                    >
                        <AppLogoIcon class="size-4" />
                    </div>
                    <span class="font-semibold text-foreground"
                        >Arena Lomba</span
                    >
                    <span>&bull;</span>
                    <span>Sistem Manajemen & Turnamen Live</span>
                </div>

                <div class="flex items-center gap-1">
                    <span>Dibuat dengan</span>
                    <Heart
                        class="inline size-3.5 fill-rose-500 text-rose-500"
                    />
                    <span
                        >untuk mendukung kegiatan kompetisi yang transparan &
                        realtime.</span
                    >
                </div>

                <div>
                    &copy; {{ new Date().getFullYear() }} Arena Lomba. Hak cipta
                    dilindungi.
                </div>
            </div>
        </footer>
        <Toaster rich-colors />
    </div>
</template>
