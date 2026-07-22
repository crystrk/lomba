<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Medal } from '@lucide/vue';
import { getInitials } from '@/composables/useInitials';

interface MatchItem {
    id: number;
    round: number;
    leg: number;
    sequence: number;
    home: { id: number; name: string } | null;
    away: { id: number; name: string } | null;
    score_home: number | null;
    score_away: number | null;
    winner_id: number | null;
    status: string;
    win_method: string | null;
    next_match_id: number | null;
    next_slot: number | null;
}

defineProps<{
    sortedRounds: number[];
    matchesByRound: Record<number, Array<MatchItem>>;
    roundLabel: (round: number, leg?: number) => string;
}>();

function scrollToBracketRound(round: number) {
    const el = document.getElementById(`bracket-round-${round}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', inline: 'start', block: 'nearest' });
    }
}
</script>

<template>
    <div class="space-y-4">
        <!-- Quick Jump Round Buttons -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2">
            <span class="text-xs font-semibold text-muted-foreground shrink-0">Jump ke Babak:</span>
            <button
                v-for="round in sortedRounds"
                :key="round"
                type="button"
                @click="scrollToBracketRound(round)"
                class="px-3 py-1 rounded-lg text-xs font-semibold bg-muted hover:bg-accent text-foreground transition-colors shrink-0 border border-border/60"
            >
                {{ roundLabel(round, 1) }}
            </button>
        </div>

        <!-- Scrollable Bracket Tree Canvas -->
        <div class="overflow-x-auto pb-6 pt-2 rounded-2xl border border-border/80 bg-card p-4 sm:p-6">
            <div class="flex gap-8" style="min-width: max-content;">
                <div 
                    v-for="round in sortedRounds" 
                    :key="round" 
                    :id="`bracket-round-${round}`"
                    class="flex flex-col gap-6"
                >
                    <div class="flex items-center gap-2 pb-2 border-b border-border/60">
                        <Medal class="size-4 text-amber-500" />
                        <h3 class="text-sm font-extrabold text-foreground">
                            {{ roundLabel(round, 1) }}
                        </h3>
                        <Badge variant="secondary" class="text-[10px] px-1.5 py-0">
                            {{ matchesByRound[round]?.length }} Match
                        </Badge>
                    </div>

                    <div class="flex-1 flex flex-col justify-around gap-4">
                        <div 
                            v-for="match in matchesByRound[round]" 
                            :key="match.id" 
                            class="w-64 rounded-xl border bg-background p-3 shadow-2xs hover:shadow-xs transition-all space-y-2"
                            :class="match.status === 'completed' ? 'border-border' : 'border-amber-500/30 ring-1 ring-amber-500/20'"
                        >
                            <!-- Bye match -->
                            <template v-if="match.status === 'bye'">
                                <div class="flex items-center justify-between text-[11px] text-muted-foreground font-semibold">
                                    <span>Match #{{ match.sequence }}</span>
                                    <span class="text-amber-600 dark:text-amber-400">BYE</span>
                                </div>
                                <div class="flex items-center gap-2 py-1">
                                    <div class="flex size-6 items-center justify-center rounded-md bg-amber-500/10 text-amber-500 font-bold text-xs">
                                        {{ getInitials(match.home?.name) }}
                                    </div>
                                    <span class="font-bold text-foreground text-xs truncate">{{ match.home?.name || '??' }}</span>
                                </div>
                            </template>

                            <!-- Regular Bracket Match -->
                            <template v-else>
                                <div class="flex items-center justify-between text-[10px] text-muted-foreground font-medium border-b border-border/40 pb-1">
                                    <span>Match #{{ match.sequence }}</span>
                                    <span v-if="match.win_method" class="text-amber-600 font-semibold">{{ match.win_method }}</span>
                                </div>

                                <!-- Home Team -->
                                <div 
                                    class="flex items-center justify-between gap-2 py-1 px-1.5 rounded-md transition-colors"
                                    :class="match.winner_id === match.home?.id ? 'bg-emerald-500/15 font-bold text-emerald-700 dark:text-emerald-300' : ''"
                                >
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="size-2 rounded-full shrink-0" :class="match.winner_id === match.home?.id ? 'bg-emerald-500' : 'bg-muted-foreground/30'" />
                                        <span class="truncate text-xs">{{ match.home?.name || 'Menunggu' }}</span>
                                    </div>
                                    <span v-if="match.score_home !== null" class="text-xs font-mono font-bold">{{ match.score_home }}</span>
                                </div>

                                <div class="border-t border-border/40 my-0.5" />

                                <!-- Away Team -->
                                <div 
                                    class="flex items-center justify-between gap-2 py-1 px-1.5 rounded-md transition-colors"
                                    :class="match.winner_id === match.away?.id ? 'bg-emerald-500/15 font-bold text-emerald-700 dark:text-emerald-300' : ''"
                                >
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="size-2 rounded-full shrink-0" :class="match.winner_id === match.away?.id ? 'bg-emerald-500' : 'bg-muted-foreground/30'" />
                                        <span class="truncate text-xs">{{ match.away?.name || 'Menunggu' }}</span>
                                    </div>
                                    <span v-if="match.score_away !== null" class="text-xs font-mono font-bold">{{ match.score_away }}</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
