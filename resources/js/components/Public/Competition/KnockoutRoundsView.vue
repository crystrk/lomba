<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Medal, ListFilter, Trophy } from '@lucide/vue';
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
    selectedRound: number;
    statusFilter: 'all' | 'completed' | 'ready' | 'bye';
    filteredMatches: MatchItem[];
    matchesByRound: Record<number, Array<MatchItem>>;
    roundLabel: (round: number, leg?: number) => string;
}>();

defineEmits<{
    (e: 'update:selectedRound', value: number): void;
    (e: 'update:statusFilter', value: 'all' | 'completed' | 'ready' | 'bye'): void;
}>();
</script>

<template>
    <div class="space-y-6">
        <!-- Round Pill Selector -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                    <Medal class="size-4 text-amber-500" />
                    Pilih Babak Pertandingan
                </h2>
            </div>

            <!-- Scrollable Round Tabs -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                <button
                    v-for="round in sortedRounds"
                    :key="round"
                    type="button"
                    @click="$emit('update:selectedRound', round)"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all shrink-0 border"
                    :class="selectedRound === round 
                        ? 'bg-amber-500 text-white border-amber-500 shadow-sm scale-102' 
                        : 'bg-card text-foreground border-border/80 hover:border-amber-500/50 hover:bg-accent'"
                    :aria-pressed="selectedRound === round"
                >
                    <span>{{ roundLabel(round, 1) }}</span>
                    <Badge variant="secondary" class="text-[10px] px-1.5 py-0 bg-background/30 text-current">
                        {{ matchesByRound[round]?.length || 0 }} Match
                    </Badge>
                </button>
            </div>
        </div>

        <!-- Status Filter Pills for Round -->
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="text-muted-foreground font-medium flex items-center gap-1">
                <ListFilter class="size-3.5" />
                Status Match:
            </span>

            <button
                type="button"
                @click="$emit('update:statusFilter', 'all')"
                class="px-3 py-1 rounded-lg font-medium transition-colors border"
                :class="statusFilter === 'all' ? 'bg-primary text-primary-foreground border-primary' : 'bg-card text-muted-foreground border-border'"
                :aria-pressed="statusFilter === 'all'"
            >
                Semua
            </button>
            <button
                type="button"
                @click="$emit('update:statusFilter', 'completed')"
                class="px-3 py-1 rounded-lg font-medium transition-colors border"
                :class="statusFilter === 'completed' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-card text-muted-foreground border-border'"
                :aria-pressed="statusFilter === 'completed'"
            >
                ✅ Selesai
            </button>
            <button
                type="button"
                @click="$emit('update:statusFilter', 'ready')"
                class="px-3 py-1 rounded-lg font-medium transition-colors border"
                :class="statusFilter === 'ready' ? 'bg-amber-600 text-white border-amber-600' : 'bg-card text-muted-foreground border-border'"
                :aria-pressed="statusFilter === 'ready'"
            >
                ⏳ Belum Dimainkan
            </button>
            <button
                type="button"
                @click="$emit('update:statusFilter', 'bye')"
                class="px-3 py-1 rounded-lg font-medium transition-colors border"
                :class="statusFilter === 'bye' ? 'bg-slate-600 text-white border-slate-600' : 'bg-card text-muted-foreground border-border'"
                :aria-pressed="statusFilter === 'bye'"
            >
                💤 Bye
            </button>
        </div>

        <!-- Empty Round State -->
        <div v-if="filteredMatches.length === 0" class="py-12 text-center rounded-2xl border border-dashed border-border bg-card p-6 text-muted-foreground">
            Tidak ada pertandingan dengan kriteria ini pada {{ roundLabel(selectedRound, 1) }}.
        </div>

        <!-- Knockout Mobile Cards Grid -->
        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="match in filteredMatches"
                :key="match.id"
                class="rounded-2xl border bg-card p-4 shadow-2xs transition-all hover:border-primary/40 space-y-3 relative overflow-hidden"
                :class="match.status === 'completed' ? 'border-border/80' : 'border-amber-500/30 bg-linear-to-b from-amber-500/5 to-transparent'"
            >
                <!-- Card Top Bar -->
                <div class="flex items-center justify-between border-b border-border/50 pb-2 text-xs">
                    <span class="font-bold text-muted-foreground flex items-center gap-1">
                        Match #{{ match.sequence }}
                    </span>

                    <Badge 
                        :variant="match.status === 'completed' ? 'outline' : match.status === 'bye' ? 'secondary' : 'default'"
                        class="text-[10px] font-semibold"
                        :class="match.status === 'ready' ? 'bg-amber-500 text-white' : ''"
                    >
                        {{ match.status === 'completed' ? 'Selesai' : match.status === 'bye' ? 'Bye (Lolos Direct)' : 'Ready' }}
                    </Badge>
                </div>

                <!-- Bye Match Display -->
                <div v-if="match.status === 'bye'" class="py-2 space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 font-extrabold text-sm border border-amber-500/20">
                            {{ getInitials(match.home?.name) }}
                        </div>
                        <div>
                            <span class="font-bold text-foreground text-sm block">{{ match.home?.name || '??' }}</span>
                            <span class="text-xs text-amber-600 dark:text-amber-400 font-medium">Lolos otomatis ke babak berikutnya</span>
                        </div>
                    </div>
                </div>

                <!-- Regular Match Teams Display -->
                <div v-else class="space-y-2.5">
                    <!-- Home Team Row -->
                    <div 
                        class="flex items-center justify-between p-2.5 rounded-xl transition-colors"
                        :class="match.winner_id === match.home?.id ? 'bg-emerald-500/10 border border-emerald-500/30' : 'bg-muted/40'"
                    >
                        <div class="flex items-center gap-2.5 min-w-0 flex-1 pr-2">
                            <div 
                                class="flex size-8 shrink-0 items-center justify-center rounded-lg font-bold text-xs shadow-2xs"
                                :class="match.winner_id === match.home?.id ? 'bg-emerald-500 text-white' : 'bg-background border border-border text-foreground'"
                            >
                                {{ getInitials(match.home?.name) }}
                            </div>
                            <span 
                                class="text-sm font-semibold truncate"
                                :class="match.winner_id === match.home?.id ? 'text-emerald-700 dark:text-emerald-300 font-bold' : match.home ? 'text-foreground' : 'text-muted-foreground italic'"
                            >
                                {{ match.home?.name || 'Menunggu Pemenang' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            <Trophy v-if="match.winner_id === match.home?.id" class="size-4 text-amber-500" />
                            <span 
                                v-if="match.score_home !== null" 
                                class="flex size-8 items-center justify-center rounded-lg bg-background border font-mono font-bold text-sm shadow-2xs"
                                :class="match.winner_id === match.home?.id ? 'border-emerald-500 text-emerald-600 font-extrabold' : ''"
                            >
                                {{ match.score_home }}
                            </span>
                            <span v-else-if="match.status === 'completed'" class="text-xs text-muted-foreground">-</span>
                            <span v-else class="text-xs text-muted-foreground font-mono">VS</span>
                        </div>
                    </div>

                    <!-- Away Team Row -->
                    <div 
                        class="flex items-center justify-between p-2.5 rounded-xl transition-colors"
                        :class="match.winner_id === match.away?.id ? 'bg-emerald-500/10 border border-emerald-500/30' : 'bg-muted/40'"
                    >
                        <div class="flex items-center gap-2.5 min-w-0 flex-1 pr-2">
                            <div 
                                class="flex size-8 shrink-0 items-center justify-center rounded-lg font-bold text-xs shadow-2xs"
                                :class="match.winner_id === match.away?.id ? 'bg-emerald-500 text-white' : 'bg-background border border-border text-foreground'"
                            >
                                {{ getInitials(match.away?.name) }}
                            </div>
                            <span 
                                class="text-sm font-semibold truncate"
                                :class="match.winner_id === match.away?.id ? 'text-emerald-700 dark:text-emerald-300 font-bold' : match.away ? 'text-foreground' : 'text-muted-foreground italic'"
                            >
                                {{ match.away?.name || 'Menunggu Pemenang' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            <Trophy v-if="match.winner_id === match.away?.id" class="size-4 text-amber-500" />
                            <span 
                                v-if="match.score_away !== null" 
                                class="flex size-8 items-center justify-center rounded-lg bg-background border font-mono font-bold text-sm shadow-2xs"
                                :class="match.winner_id === match.away?.id ? 'border-emerald-500 text-emerald-600 font-extrabold' : ''"
                            >
                                {{ match.score_away }}
                            </span>
                            <span v-else-if="match.status === 'completed'" class="text-xs text-muted-foreground">-</span>
                            <span v-else class="text-xs text-muted-foreground font-mono">VS</span>
                        </div>
                    </div>
                </div>

                <!-- Card Bottom Info (Win method / next match link) -->
                <div v-if="match.win_method || (match.home && match.away && match.status !== 'completed')" class="pt-2 border-t border-border/40 flex items-center justify-between text-xs">
                    <span v-if="match.win_method" class="font-semibold text-amber-600 dark:text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-md">
                        {{ match.win_method }}
                    </span>
                    <span v-else-if="match.status !== 'completed'" class="text-muted-foreground italic text-[11px]">
                        Belum dimainkan
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
