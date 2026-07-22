<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    ArrowLeft, 
    Trophy, 
    Users, 
    Medal, 
    CheckCircle2, 
    Flame, 
    Calendar,
    ChevronRight,
    LayoutGrid,
    ListFilter,
    Sparkles,
    Shield,
    Swords,
    Layers,
    SlidersHorizontal
} from '@lucide/vue';
import { index as homeLanding } from '@/actions/App/Http/Controllers/PublicCompetitionController';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
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

const props = defineProps<{
    competition: {
        id: number;
        name: string;
        slug: string;
        format: string;
        status: string;
        win_points: number | null;
        draw_points: number | null;
        loss_points: number | null;
    };
    participants: Array<{
        id: number;
        name: string;
        short_name: string | null;
    }>;
    matchesByRound: Record<number, Array<MatchItem>>;
    standings: Array<{
        rank: number;
        participant_id: number;
        participant_name: string;
        played: number;
        won: number;
        drawn: number;
        lost: number;
        score_for: number;
        score_against: number;
        difference: number;
        points: number;
    }>;
}>();

const statusLabel: Record<string, string> = {
    locked: 'Terkunci / Siap',
    in_progress: 'Sedang Berlangsung',
    completed: 'Selesai',
};

const formatLabel: Record<string, string> = {
    knockout: 'Knockout (Gugur)',
    full_competition: 'Kompetisi Penuh (Liga)',
    half_competition: 'Setengah Kompetisi',
};

const isKnockout = computed(() => props.competition.format === 'knockout');

const sortedRounds = computed(() => {
    return Object.keys(props.matchesByRound)
        .map(Number)
        .sort((a, b) => a - b);
});

// Knockout specific state & refs
const selectedKnockoutRound = ref<number>(sortedRounds.value[0] || 1);
const knockoutViewMode = ref<'rounds' | 'bracket'>('rounds'); // 'rounds' is mobile optimized card view
const knockoutStatusFilter = ref<'all' | 'completed' | 'ready' | 'bye'>('all');

// League specific state & refs
const activeTab = ref<'standings' | 'matches'>('matches');
const selectedLeagueRound = ref<number | 'all'>('all');
const leagueStatusFilter = ref<'all' | 'completed' | 'ready'>('all');

// All matches count & progress
const allMatchesList = computed(() => {
    return Object.values(props.matchesByRound).flat();
});

const completedMatchesCount = computed(() => {
    return allMatchesList.value.filter(m => m.status === 'completed' || m.status === 'bye').length;
});

const totalScorableMatchesCount = computed(() => {
    return allMatchesList.value.filter(m => m.status !== 'bye' && (m.home !== null || m.away !== null)).length;
});

const matchProgressPercentage = computed(() => {
    if (totalScorableMatchesCount.value === 0) return 0;
    return Math.min(100, Math.round((completedMatchesCount.value / totalScorableMatchesCount.value) * 100));
});

function roundLabel(round: number, leg: number = 1): string {
    if (isKnockout.value) {
        const total = sortedRounds.value.length;
        const mapping: Record<number, string> = { 1: 'Final', 2: 'Semifinal', 3: 'Perempat Final' };
        const fromEnd = total - round + 1;
        return mapping[fromEnd] || `Babak ${round}`;
    }
    return leg > 1 ? `Pekan ${round} - Leg ${leg}` : `Pekan ${round}`;
}

// Filtered matches for selected knockout round
const filteredKnockoutMatches = computed(() => {
    const roundMatches = props.matchesByRound[selectedKnockoutRound.value] || [];
    if (knockoutStatusFilter.value === 'all') return roundMatches;
    if (knockoutStatusFilter.value === 'completed') return roundMatches.filter(m => m.status === 'completed');
    if (knockoutStatusFilter.value === 'ready') return roundMatches.filter(m => m.status === 'ready' || m.status === 'in_progress');
    if (knockoutStatusFilter.value === 'bye') return roundMatches.filter(m => m.status === 'bye');
    return roundMatches;
});

// Filtered matches for league format
const filteredLeagueMatches = computed(() => {
    let matches = allMatchesList.value;

    if (selectedLeagueRound.value !== 'all') {
        matches = matches.filter(m => m.round === selectedLeagueRound.value);
    }

    if (leagueStatusFilter.value === 'completed') {
        matches = matches.filter(m => m.status === 'completed');
    } else if (leagueStatusFilter.value === 'ready') {
        matches = matches.filter(m => m.status === 'ready' || m.status === 'in_progress' || m.status === 'pending');
    }

    return matches;
});

