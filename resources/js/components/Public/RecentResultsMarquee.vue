<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Trophy, Check, Swords } from '@lucide/vue';
import { computed, ref } from 'vue';
import CompetitionSportIcon from '@/components/competitions/CompetitionSportIcon.vue';
import { Badge } from '@/components/ui/badge';
import { show } from '@/routes/public/competitions';

export interface RecentResultItem {
    id: number;
    round: number;
    score_home: number | null;
    score_away: number | null;
    winner_id: number | null;
    win_method: string | null;
    match_type?: string | null;
    competition: {
        id: number;
        name: string;
        slug: string;
        sport: string | null;
        format: string;
    };
    home: {
        id: number;
        name: string;
        short_name?: string | null;
    } | null;
    away: {
        id: number;
        name: string;
        short_name?: string | null;
    } | null;
}

const props = defineProps<{
    results: RecentResultItem[];
}>();

const isPaused = ref(false);

// Duplikasikan list hasil pertandingan agar looping marquee berjalan mulus tanpa celah (seamless infinite scroll)
const repeatedResults = computed(() => {
    if (!props.results || props.results.length === 0) {
        return [];
    }

    // Jika jumlah match sedikit (misal 1-2), duplikasikan lebih banyak agar mengisi lebar layar
    const repeatCount = props.results.length < 3 ? 6 : (props.results.length < 5 ? 4 : 2);
    const list: RecentResultItem[] = [];
    for (let i = 0; i < repeatCount; i++) {
        list.push(...props.results);
    }
    return list;
});
</script>

<template>
    <div v-if="results && results.length > 0" class="w-full overflow-hidden border-y border-border/70 bg-card/70 backdrop-blur-xs py-2 sm:py-2.5 shadow-2xs">
        <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Static Label Badge (Responsive for Mobile & Desktop) -->
                <div class="shrink-0 flex items-center gap-1 sm:gap-1.5 rounded-lg bg-amber-500/10 dark:bg-amber-500/15 border border-amber-500/30 px-2 sm:px-2.5 py-1 text-xs font-black text-amber-700 dark:text-amber-300 shadow-2xs select-none">
                    <Trophy class="size-3.5 text-amber-500 shrink-0" />
                    <span class="hidden sm:inline tracking-wider uppercase text-[11px]">Hasil Terbaru</span>
                    <span class="sm:hidden tracking-wider uppercase text-[10px]">Hasil</span>
                </div>

                <!-- Marquee Container -->
                <div class="relative flex-1 overflow-hidden mask-gradient">
                    <!-- Infinite Scrolling Marquee Track with Touch & Hover Pause -->
                    <div
                        class="marquee-track flex items-center gap-2 sm:gap-3 w-max hover:[animation-play-state:paused] active:[animation-play-state:paused] focus-within:[animation-play-state:paused]"
                        :class="{ 'paused': isPaused }"
                        @touchstart="isPaused = true"
                        @touchend="isPaused = false"
                        @touchcancel="isPaused = false"
                    >
                        <Link
                            v-for="(result, index) in repeatedResults"
                            :key="`${result.id}-${index}`"
                            :href="show(result.competition.slug)"
                            class="group shrink-0 inline-flex items-center gap-1.5 sm:gap-2.5 rounded-xl border border-border/80 bg-background/90 hover:bg-muted/60 hover:border-amber-500/50 px-2.5 sm:px-3 py-1.5 text-xs transition-all shadow-2xs"
                        >
                            <!-- Sport icon & competition name -->
                            <div class="flex items-center gap-1 sm:gap-1.5 text-muted-foreground group-hover:text-foreground">
                                <CompetitionSportIcon :sport="result.competition.sport" class="size-3.5 text-primary shrink-0" />
                                <span class="font-semibold text-[10px] sm:text-[11px] max-w-[80px] sm:max-w-[120px] truncate">
                                    {{ result.competition.name }}
                                </span>
                            </div>

                            <span class="text-border text-[10px]">|</span>

                            <!-- Home Team vs Away Team with Scores -->
                            <div class="flex items-center gap-1 sm:gap-1.5 font-bold text-[11px] sm:text-xs">
                                <!-- Home Team (Uses short_name or truncated full name) -->
                                <span
                                    class="truncate max-w-[70px] sm:max-w-[120px]"
                                    :class="{
                                        'text-emerald-600 dark:text-emerald-400 font-black': result.winner_id && result.home && result.winner_id === result.home.id,
                                        'text-foreground': !result.winner_id || (result.home && result.winner_id !== result.home.id),
                                    }"
                                    :title="result.home?.name"
                                >
                                    {{ result.home?.short_name || result.home?.name || 'TBD' }}
                                </span>

                                <!-- Score Pill -->
                                <div class="inline-flex items-center gap-0.5 rounded-md bg-muted/90 px-1.5 py-0.5 font-mono text-[11px] font-black text-foreground border border-border/60">
                                    <span :class="{ 'text-emerald-600 dark:text-emerald-400': result.winner_id && result.home && result.winner_id === result.home.id }">
                                        {{ result.score_home ?? 0 }}
                                    </span>
                                    <span class="text-muted-foreground text-[9px] mx-0.5">-</span>
                                    <span :class="{ 'text-emerald-600 dark:text-emerald-400': result.winner_id && result.away && result.winner_id === result.away.id }">
                                        {{ result.score_away ?? 0 }}
                                    </span>
                                </div>

                                <!-- Away Team (Uses short_name or truncated full name) -->
                                <span
                                    class="truncate max-w-[70px] sm:max-w-[120px]"
                                    :class="{
                                        'text-emerald-600 dark:text-emerald-400 font-black': result.winner_id && result.away && result.winner_id === result.away.id,
                                        'text-foreground': !result.winner_id || (result.away && result.winner_id !== result.away.id),
                                    }"
                                    :title="result.away?.name"
                                >
                                    {{ result.away?.short_name || result.away?.name || 'TBD' }}
                                </span>
                            </div>

                            <!-- Tie-Break indicator (if any) -->
                            <Badge
                                v-if="result.win_method"
                                variant="outline"
                                class="text-[8px] sm:text-[9px] px-1 sm:px-1.5 py-0 h-4 border-amber-500/40 text-amber-700 dark:text-amber-300 bg-amber-500/10 font-bold shrink-0"
                            >
                                {{ result.win_method }}
                            </Badge>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes marquee-scroll {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}

.marquee-track {
    animation: marquee-scroll 28s linear infinite;
    will-change: transform;
}

.marquee-track.paused {
    animation-play-state: paused;
}

@media (max-width: 640px) {
    .marquee-track {
        animation-duration: 34s; /* slightly slower and smoother on mobile */
    }
}

@media (prefers-reduced-motion: reduce) {
    .marquee-track {
        animation: none;
        overflow-x: auto;
    }
}

/* Gradient fade mask on edges for seamless look */
.mask-gradient {
    mask-image: linear-gradient(to right, transparent, black 1.5%, black 98.5%, transparent);
    -webkit-mask-image: linear-gradient(to right, transparent, black 1.5%, black 98.5%, transparent);
}
</style>
