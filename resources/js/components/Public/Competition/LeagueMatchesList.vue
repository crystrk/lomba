<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Swords } from '@lucide/vue';
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
    selectedRound: number | 'all';
    statusFilter: 'all' | 'completed' | 'ready';
    filteredMatches: MatchItem[];
    matchesByRound: Record<number, Array<MatchItem>>;
    roundLabel: (round: number, leg?: number) => string;
}>();

defineEmits<{
    (e: 'update:selectedRound', value: number | 'all'): void;
    (e: 'update:statusFilter', value: 'all' | 'completed' | 'ready'): void;
}>();
</script>

<template>
    <div class="space-y-6">
        <!-- Filter Controls (Pekan selector & status filter) -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-card border border-border/80 p-3.5 rounded-2xl shadow-2xs">
            <div class="flex items-center gap-2 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0">
                <span class="text-xs font-semibold text-muted-foreground shrink-0">Pekan:</span>
                <button
                    type="button"
                    @click="$emit('update:selectedRound', 'all')"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 border"
                    :class="selectedRound === 'all' ? 'bg-primary text-primary-foreground border-primary' : 'bg-muted/40 text-muted-foreground border-border'"
                    :aria-pressed="selectedRound === 'all'"
                >
                    Semua Pekan
                </button>
                <button
                    v-for="round in sortedRounds"
                    :key="round"
                    type="button"
                    @click="$emit('update:selectedRound', round)"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 border"
                    :class="selectedRound === round ? 'bg-amber-500 text-white border-amber-500' : 'bg-muted/40 text-muted-foreground border-border'"
                    :aria-pressed="selectedRound === round"
                >
                    {{ roundLabel(round, matchesByRound[round]?.[0]?.leg ?? 1) }}
                </button>
            </div>

            <!-- Status Filter -->
            <div class="flex items-center gap-2 text-xs">
                <button
                    type="button"
                    @click="$emit('update:statusFilter', 'all')"
                    class="px-2.5 py-1 rounded-lg font-medium border"
                    :class="statusFilter === 'all' ? 'bg-primary text-primary-foreground border-primary' : 'bg-card text-muted-foreground border-border'"
                    :aria-pressed="statusFilter === 'all'"
                >
                    Semua Status
                </button>
                <button
                    type="button"
                    @click="$emit('update:statusFilter', 'completed')"
                    class="px-2.5 py-1 rounded-lg font-medium border"
                    :class="statusFilter === 'completed' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-card text-muted-foreground border-border'"
                    :aria-pressed="statusFilter === 'completed'"
                >
                    ✅ Selesai
                </button>
            </div>
        </div>

        <!-- League Matches List Cards -->
        <div v-if="filteredMatches.length === 0" class="py-12 text-center rounded-2xl border border-dashed border-border bg-card p-6 text-muted-foreground">
            Belum ada pertandingan yang memenuhi filter ini.
        </div>

        <div v-else class="space-y-4">
            <div 
                v-for="match in filteredMatches" 
                :key="match.id"
                class="rounded-2xl border bg-card p-4 shadow-2xs hover:border-primary/40 transition-all space-y-3"
                :class="match.status === 'completed' ? 'border-border/80' : 'border-amber-500/30 bg-linear-to-r from-amber-500/5 via-transparent to-transparent'"
            >
                <!-- Match Header -->
                <div class="flex items-center justify-between text-xs text-muted-foreground border-b border-border/50 pb-2">
                    <span class="font-bold flex items-center gap-1.5">
                        <Swords class="size-3.5 text-amber-500" />
                        {{ roundLabel(match.round, match.leg) }} &bull; Match #{{ match.sequence }}
                    </span>

                    <Badge 
                        :variant="match.status === 'completed' ? 'outline' : 'default'"
                        class="text-[10px] font-semibold"
                        :class="match.status !== 'completed' ? 'bg-amber-500 text-white' : ''"
                    >
                        {{ match.status === 'completed' ? 'Selesai' : 'Belum Dimainkan' }}
                    </Badge>
                </div>

                <!-- Mobile Match Card Core Layout -->
                <div class="flex items-center justify-between gap-2 sm:gap-6">
                    <!-- Home Team -->
                    <div class="flex-1 flex items-center justify-end gap-2.5 min-w-0 text-right">
                        <span 
                            class="text-xs sm:text-sm font-semibold truncate"
                            :class="match.winner_id === match.home?.id ? 'text-emerald-600 dark:text-emerald-400 font-extrabold' : 'text-foreground'"
                        >
                            {{ match.home?.name || '??' }}
                        </span>
                        <div 
                            class="flex size-8 shrink-0 items-center justify-center rounded-lg font-bold text-xs shadow-2xs border"
                            :class="match.winner_id === match.home?.id ? 'bg-emerald-500 text-white border-emerald-500' : 'bg-muted text-foreground border-border'"
                        >
                            {{ getInitials(match.home?.name) }}
                        </div>
                    </div>

                    <!-- Score Badge Center -->
                    <div class="shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-muted/60 border border-border/80 shadow-2xs">
                        <span 
                            v-if="match.score_home !== null" 
                            class="font-mono font-extrabold text-sm sm:text-base text-foreground"
                            :class="match.winner_id === match.home?.id ? 'text-emerald-600 dark:text-emerald-400' : ''"
                        >
                            {{ match.score_home }}
                        </span>
                        <span v-else class="text-xs font-mono text-muted-foreground">-</span>

                        <span class="text-xs font-bold text-muted-foreground px-0.5">:</span>

                        <span 
                            v-if="match.score_away !== null" 
                            class="font-mono font-extrabold text-sm sm:text-base text-foreground"
                            :class="match.winner_id === match.away?.id ? 'text-emerald-600 dark:text-emerald-400' : ''"
                        >
                            {{ match.score_away }}
                        </span>
                        <span v-else class="text-xs font-mono text-muted-foreground">-</span>
                    </div>

                    <!-- Away Team -->
                    <div class="flex-1 flex items-center justify-start gap-2.5 min-w-0 text-left">
                        <div 
                            class="flex size-8 shrink-0 items-center justify-center rounded-lg font-bold text-xs shadow-2xs border"
                            :class="match.winner_id === match.away?.id ? 'bg-emerald-500 text-white border-emerald-500' : 'bg-muted text-foreground border-border'"
                        >
                            {{ getInitials(match.away?.name) }}
                        </div>
                        <span 
                            class="text-xs sm:text-sm font-semibold truncate"
                            :class="match.winner_id === match.away?.id ? 'text-emerald-600 dark:text-emerald-400 font-extrabold' : 'text-foreground'"
                        >
                            {{ match.away?.name || '??' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