// Jump to bracket column smoothly
function scrollToBracketRound(round: number) {
    const el = document.getElementById(`bracket-round-${round}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', inline: 'start', block: 'nearest' });
    }
}
</script>

<template>
    <Head :title="`${competition.name} - Match & Detail`" />

    <PublicLayout :competition-name="competition.name" :competition-slug="competition.slug">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 sm:py-10 space-y-6">
            <!-- Navigation Back Button -->
            <div>
                <Link
                    :href="homeLanding()"
                    class="inline-flex items-center gap-2 text-xs sm:text-sm font-medium text-muted-foreground hover:text-primary transition-colors bg-card border border-border/80 px-3.5 py-1.5 rounded-lg shadow-2xs hover:shadow-xs"
                >
                    <ArrowLeft class="size-4" />
                    <span>Kembali ke Daftar Lomba</span>
                </Link>
            </div>

            <!-- Competition Overview Header Card -->
            <div class="relative overflow-hidden rounded-2xl border border-border/80 bg-card p-5 sm:p-8 shadow-xs">
                <!-- Top Glow Accent -->
                <div class="absolute -top-24 -right-24 size-64 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-3 max-w-2xl">
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge 
                                :variant="competition.status === 'in_progress' ? 'default' : 'secondary'"
                                class="text-xs font-semibold px-2.5 py-0.5"
                                :class="competition.status === 'in_progress' ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : ''"
                            >
                                <span v-if="competition.status === 'in_progress'" class="size-1.5 rounded-full bg-white animate-pulse mr-1.5 inline-block"></span>
                                {{ statusLabel[competition.status] || competition.status }}
                            </Badge>

                            <Badge variant="outline" class="text-xs font-medium border-amber-500/40 text-amber-600 dark:text-amber-400 bg-amber-500/10">
                                <Layers class="size-3 mr-1" />
                                {{ formatLabel[competition.format] || competition.format }}
                            </Badge>
                        </div>

                        <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-foreground">
                            {{ competition.name }}
                        </h1>

                        <div class="flex flex-wrap items-center gap-4 text-xs sm:text-sm text-muted-foreground pt-1">
                            <span class="flex items-center gap-1.5 font-medium text-foreground">
                                <Users class="size-4 text-indigo-500" />
                                {{ props.participants.length }} Peserta Terdaftar
                            </span>

                            <span class="flex items-center gap-1.5 font-medium text-foreground">
                                <Calendar class="size-4 text-emerald-500" />
                                {{ completedMatchesCount }} / {{ totalScorableMatchesCount }} Match Selesai
                            </span>
                        </div>

                        <!-- Point Rules if League -->
                        <div v-if="competition.win_points !== null" class="text-xs text-muted-foreground bg-muted/60 px-3 py-1.5 rounded-lg border border-border/50 inline-flex items-center gap-2">
                            <Shield class="size-3.5 text-amber-500 shrink-0" />
                            <span>Sistem Poin: Menang <strong>{{ competition.win_points }}</strong> &bull; Seri <strong>{{ competition.draw_points }}</strong> &bull; Kalah <strong>{{ competition.loss_points }}</strong></span>
                        </div>
                    </div>

                    <!-- Progress Ring / Bar Summary -->
                    <div v-if="totalScorableMatchesCount > 0" class="w-full md:w-64 p-4 rounded-xl bg-muted/40 border border-border/60 space-y-2">
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span class="text-muted-foreground">Progress Lomba</span>
                            <span class="text-foreground font-bold">{{ matchProgressPercentage }}%</span>
                        </div>
                        <div class="h-2.5 w-full rounded-full bg-muted overflow-hidden">
                            <div 
                                class="h-full rounded-full transition-all duration-500"
                                :class="completedMatchesCount === totalScorableMatchesCount ? 'bg-emerald-500' : 'bg-amber-500'"
                                :style="{ width: `${matchProgressPercentage}%` }"
                            ></div>
                        </div>
                        <p class="text-[11px] text-muted-foreground text-right italic">
                            {{ completedMatchesCount === totalScorableMatchesCount ? 'Seluruh pertandingan telah usai 🎉' : 'Pertandingan berlangsung secara berkala' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- =================================================================== -->
            <!-- KNOCKOUT FORMAT VIEW (OPTIMIZED FOR MOBILE & DESKTOP) -->
            <!-- =================================================================== -->
            <div v-if="isKnockout" class="space-y-6">
                <!-- Knockout View Switcher (Per Babak vs Bagan Utuh) -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-card border border-border/80 p-3 rounded-2xl shadow-2xs">
                    <div class="flex items-center gap-1.5 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                        <button
                            @click="knockoutViewMode = 'rounds'"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all shrink-0"
                            :class="knockoutViewMode === 'rounds' ? 'bg-primary text-primary-foreground shadow-xs' : 'bg-muted/50 text-muted-foreground hover:bg-accent'"
                        >
                            <LayoutGrid class="size-4" />
                            <span>📱 Tampilan Per Babak (Mobile Friendly)</span>
                        </button>
                        <button
                            @click="knockoutViewMode = 'bracket'"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all shrink-0"
                            :class="knockoutViewMode === 'bracket' ? 'bg-primary text-primary-foreground shadow-xs' : 'bg-muted/50 text-muted-foreground hover:bg-accent'"
                        >
                            <Medal class="size-4" />
                            <span>🖥️ Bagan Utuh (Bracket)</span>
                        </button>
                    </div>

                    <div class="text-xs text-muted-foreground hidden lg:block">
                        <span class="italic">Tip: Gunakan "Tampilan Per Babak" untuk HP agar mudah memilih match</span>
                    </div>
                </div>

                <!-- ------------------------------------------------------------- -->
                <!-- MODE 1: TAMPILAN PER BABAK (MOBILE OPTIMIZED CARDS BY ROUND) -->
                <!-- ------------------------------------------------------------- -->
                <div v-if="knockoutViewMode === 'rounds'" class="space-y-6">
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
                                @click="selectedKnockoutRound = round"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all shrink-0 border"
                                :class="selectedKnockoutRound === round 
                                    ? 'bg-amber-500 text-white border-amber-500 shadow-sm scale-102' 
                                    : 'bg-card text-foreground border-border/80 hover:border-amber-500/50 hover:bg-accent'"
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
                            @click="knockoutStatusFilter = 'all'"
                            class="px-3 py-1 rounded-lg font-medium transition-colors border"
                            :class="knockoutStatusFilter === 'all' ? 'bg-primary text-primary-foreground border-primary' : 'bg-card text-muted-foreground border-border'"
                        >
                            Semua
                        </button>
                        <button
                            @click="knockoutStatusFilter = 'completed'"
                            class="px-3 py-1 rounded-lg font-medium transition-colors border"
                            :class="knockoutStatusFilter === 'completed' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-card text-muted-foreground border-border'"
                        >
                            ✅ Selesai
                        </button>
                        <button
                            @click="knockoutStatusFilter = 'ready'"
                            class="px-3 py-1 rounded-lg font-medium transition-colors border"
                            :class="knockoutStatusFilter === 'ready' ? 'bg-amber-600 text-white border-amber-600' : 'bg-card text-muted-foreground border-border'"
                        >
                            ⏳ Belum Dimainkan
                        </button>
                        <button
                            @click="knockoutStatusFilter = 'bye'"
                            class="px-3 py-1 rounded-lg font-medium transition-colors border"
                            :class="knockoutStatusFilter === 'bye' ? 'bg-slate-600 text-white border-slate-600' : 'bg-card text-muted-foreground border-border'"
                        >
                            💤 Bye
                        </button>
                    </div>

                    <!-- Empty Round State -->
                    <div v-if="filteredKnockoutMatches.length === 0" class="py-12 text-center rounded-2xl border border-dashed border-border bg-card p-6 text-muted-foreground">
                        Tidak ada pertandingan dengan kriteria ini pada {{ roundLabel(selectedKnockoutRound, 1) }}.
                    </div>

                    <!-- Knockout Mobile Cards Grid -->
                    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="match in filteredKnockoutMatches"
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

                <!-- ------------------------------------------------------------- -->
                <!-- MODE 2: BAGAN UTUH (BRACKET TREE VIEW) -->
                <!-- ------------------------------------------------------------- -->
                <div v-else class="space-y-4">
                    <!-- Quick Jump Round Buttons -->
                    <div class="flex items-center gap-2 overflow-x-auto pb-2">
                        <span class="text-xs font-semibold text-muted-foreground shrink-0">Jump ke Babak:</span>
                        <button
                            v-for="round in sortedRounds"
                            :key="round"
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
                                                    <span class="size-2 rounded-full shrink-0" :class="match.winner_id === match.home?.id ? 'bg-emerald-500' : 'bg-muted-foreground/30'"></span>
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
                                                    <span class="size-2 rounded-full shrink-0" :class="match.winner_id === match.away?.id ? 'bg-emerald-500' : 'bg-muted-foreground/30'"></span>
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
            </div>

            <!-- =================================================================== -->
            <!-- LIGA / ROUND ROBIN FORMAT VIEW -->
            <!-- =================================================================== -->
            <div v-else class="space-y-6">
                <!-- Navigation Tabs (Klasemen vs Pertandingan) -->
                <div class="flex items-center gap-2 border-b border-border pb-3">
                    <Button
                        v-if="standings.length > 0"
                        :variant="activeTab === 'standings' ? 'default' : 'outline'"
                        size="sm"
                        @click="activeTab = 'standings'"
                        class="rounded-xl px-4 font-bold text-xs sm:text-sm"
                    >
                        <Trophy class="mr-1.5 size-4 text-amber-400" />
                        Klasemen Perolehan Poin
                    </Button>
                    <Button
                        :variant="activeTab === 'matches' ? 'default' : 'outline'"
                        size="sm"
                        @click="activeTab = 'matches'"
                        class="rounded-xl px-4 font-bold text-xs sm:text-sm"
                    >
                        <CheckCircle2 class="mr-1.5 size-4" />
                        Jadwal & Hasil Match
                    </Button>
                </div>

                <!-- ------------------------------------------------------------- -->
                <!-- TAB 1: KLASEMEN (STANDINGS TABLE & MOBILE CARDS) -->
                <!-- ------------------------------------------------------------- -->
                <div v-if="activeTab === 'standings' && standings.length > 0" class="space-y-4">
                    <Card class="rounded-2xl overflow-hidden border border-border/80 shadow-xs">
                        <CardHeader class="border-b py-4 bg-muted/20">
                            <CardTitle class="text-base font-bold flex items-center justify-between">
                                <span class="flex items-center gap-2">
                                    <Trophy class="size-5 text-amber-500" />
                                    Klasemen Terbaru
                                </span>
                                <Badge variant="outline" class="text-xs font-normal">
                                    {{ standings.length }} Tim
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-0 overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader class="bg-muted/40">
                                    <TableRow>
                                        <TableHead class="w-14 text-center font-bold">#</TableHead>
                                        <TableHead class="font-bold min-w-[160px]">Nama Tim / Peserta</TableHead>
                                        <TableHead class="text-center font-bold">Main</TableHead>
                                        <TableHead class="text-center font-bold text-emerald-600 dark:text-emerald-400">M</TableHead>
                                        <TableHead class="text-center font-bold text-amber-600 dark:text-amber-400">S</TableHead>
                                        <TableHead class="text-center font-bold text-rose-600 dark:text-rose-400">K</TableHead>
                                        <TableHead class="text-center font-bold hidden sm:table-cell">GM</TableHead>
                                        <TableHead class="text-center font-bold hidden sm:table-cell">GK</TableHead>
                                        <TableHead class="text-center font-bold">Selisih</TableHead>
                                        <TableHead class="text-center font-extrabold text-foreground">Poin</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow 
                                        v-for="entry in standings" 
                                        :key="entry.participant_id"
                                        class="hover:bg-accent/40 transition-colors"
                                        :class="entry.rank === 1 ? 'bg-amber-500/5 font-semibold' : ''"
                                    >
                                        <TableCell class="text-center font-bold">
                                            <span v-if="entry.rank === 1" class="inline-flex size-7 items-center justify-center rounded-full bg-amber-500 text-white font-extrabold text-xs shadow-xs">🥇 1</span>
                                            <span v-else-if="entry.rank === 2" class="inline-flex size-7 items-center justify-center rounded-full bg-slate-300 dark:bg-slate-700 text-foreground font-extrabold text-xs">🥈 2</span>
                                            <span v-else-if="entry.rank === 3" class="inline-flex size-7 items-center justify-center rounded-full bg-amber-700/30 text-amber-800 dark:text-amber-300 font-extrabold text-xs">🥉 3</span>
                                            <span v-else class="text-muted-foreground">{{ entry.rank }}</span>
                                        </TableCell>

                                        <TableCell class="font-bold text-foreground">
                                            <div class="flex items-center gap-2">
                                                <div class="flex size-7 shrink-0 items-center justify-center rounded-md bg-muted text-foreground font-extrabold text-[11px] border">
                                                    {{ getInitials(entry.participant_name) }}
                                                </div>
                                                <span class="truncate max-w-[180px] sm:max-w-none">{{ entry.participant_name }}</span>
                                            </div>
                                        </TableCell>

                                        <TableCell class="text-center font-medium">{{ entry.played }}</TableCell>
                                        <TableCell class="text-center text-emerald-600 dark:text-emerald-400 font-semibold">{{ entry.won }}</TableCell>
                                        <TableCell class="text-center text-amber-600 dark:text-amber-400">{{ entry.drawn }}</TableCell>
                                        <TableCell class="text-center text-rose-600 dark:text-rose-400">{{ entry.lost }}</TableCell>
                                        <TableCell class="text-center text-muted-foreground hidden sm:table-cell">{{ entry.score_for }}</TableCell>
                                        <TableCell class="text-center text-muted-foreground hidden sm:table-cell">{{ entry.score_against }}</TableCell>
                                        <TableCell class="text-center font-mono font-bold" :class="entry.difference > 0 ? 'text-emerald-600' : entry.difference < 0 ? 'text-rose-600' : 'text-muted-foreground'">
                                            {{ entry.difference > 0 ? '+' : '' }}{{ entry.difference }}
                                        </TableCell>
                                        <TableCell class="text-center font-extrabold text-base text-foreground">{{ entry.points }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                <!-- ------------------------------------------------------------- -->
                <!-- TAB 2: PERTANDINGAN (LEAGUE MATCHES) -->
                <!-- ------------------------------------------------------------- -->
                <div v-if="activeTab === 'matches'" class="space-y-6">
                    <!-- Filter Controls (Pekan selector & status filter) -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-card border border-border/80 p-3.5 rounded-2xl shadow-2xs">
                        <div class="flex items-center gap-2 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0">
                            <span class="text-xs font-semibold text-muted-foreground shrink-0">Pekan:</span>
                            <button
                                @click="selectedLeagueRound = 'all'"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 border"
                                :class="selectedLeagueRound === 'all' ? 'bg-primary text-primary-foreground border-primary' : 'bg-muted/40 text-muted-foreground border-border'"
                            >
                                Semua Pekan
                            </button>
                            <button
                                v-for="round in sortedRounds"
                                :key="round"
                                @click="selectedLeagueRound = round"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 border"
                                :class="selectedLeagueRound === round ? 'bg-amber-500 text-white border-amber-500' : 'bg-muted/40 text-muted-foreground border-border'"
                            >
                                {{ roundLabel(round, matchesByRound[round][0]?.leg ?? 1) }}
                            </button>
                        </div>

                        <!-- Status Filter -->
                        <div class="flex items-center gap-2 text-xs">
                            <button
                                @click="leagueStatusFilter = 'all'"
                                class="px-2.5 py-1 rounded-lg font-medium border"
                                :class="leagueStatusFilter === 'all' ? 'bg-primary text-primary-foreground border-primary' : 'bg-card text-muted-foreground border-border'"
                            >
                                Semua Status
                            </button>
                            <button
                                @click="leagueStatusFilter = 'completed'"
                                class="px-2.5 py-1 rounded-lg font-medium border"
                                :class="leagueStatusFilter === 'completed' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-card text-muted-foreground border-border'"
                            >
                                ✅ Selesai
                            </button>
                        </div>
                    </div>

                    <!-- League Matches List Cards -->
                    <div v-if="filteredLeagueMatches.length === 0" class="py-12 text-center rounded-2xl border border-dashed border-border bg-card p-6 text-muted-foreground">
                        Belum ada pertandingan yang memenuhi filter ini.
                    </div>

                    <div v-else class="space-y-4">
                        <div 
                            v-for="match in filteredLeagueMatches" 
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
            </div>

            <!-- Empty Competition State -->
            <Card v-if="sortedRounds.length === 0" class="p-12 text-center text-muted-foreground rounded-2xl border-dashed">
                <Trophy class="size-12 mx-auto text-muted-foreground/30 mb-3" />
                <h3 class="text-base font-bold text-foreground">Pertandingan Belum Dibuat</h3>
                <p class="text-xs mt-1">Jadwal dan bagan undian pertandingan belum tersedia untuk lomba ini.</p>
            </Card>
        </div>
    </PublicLayout>
</template>
