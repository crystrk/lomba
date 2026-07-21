<script setup lang="ts">
import PublicTopNav from '@/components/PublicTopNav.vue';
import { Trophy, Heart } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

defineProps<{
    competitionName?: string;
    competitionSlug?: string;
}>();
</script>

<template>
    <div class="min-h-screen flex flex-col bg-[#FDFDFC] dark:bg-[#0a0a0a] text-foreground font-sans selection:bg-amber-500 selection:text-white">
        <!-- Top Navigation -->
        <PublicTopNav :competition-name="competitionName" :competition-slug="competitionSlug" />

        <!-- Main Content Area -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="mt-auto border-t border-border/60 bg-muted/30 py-8 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl flex flex-col md:flex-row items-center justify-between gap-4 text-center md:text-left text-xs text-muted-foreground">
                <div class="flex items-center gap-2">
                    <div class="flex size-6 items-center justify-center rounded-md bg-amber-500/10 text-amber-500">
                        <Trophy class="size-3.5" />
                    </div>
                    <span class="font-semibold text-foreground">Arena Lomba</span>
                    <span>&bull;</span>
                    <span>Sistem Manajemen & Turnamen Live</span>
                </div>

                <div class="flex items-center gap-1">
                    <span>Dibuat dengan</span>
                    <Heart class="size-3.5 text-rose-500 fill-rose-500 inline" />
                    <span>untuk mendukung kegiatan kompetisi yang transparan & realtime.</span>
                </div>

                <div>
                    &copy; {{ new Date().getFullYear() }} Arena Lomba. Hak cipta dilindungi.
                </div>
            </div>
        </footer>
    </div>
</template>
