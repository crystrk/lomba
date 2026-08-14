<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Save,
    Trophy,
    Medal,
    AlertCircle,
    Lock,
    Unlock,
    Clock,
    ExternalLink,
    Radio,
    Check,
    Plus,
    Minus,
    Search,
    X,
    ChevronDown,
    ChevronUp,
    ArrowLeft,
    CheckCircle2,
    Activity,
    Calendar,
    Sparkles,
    ShieldAlert,
    TableProperties,
} from '@lucide/vue';
import { ref, computed, shallowRef, watch } from 'vue';
import CompetitionSportIcon from '@/components/competitions/CompetitionSportIcon.vue';
import LeagueStandingsTable from '@/components/Public/Competition/LeagueStandingsTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { getInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { show, lockResults } from '@/routes/admin/competitions';
import { toggleOngoing as toggleOngoingRoute } from '@/routes/admin/matches';
import { update as updateSchedule } from '@/routes/admin/matches/schedule';
import { update as updateScore } from '@/routes/admin/matches/score';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    competition: {
        id: number;
        name: string;
        slug: string;
        sport?: string | null;
        format: string;
        status: string;
        win_points: number | null;
        draw_points: number | null;
        loss_points: number | null;
        draw_version: number;
        is_results_locked?: boolean;
    };
    matchesByRound: Record<number, Array<{
        id: number;
        round: number;
        leg: number;
        sequence: number;
        participant_id_home: number | null;
        participant_id_away: number | null;
        score_home: number | null;
        score_away: number | null;
        scheduled_time: string | null;
        winner_id: number | null;
        win_method: string | null;
        status: string;
        match_type?: string;
        is_ongoing: boolean;
        result_version: number;
        home_participant: { id: number; name: string; short_name: string | null } | null;
        away_participant: { id: number; name: string; short_name: string | null } | null;
    }>>;
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
    groupStandings?: {
        group_a: Array<{
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
        group_b: Array<{
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
    } | null;
}>();

const statusLabel: Record<string, string> = {
    draft: 'Draft',
    drawn: 'Diundi',
    locked: 'Terkunci',
    in_progress: 'Sedang Berlangsung',
    completed: 'Selesai',
};

const formatLabel: Record<string, string> = {
    knockout: 'Knockout (Gugur)',
    final_four: 'Final Four',
    group_final_four: 'Group Final Four',
    group_four_final: 'Group Final Four',
    full_competition: 'Liga Penuh',
    half_competition: 'Setengah Liga',
};

const isKnockout = computed(() => props.competition.format === 'knockout' || props.competition.format === 'final_four');

const sortedRounds = computed(() => {
    return Object.keys(props.matchesByRound)
        .map(Number)
        .sort((a, b) => a - b);
});

// Navigation & Filters
const selectedRound = ref<string>('all');
const selectedStatus = ref<'all' | 'ongoing' | 'pending' | 'completed'>('all');
const searchQuery = ref<string>('');
const showStandings = ref<boolean>(false);

// Match Counts & Metrics
const allMatchesList = computed(() => {
    const list: typeof props.matchesByRound[number] = [];
    for (const round of sortedRounds.value) {
        list.push(...(props.matchesByRound[round] || []));
    }
    return list;
});

const playableMatches = computed(() => allMatchesList.value.filter((m) => m.status !== 'bye'));
const totalMatchesCount = computed(() => playableMatches.value.length);
const completedMatchesCount = computed(() => playableMatches.value.filter((m) => m.status === 'completed').length);
const ongoingMatchesCount = computed(() => playableMatches.value.filter((m) => m.is_ongoing).length);
const pendingMatchesCount = computed(() => playableMatches.value.filter((m) => m.status !== 'completed' && !m.is_ongoing).length);

const progressPercent = computed(() => {
    if (totalMatchesCount.value === 0) return 0;
    return Math.round((completedMatchesCount.value / totalMatchesCount.value) * 100);
});

const roundMatchCounts = computed(() => {
    const counts: Record<number, { total: number; completed: number; ongoing: number }> = {};
    for (const r of sortedRounds.value) {
        const matches = props.matchesByRound[r] || [];
        const nonBye = matches.filter((m) => m.status !== 'bye');
        counts[r] = {
            total: nonBye.length,
            completed: nonBye.filter((m) => m.status === 'completed').length,
            ongoing: nonBye.filter((m) => m.is_ongoing).length,
        };
    }
    return counts;
});

const filteredRounds = computed(() => {
    if (selectedRound.value === 'all') {
        return sortedRounds.value;
    }
    return sortedRounds.value.filter((r) => String(r) === selectedRound.value);
});

function isMatchVisible(match: typeof props.matchesByRound[number][0]): boolean {
    if (match.status === 'bye') {
        return selectedStatus.value === 'all';
    }

    if (selectedStatus.value === 'ongoing' && !match.is_ongoing) {
        return false;
    }

    if (selectedStatus.value === 'completed' && match.status !== 'completed') {
        return false;
    }

    if (selectedStatus.value === 'pending' && (match.status === 'completed' || match.is_ongoing)) {
        return false;
    }

    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase().trim();
        const homeName = (match.home_participant?.name || '').toLowerCase();
        const awayName = (match.away_participant?.name || '').toLowerCase();
        const homeShort = (match.home_participant?.short_name || '').toLowerCase();
        const awayShort = (match.away_participant?.short_name || '').toLowerCase();
        const time = (match.scheduled_time || '').toLowerCase();

        return homeName.includes(query) || awayName.includes(query) || homeShort.includes(query) || awayShort.includes(query) || time.includes(query);
    }

    return true;
}

function getVisibleMatchesCountForRound(round: number): number {
    return (props.matchesByRound[round] || []).filter(isMatchVisible).length;
}

// Form state
const matchForms = shallowRef<Record<number, ReturnType<typeof useForm>>>({});
const recentlySaved = ref<Record<number, boolean>>({});

watch(sortedRounds, () => {
    const forms = { ...matchForms.value };

    for (const round of sortedRounds.value) {
        for (const match of props.matchesByRound[round]) {
            if (match.status === 'bye') {
                continue;
            }

            if (!forms[match.id]) {
                forms[match.id] = useForm({
                    score_home: match.score_home ?? '',
                    score_away: match.score_away ?? '',
                    winner_id: match.winner_id ? String(match.winner_id) : '',
                    win_method: match.win_method ?? '',
                    scheduled_time: match.scheduled_time ?? '',
                    result_version: match.result_version,
                });
            } else {
                // Update version & default values when props change from server
                forms[match.id].result_version = match.result_version;
                if (!forms[match.id].isDirty) {
                    forms[match.id].score_home = match.score_home ?? '';
                    forms[match.id].score_away = match.score_away ?? '';
                    forms[match.id].winner_id = match.winner_id ? String(match.winner_id) : '';
                    forms[match.id].win_method = match.win_method ?? '';
                    forms[match.id].scheduled_time = match.scheduled_time ?? '';
                }
            }
        }
    }

    matchForms.value = forms;
}, { immediate: true });

// Stepper score change
function stepScore(matchId: number, side: 'home' | 'away', delta: number) {
    const form = matchForms.value[matchId];
    if (!form || props.competition.is_results_locked) {
        return;
    }

    const currentVal = form[`score_${side}`];
    let nextVal = 0;

    if (currentVal === '' || currentVal === null || currentVal === undefined) {
        nextVal = delta > 0 ? delta : 0;
    } else {
        nextVal = Math.max(0, Number(currentVal) + delta);
    }

    form[`score_${side}`] = nextVal;
}

function submitScore(matchId: number) {
    const form = matchForms.value[matchId];
    if (!form || props.competition.is_results_locked) {
        return;
    }

    form.post(updateScore.url({ competition: props.competition.id, match: matchId }), {
        preserveScroll: true,
        onSuccess: () => {
            recentlySaved.value[matchId] = true;
            setTimeout(() => {
                recentlySaved.value[matchId] = false;
            }, 2500);
        },
    });
}

// Lock Results Toggle (Admin Feature)
const lockResultsForm = useForm({});
const lockResultsDialogOpen = ref(false);

function executeToggleResultsLock() {
    lockResultsForm.post(lockResults.url({ competition: props.competition.id }), {
        preserveScroll: true,
        onSuccess: () => {
            lockResultsDialogOpen.value = false;
        },
    });
}

// Live Toggle
const toggleOngoingProcessing = ref<Record<number, boolean>>({});
function submitToggleOngoing(matchId: number) {
    if (toggleOngoingProcessing.value[matchId] || props.competition.is_results_locked) {
        return;
    }

    toggleOngoingProcessing.value[matchId] = true;
    useForm({}).post(toggleOngoingRoute.url({ competition: props.competition.id, match: matchId }), {
        preserveScroll: true,
        onFinish: () => {
            toggleOngoingProcessing.value[matchId] = false;
        },
    });
}

// Schedule Quick Modal (WITA Timezone)
const scheduleModalOpen = ref(false);
const selectedMatchForSchedule = ref<typeof props.matchesByRound[number][0] | null>(null);
const scheduleForm = useForm({
    scheduled_time: '',
});

const quickTimePresets = ['08:00 WITA', '09:30 WITA', '11:00 WITA', '13:30 WITA', '15:00 WITA', '16:30 WITA', '19:00 WITA', '20:00 WITA'];

function openScheduleDialog(match: typeof props.matchesByRound[number][0]) {
    selectedMatchForSchedule.value = match;
    scheduleForm.scheduled_time = match.scheduled_time || '';
    scheduleModalOpen.value = true;
}

function submitQuickSchedule() {
    if (!selectedMatchForSchedule.value || props.competition.is_results_locked) {
        return;
    }

    const matchId = selectedMatchForSchedule.value.id;
    scheduleForm.post(updateSchedule.url({ competition: props.competition.id, match: matchId }), {
        preserveScroll: true,
        onSuccess: () => {
            if (matchForms.value[matchId]) {
                matchForms.value[matchId].scheduled_time = scheduleForm.scheduled_time;
            }
            scheduleModalOpen.value = false;
        },
    });
}

function setQuickTime(preset: string) {
    scheduleForm.scheduled_time = preset;
}

function isTieInKnockout(matchId: number): boolean {
    const form = matchForms.value[matchId];
    if (!form) {
        return false;
    }

    let matchItem: typeof props.matchesByRound[number][0] | null = null;
    for (const r of sortedRounds.value) {
        const found = props.matchesByRound[r].find((m) => m.id === matchId);
        if (found) {
            matchItem = found;
            break;
        }
    }

    const isKnockoutMatch = isKnockout.value || (props.competition.format === 'group_final_four' && (matchItem?.match_type === 'final' || matchItem?.match_type === 'third_place'));

    if (!isKnockoutMatch) {
        return false;
    }

    const home = form.score_home;
    const away = form.score_away;

    return home !== '' && away !== '' && Number(home) === Number(away);
}

function roundLabel(round: number, leg: number): string {
    if (props.competition.format === 'group_final_four') {
        const maxRound = sortedRounds.value[sortedRounds.value.length - 1];
        if (round === maxRound) {
            return 'Babak Final Placement';
        }
        return `Penyisihan Grup (Pekan ${round})`;
    }

    if (isKnockout.value) {
        const labels: Record<number, string> = { 1: 'Final', 2: 'Semifinal', 3: 'Perempat Final', 4: 'Babak 16 Besar', 5: 'Babak 32 Besar' };
        const totalRounds = sortedRounds.value.length;
        const fromEnd = totalRounds - round + 1;
        return labels[fromEnd] || `Babak ${round}`;
    }

    if (leg > 1) {
        return `Pekan ${round} (Leg ${leg})`;
    }

    return `Pekan ${round}`;
}

// Avatar color generator based on name
function getTeamColorClass(name: string): string {
    const colors = [
        'from-blue-600 to-indigo-600 text-white',
        'from-emerald-600 to-teal-600 text-white',
        'from-purple-600 to-violet-600 text-white',
        'from-amber-600 to-orange-600 text-white',
        'from-rose-600 to-pink-600 text-white',
        'from-cyan-600 to-blue-600 text-white',
    ];
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    const index = Math.abs(hash) % colors.length;
    return colors[index];
}
</script>

<template>
    <Head :title="`Input Skor - ${competition.name}`" />

    <div class="flex flex-col min-h-screen pb-16 bg-muted/20">
        <!-- Top App Bar / Header -->
        <div class="sticky top-0 z-30 bg-background/95 backdrop-blur-md border-b shadow-2xs">
            <div class="max-w-4xl mx-auto px-4 py-3 sm:py-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <Link :href="show.url({ competition: competition.id })">
                            <Button variant="ghost" size="icon" class="size-9 rounded-full shrink-0 -ml-1 text-muted-foreground hover:text-foreground">
                                <ArrowLeft class="size-5" />
                                <span class="sr-only">Kembali ke Detail Lomba</span>
                            </Button>
                        </Link>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 text-xs text-muted-foreground font-medium truncate">
                                <CompetitionSportIcon :sport="competition.sport ?? null" class="size-3.5 text-primary shrink-0" />
                                <span class="truncate">{{ formatLabel[competition.format] || competition.format }}</span>
                            </div>
                            <h1 class="text-base sm:text-lg font-bold tracking-tight text-foreground truncate">
                                {{ competition.name }}
                            </h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                        <!-- Admin Lock / Unlock Results Button -->
                        <Button
                            v-if="competition.status !== 'draft' && competition.status !== 'drawn'"
                            :variant="competition.is_results_locked ? 'outline' : 'default'"
                            size="sm"
                            class="h-8 px-2.5 sm:px-3 text-xs gap-1.5 rounded-lg font-semibold"
                            :disabled="lockResultsForm.processing || (!competition.is_results_locked && competition.status !== 'completed')"
                            :title="!competition.is_results_locked && competition.status !== 'completed' ? 'Penguncian hasil hanya dapat dilakukan setelah seluruh pertandingan selesai' : ''"
                            @click="lockResultsDialogOpen = true"
                        >
                            <component :is="competition.is_results_locked ? Unlock : Lock" class="size-3.5" />
                            <span class="hidden sm:inline">{{ competition.is_results_locked ? 'Buka Kunci Hasil' : 'Kunci Hasil' }}</span>
                            <span class="sm:hidden">{{ competition.is_results_locked ? 'Buka Kunci' : 'Kunci' }}</span>
                        </Button>

                        <a
                            :href="`/lomba/${competition.slug}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex"
                        >
                            <Button variant="outline" size="sm" class="h-8 px-2.5 text-xs gap-1.5 rounded-lg">
                                <ExternalLink class="size-3.5 text-primary" />
                                <span class="hidden md:inline">Halaman Publik</span>
                            </Button>
                        </a>

                        <Badge
                            :variant="competition.status === 'in_progress' ? 'default' : 'secondary'"
                            class="h-8 px-2.5 text-xs font-semibold rounded-lg shrink-0"
                            :class="{ 'bg-emerald-500 hover:bg-emerald-600 text-white font-bold animate-pulse': competition.status === 'in_progress' }"
                        >
                            {{ statusLabel[competition.status] || competition.status }}
                        </Badge>
                    </div>
                </div>

                <!-- Progress & Quick Match Status Summary Bar -->
                <div class="mt-3 pt-2.5 border-t flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1.5 font-semibold text-foreground">
                            <CheckCircle2 class="size-3.5 text-emerald-500" />
                            <span>{{ completedMatchesCount }}/{{ totalMatchesCount }} Selesai</span>
                        </div>
                        <span class="text-muted-foreground">({{ progressPercent }}%)</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span
                            v-if="ongoingMatchesCount > 0"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-rose-500 text-white font-black text-[11px] shadow-2xs animate-pulse"
                        >
                            <span class="size-1.5 rounded-full bg-white"></span>
                            {{ ongoingMatchesCount }} LIVE
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-muted text-muted-foreground font-medium text-[11px]">
                            {{ pendingMatchesCount }} Menunggu
                        </span>
                    </div>
                </div>

                <!-- Progress Bar Line -->
                <div class="w-full bg-muted rounded-full h-1.5 mt-2 overflow-hidden">
                    <div
                        class="bg-primary h-1.5 rounded-full transition-all duration-500"
                        :style="{ width: `${progressPercent}%` }"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <main class="max-w-4xl mx-auto px-4 py-4 sm:py-6 w-full space-y-4">
            <!-- Alert Banner: Locked Results -->
            <div
                v-if="competition.is_results_locked"
                class="rounded-xl border border-rose-500/40 bg-rose-500/10 p-3.5 sm:p-4 text-rose-800 dark:text-rose-300 flex items-start gap-3 shadow-2xs"
            >
                <Lock class="size-5 shrink-0 mt-0.5 text-rose-600 dark:text-rose-400" />
                <div class="space-y-0.5">
                    <h4 class="font-bold text-sm">Hasil Pertandingan Terkunci Final</h4>
                    <p class="text-xs text-rose-700 dark:text-rose-300">
                        Hasil pertandingan telah dikunci secara final oleh Admin. Skor tidak dapat diubah lagi kecuali Anda membuka kunci hasil di atas.
                    </p>
                </div>
            </div>

            <!-- Alert Banner: Draft / Drawn Notice -->
            <div
                v-else-if="competition.status === 'draft' || competition.status === 'drawn'"
                class="rounded-xl border border-amber-500/40 bg-amber-500/10 p-3.5 sm:p-4 text-amber-800 dark:text-amber-300 flex items-start gap-3 shadow-2xs"
            >
                <AlertCircle class="size-5 shrink-0 mt-0.5 text-amber-600 dark:text-amber-400" />
                <div class="space-y-0.5">
                    <h4 class="font-bold text-sm">Input Skor Belum Diizinkan</h4>
                    <p class="text-xs text-amber-700 dark:text-amber-300">
                        Lomba berstatus <strong>{{ statusLabel[competition.status] || competition.status }}</strong>.
                        Kunci bagan/jadwal (Status Terkunci/Locked) di menu Bagan/Undian terlebih dahulu sebelum skor pertandingan dapat diinput.
                    </p>
                </div>
            </div>

            <!-- Collapsible Klasemen Sementara (Points Formats) -->
            <div v-if="!isKnockout && standings.length > 0" class="space-y-2">
                <Button
                    type="button"
                    variant="outline"
                    class="w-full flex items-center justify-between bg-card hover:bg-muted/50 border py-3 px-4 rounded-xl shadow-2xs h-auto"
                    @click="showStandings = !showStandings"
                >
                    <div class="flex items-center gap-2.5">
                        <div class="p-1.5 rounded-lg bg-primary/10 text-primary">
                            <TableProperties class="size-4" />
                        </div>
                        <div class="text-left">
                            <div class="text-xs sm:text-sm font-bold text-foreground">
                                Klasemen Sementara
                            </div>
                            <div class="text-[11px] text-muted-foreground">
                                {{ standings.length }} Tim Terdaftar &bull; Otomatis terhitung dari skor
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge variant="secondary" class="text-xs font-semibold">
                            {{ showStandings ? 'Tutup' : 'Lihat' }}
                        </Badge>
                        <component :is="showStandings ? ChevronUp : ChevronDown" class="size-4 text-muted-foreground" />
                    </div>
                </Button>

                <div v-show="showStandings" class="pt-2 animate-in fade-in slide-in-from-top-2 duration-200">
                    <LeagueStandingsTable
                        :standings="standings"
                        :group-standings="groupStandings"
                        title="Klasemen Sementara"
                    />
                </div>
            </div>

            <!-- Sticky Quick Controls: Status Chips, Round Pills & Search -->
            <div class="sticky top-[110px] sm:top-[122px] z-20 bg-background/95 backdrop-blur-md rounded-2xl border p-2.5 sm:p-3 shadow-sm space-y-2.5">
                <!-- Status Filter Chips (Horizontal Scrollable) -->
                <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5">
                    <button
                        type="button"
                        class="px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 cursor-pointer shrink-0"
                        :class="selectedStatus === 'all'
                            ? 'bg-primary text-primary-foreground shadow-2xs'
                            : 'bg-muted/60 text-muted-foreground hover:bg-muted hover:text-foreground'"
                        @click="selectedStatus = 'all'"
                    >
                        Semua
                        <span class="text-[10px] px-1.5 py-0.2 rounded-full opacity-80" :class="selectedStatus === 'all' ? 'bg-primary-foreground/20 text-primary-foreground' : 'bg-background text-foreground'">
                            {{ totalMatchesCount }}
                        </span>
                    </button>

                    <button
                        type="button"
                        class="px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 cursor-pointer shrink-0"
                        :class="selectedStatus === 'ongoing'
                            ? 'bg-rose-500 text-white shadow-2xs font-bold'
                            : 'bg-muted/60 text-muted-foreground hover:bg-rose-500/10 hover:text-rose-600'"
                        @click="selectedStatus = 'ongoing'"
                    >
                        <span class="size-1.5 rounded-full bg-rose-500" :class="selectedStatus === 'ongoing' ? 'bg-white animate-pulse' : ''"></span>
                        Live
                        <span
                            v-if="ongoingMatchesCount > 0"
                            class="text-[10px] px-1.5 py-0.2 rounded-full font-bold"
                            :class="selectedStatus === 'ongoing' ? 'bg-white/30 text-white' : 'bg-rose-500/20 text-rose-700 dark:text-rose-300'"
                        >
                            {{ ongoingMatchesCount }}
                        </span>
                    </button>

                    <button
                        type="button"
                        class="px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 cursor-pointer shrink-0"
                        :class="selectedStatus === 'pending'
                            ? 'bg-primary text-primary-foreground shadow-2xs'
                            : 'bg-muted/60 text-muted-foreground hover:bg-muted hover:text-foreground'"
                        @click="selectedStatus = 'pending'"
                    >
                        Belum Main
                        <span class="text-[10px] px-1.5 py-0.2 rounded-full opacity-80" :class="selectedStatus === 'pending' ? 'bg-primary-foreground/20 text-primary-foreground' : 'bg-background text-foreground'">
                            {{ pendingMatchesCount }}
                        </span>
                    </button>

                    <button
                        type="button"
                        class="px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 cursor-pointer shrink-0"
                        :class="selectedStatus === 'completed'
                            ? 'bg-emerald-600 text-white shadow-2xs'
                            : 'bg-muted/60 text-muted-foreground hover:bg-emerald-500/10 hover:text-emerald-600'"
                        @click="selectedStatus = 'completed'"
                    >
                        Selesai
                        <span class="text-[10px] px-1.5 py-0.2 rounded-full opacity-80" :class="selectedStatus === 'completed' ? 'bg-white/20 text-white' : 'bg-background text-foreground'">
                            {{ completedMatchesCount }}
                        </span>
                    </button>
                </div>

                <!-- Round Navigation Tabs (Horizontal Scrollable) & Search Input -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2 pt-1 border-t">
                    <!-- Round Pills -->
                    <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5">
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-lg text-xs font-medium whitespace-nowrap transition-all cursor-pointer shrink-0"
                            :class="selectedRound === 'all'
                                ? 'bg-foreground text-background font-bold shadow-2xs'
                                : 'bg-muted/40 text-muted-foreground hover:bg-muted hover:text-foreground'"
                            @click="selectedRound = 'all'"
                        >
                            Semua Babak
                        </button>

                        <button
                            v-for="r in sortedRounds"
                            :key="r"
                            type="button"
                            class="px-2.5 py-1 rounded-lg text-xs font-medium whitespace-nowrap transition-all flex items-center gap-1 cursor-pointer shrink-0"
                            :class="selectedRound === String(r)
                                ? 'bg-foreground text-background font-bold shadow-2xs'
                                : 'bg-muted/40 text-muted-foreground hover:bg-muted hover:text-foreground'"
                            @click="selectedRound = String(r)"
                        >
                            <span>{{ roundLabel(r, matchesByRound[r][0]?.leg ?? 1) }}</span>
                            <span
                                v-if="roundMatchCounts[r]"
                                class="text-[10px] opacity-75"
                            >
                                ({{ roundMatchCounts[r].completed }}/{{ roundMatchCounts[r].total }})
                            </span>
                        </button>
                    </div>

                    <!-- Search Input on Mobile / Desktop -->
                    <div class="relative min-w-[180px] sm:max-w-[220px]">
                        <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari tim..."
                            class="h-8 pl-8 pr-7 text-xs bg-background rounded-lg"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            @click="searchQuery = ''"
                        >
                            <X class="size-3.5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Matches List by Round -->
            <div class="space-y-6">
                <template v-for="round in filteredRounds" :key="round">
                    <div v-if="getVisibleMatchesCountForRound(round) > 0" class="space-y-3">
                        <!-- Round Section Title Header -->
                        <div class="flex items-center justify-between px-1">
                            <div class="flex items-center gap-2">
                                <div class="size-6 rounded-md bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                                    {{ round }}
                                </div>
                                <h2 class="text-sm sm:text-base font-bold text-foreground">
                                    {{ roundLabel(round, matchesByRound[round][0]?.leg ?? 1) }}
                                </h2>
                            </div>
                            <span class="text-xs text-muted-foreground font-medium">
                                {{ getVisibleMatchesCountForRound(round) }} Pertandingan
                            </span>
                        </div>

                        <!-- Match Cards Grid -->
                        <div class="grid grid-cols-1 gap-3.5">
                            <template v-for="match in matchesByRound[round]" :key="match.id">
                                <div v-if="isMatchVisible(match)">
                                    <!-- BYE MATCH CARD -->
                                    <div
                                        v-if="match.status === 'bye'"
                                        class="rounded-2xl border border-dashed border-border bg-card/60 p-4 text-xs text-muted-foreground flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-2 font-medium">
                                            <span class="size-2 rounded-full bg-muted-foreground/50"></span>
                                            <div>
                                                <span class="font-bold text-foreground">{{ match.home_participant?.name ?? 'TBD' }}</span>
                                                <span v-if="match.home_participant?.short_name" class="ml-1 text-muted-foreground">({{ match.home_participant.short_name }})</span>
                                            </div>
                                        </div>
                                        <Badge variant="outline" class="text-[10px]">
                                            Lolos Otomatis (Bye)
                                        </Badge>
                                    </div>

                                    <!-- REGULAR MATCH CARD (TOUCH & MOBILE OPTIMIZED) -->
                                    <Card
                                        v-else
                                        class="overflow-hidden rounded-2xl border transition-all shadow-xs"
                                        :class="{
                                            'border-rose-500/60 ring-2 ring-rose-500/20 bg-gradient-to-b from-rose-500/5 via-card to-card': match.is_ongoing,
                                            'border-border/80 bg-card hover:border-border': !match.is_ongoing && match.status !== 'completed',
                                            'border-emerald-500/20 bg-muted/10': match.status === 'completed' && !match.is_ongoing,
                                        }"
                                    >
                                        <!-- Top Match Bar: Sequence, Schedule, Live Indicator, Badges -->
                                        <div class="px-4 py-2.5 border-b bg-muted/30 flex items-center justify-between gap-2 text-xs">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="font-mono font-bold text-[11px] text-muted-foreground shrink-0">
                                                    Match #{{ match.sequence || match.id }}
                                                </span>

                                                <!-- Schedule badge / Quick edit button -->
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] transition-colors border cursor-pointer max-w-[160px] truncate"
                                                    :class="match.scheduled_time
                                                        ? 'bg-background text-foreground border-border hover:bg-muted'
                                                        : 'bg-muted/80 text-muted-foreground border-dashed hover:text-foreground'"
                                                    :disabled="competition.is_results_locked"
                                                    @click="openScheduleDialog(match)"
                                                >
                                                    <Clock class="size-3 text-primary shrink-0" />
                                                    <span class="truncate">{{ match.scheduled_time || 'Set Jam' }}</span>
                                                </button>
                                            </div>

                                            <div class="flex items-center gap-1.5 shrink-0">
                                                <!-- LIVE Pill -->
                                                <span
                                                    v-if="match.is_ongoing"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-rose-500 text-white font-extrabold text-[10px] tracking-wide shadow-2xs animate-pulse"
                                                >
                                                    <span class="size-1.5 rounded-full bg-white"></span>
                                                    LIVE
                                                </span>

                                                <!-- Status Badge -->
                                                <Badge
                                                    v-else
                                                    :variant="match.status === 'completed' ? 'default' : 'outline'"
                                                    class="text-[10px] font-semibold h-5 px-1.5"
                                                    :class="{ 'bg-emerald-600 text-white hover:bg-emerald-600': match.status === 'completed' }"
                                                >
                                                    {{ match.status === 'completed' ? 'Selesai' : 'Belum Dimainkan' }}
                                                </Badge>
                                            </div>
                                        </div>

                                        <!-- Highlight Banner for Final / 3rd Place Match -->
                                        <div
                                            v-if="match.match_type === 'final' || (isKnockout && roundLabel(round, 1) === 'Final' && match.match_type !== 'third_place')"
                                            class="px-4 py-1.5 bg-amber-500/10 border-b border-amber-500/20 flex items-center justify-between text-xs font-bold text-amber-700 dark:text-amber-300"
                                        >
                                            <div class="flex items-center gap-1.5">
                                                <Trophy class="size-3.5 text-amber-500" />
                                                <span>🏆 FINAL &mdash; Perebutan Juara 1</span>
                                            </div>
                                            <Badge variant="outline" class="text-[10px] font-extrabold border-amber-400 bg-amber-500/20 text-amber-800 dark:text-amber-200">
                                                Championship
                                            </Badge>
                                        </div>

                                        <div
                                            v-else-if="match.match_type === 'third_place'"
                                            class="px-4 py-1.5 bg-orange-500/10 border-b border-orange-500/20 flex items-center justify-between text-xs font-bold text-orange-700 dark:text-orange-300"
                                        >
                                            <div class="flex items-center gap-1.5">
                                                <Medal class="size-3.5 text-orange-500" />
                                                <span>🥉 MATCH PEREBUTAN JUARA 3</span>
                                            </div>
                                            <Badge variant="outline" class="text-[10px] font-extrabold border-orange-400 bg-orange-500/20 text-orange-800 dark:text-orange-200">
                                                Juara 3 & 4
                                            </Badge>
                                        </div>

                                        <!-- Score Input Card Body -->
                                        <CardContent class="p-4 space-y-4">
                                            <form @submit.prevent="submitScore(match.id)" class="space-y-4">
                                                <!-- Team 1 & Team 2 Input Rows with VS Element in Between -->
                                                <div class="space-y-2.5">
                                                    <!-- HOME TEAM ROW -->
                                                    <div
                                                        class="flex items-center justify-between gap-3 p-2.5 sm:p-3 rounded-xl border transition-all"
                                                        :class="{
                                                            'bg-card border-border shadow-2xs': match.winner_id !== match.participant_id_home,
                                                            'bg-emerald-500/10 border-emerald-500/40 ring-1 ring-emerald-500/30': match.status === 'completed' && match.winner_id === match.participant_id_home,
                                                        }"
                                                    >
                                                        <!-- Team Info: Full Name (Primary) & Short Name (Secondary) -->
                                                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                                            <div
                                                                class="size-8 sm:size-9 rounded-full flex items-center justify-center font-black text-xs shrink-0 shadow-2xs bg-gradient-to-br"
                                                                :class="getTeamColorClass(match.home_participant?.name || 'Home')"
                                                            >
                                                                {{ getInitials(match.home_participant?.name || 'H') }}
                                                            </div>
                                                            <div class="min-w-0 flex-1">
                                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                                    <span class="font-bold text-sm sm:text-base text-foreground break-words leading-tight">
                                                                        {{ match.home_participant?.name ?? 'TBD (Menunggu)' }}
                                                                    </span>
                                                                    <span
                                                                        v-if="match.status === 'completed' && match.winner_id === match.participant_id_home"
                                                                        class="inline-flex items-center text-emerald-600 dark:text-emerald-400 font-extrabold text-[11px] shrink-0"
                                                                    >
                                                                        🏆 Pemenang
                                                                    </span>
                                                                </div>
                                                                <!-- Secondary Short Name -->
                                                                <span
                                                                    v-if="match.home_participant?.short_name"
                                                                    class="text-[11px] font-medium text-muted-foreground block truncate mt-0.5"
                                                                >
                                                                    ({{ match.home_participant.short_name }})
                                                                </span>
                                                                <span
                                                                    v-else
                                                                    class="text-[11px] text-muted-foreground/70 block truncate mt-0.5"
                                                                >
                                                                    Tim Tuan Rumah
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <!-- Stepper & Score Box -->
                                                        <div class="flex items-center gap-1.5 shrink-0">
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="icon"
                                                                class="size-9 rounded-lg shrink-0 touch-manipulation active:scale-95 text-muted-foreground hover:text-foreground"
                                                                :disabled="matchForms[match.id]?.processing || competition.is_results_locked"
                                                                @click="stepScore(match.id, 'home', -1)"
                                                            >
                                                                <Minus class="size-4" />
                                                                <span class="sr-only">Kurangi Skor Tuan Rumah</span>
                                                            </Button>

                                                            <Input
                                                                v-if="matchForms[match.id]"
                                                                v-model="matchForms[match.id].score_home"
                                                                type="number"
                                                                inputmode="numeric"
                                                                min="0"
                                                                placeholder="0"
                                                                class="w-13 sm:w-16 h-10 text-center text-lg sm:text-xl font-black rounded-lg bg-background border-2 border-primary/20 focus:border-primary"
                                                                :disabled="matchForms[match.id].processing || competition.is_results_locked"
                                                            />

                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="icon"
                                                                class="size-9 rounded-lg shrink-0 touch-manipulation active:scale-95 text-primary hover:bg-primary/10"
                                                                :disabled="matchForms[match.id]?.processing || competition.is_results_locked"
                                                                @click="stepScore(match.id, 'home', 1)"
                                                            >
                                                                <Plus class="size-4" />
                                                                <span class="sr-only">Tambah Skor Tuan Rumah</span>
                                                            </Button>
                                                        </div>
                                                    </div>

                                                    <!-- STYLISH VS BADGE DIVIDER -->
                                                    <div class="relative flex items-center justify-center py-0.5">
                                                        <div class="absolute inset-0 flex items-center">
                                                            <div class="w-full border-t border-dashed border-border/80"></div>
                                                        </div>
                                                        <div class="relative px-3 py-0.5 rounded-full bg-muted/90 dark:bg-muted text-[10px] font-black tracking-widest text-muted-foreground border border-border/60 shadow-2xs flex items-center gap-1.5 select-none">
                                                            <span>VS</span>
                                                        </div>
                                                    </div>

                                                    <!-- AWAY TEAM ROW -->
                                                    <div
                                                        class="flex items-center justify-between gap-3 p-2.5 sm:p-3 rounded-xl border transition-all"
                                                        :class="{
                                                            'bg-card border-border shadow-2xs': match.winner_id !== match.participant_id_away,
                                                            'bg-emerald-500/10 border-emerald-500/40 ring-1 ring-emerald-500/30': match.status === 'completed' && match.winner_id === match.participant_id_away,
                                                        }"
                                                    >
                                                        <!-- Team Info: Full Name (Primary) & Short Name (Secondary) -->
                                                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                                            <div
                                                                class="size-8 sm:size-9 rounded-full flex items-center justify-center font-black text-xs shrink-0 shadow-2xs bg-gradient-to-br"
                                                                :class="getTeamColorClass(match.away_participant?.name || 'Away')"
                                                            >
                                                                {{ getInitials(match.away_participant?.name || 'A') }}
                                                            </div>
                                                            <div class="min-w-0 flex-1">
                                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                                    <span class="font-bold text-sm sm:text-base text-foreground break-words leading-tight">
                                                                        {{ match.away_participant?.name ?? 'TBD (Menunggu)' }}
                                                                    </span>
                                                                    <span
                                                                        v-if="match.status === 'completed' && match.winner_id === match.participant_id_away"
                                                                        class="inline-flex items-center text-emerald-600 dark:text-emerald-400 font-extrabold text-[11px] shrink-0"
                                                                    >
                                                                        🏆 Pemenang
                                                                    </span>
                                                                </div>
                                                                <!-- Secondary Short Name -->
                                                                <span
                                                                    v-if="match.away_participant?.short_name"
                                                                    class="text-[11px] font-medium text-muted-foreground block truncate mt-0.5"
                                                                >
                                                                    ({{ match.away_participant.short_name }})
                                                                </span>
                                                                <span
                                                                    v-else
                                                                    class="text-[11px] text-muted-foreground/70 block truncate mt-0.5"
                                                                >
                                                                    Tim Tamu
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <!-- Stepper & Score Box -->
                                                        <div class="flex items-center gap-1.5 shrink-0">
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="icon"
                                                                class="size-9 rounded-lg shrink-0 touch-manipulation active:scale-95 text-muted-foreground hover:text-foreground"
                                                                :disabled="matchForms[match.id]?.processing || competition.is_results_locked"
                                                                @click="stepScore(match.id, 'away', -1)"
                                                            >
                                                                <Minus class="size-4" />
                                                                <span class="sr-only">Kurangi Skor Tim Tamu</span>
                                                            </Button>

                                                            <Input
                                                                v-if="matchForms[match.id]"
                                                                v-model="matchForms[match.id].score_away"
                                                                type="number"
                                                                inputmode="numeric"
                                                                min="0"
                                                                placeholder="0"
                                                                class="w-13 sm:w-16 h-10 text-center text-lg sm:text-xl font-black rounded-lg bg-background border-2 border-primary/20 focus:border-primary"
                                                                :disabled="matchForms[match.id]?.processing || competition.is_results_locked"
                                                            />

                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="icon"
                                                                class="size-9 rounded-lg shrink-0 touch-manipulation active:scale-95 text-primary hover:bg-primary/10"
                                                                :disabled="matchForms[match.id]?.processing || competition.is_results_locked"
                                                                @click="stepScore(match.id, 'away', 1)"
                                                            >
                                                                <Plus class="size-4" />
                                                                <span class="sr-only">Tambah Skor Tim Tamu</span>
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Knockout Tie-Break Decision Card -->
                                                <div
                                                    v-if="isTieInKnockout(match.id)"
                                                    class="rounded-xl border border-amber-500/40 bg-amber-500/10 p-3.5 space-y-3 animate-in fade-in zoom-in-95 duration-200"
                                                >
                                                    <div class="flex items-center gap-2 text-xs font-bold text-amber-800 dark:text-amber-300">
                                                        <AlertCircle class="size-4 shrink-0 text-amber-600" />
                                                        <span>Skor Imbang (Sistem Gugur). Pilih Tim Pemenang Adu Penalti / Tie-Break:</span>
                                                    </div>

                                                    <!-- Winner Selection Cards -->
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <button
                                                            v-if="match.home_participant"
                                                            type="button"
                                                            class="p-2.5 rounded-lg border text-left text-xs font-bold transition-all flex items-center justify-between cursor-pointer"
                                                            :class="matchForms[match.id].winner_id === String(match.home_participant.id)
                                                                ? 'bg-amber-500 text-white border-amber-600 shadow-2xs'
                                                                : 'bg-card text-foreground hover:bg-muted border-border'"
                                                            :disabled="competition.is_results_locked"
                                                            @click="matchForms[match.id].winner_id = String(match.home_participant.id)"
                                                        >
                                                            <div class="truncate">
                                                                <div>{{ match.home_participant.name }}</div>
                                                                <div v-if="match.home_participant.short_name" class="text-[10px] opacity-80">({{ match.home_participant.short_name }})</div>
                                                            </div>
                                                            <Check v-if="matchForms[match.id].winner_id === String(match.home_participant.id)" class="size-4 shrink-0 ml-1" />
                                                        </button>

                                                        <button
                                                            v-if="match.away_participant"
                                                            type="button"
                                                            class="p-2.5 rounded-lg border text-left text-xs font-bold transition-all flex items-center justify-between cursor-pointer"
                                                            :class="matchForms[match.id].winner_id === String(match.away_participant.id)
                                                                ? 'bg-amber-500 text-white border-amber-600 shadow-2xs'
                                                                : 'bg-card text-foreground hover:bg-muted border-border'"
                                                            :disabled="competition.is_results_locked"
                                                            @click="matchForms[match.id].winner_id = String(match.away_participant.id)"
                                                        >
                                                            <div class="truncate">
                                                                <div>{{ match.away_participant.name }}</div>
                                                                <div v-if="match.away_participant.short_name" class="text-[10px] opacity-80">({{ match.away_participant.short_name }})</div>
                                                            </div>
                                                            <Check v-if="matchForms[match.id].winner_id === String(match.away_participant.id)" class="size-4 shrink-0 ml-1" />
                                                        </button>
                                                    </div>

                                                    <div class="space-y-1">
                                                        <label class="text-[11px] font-medium text-amber-800 dark:text-amber-300">
                                                            Keterangan Tie-Break (Opsional)
                                                        </label>
                                                        <Input
                                                            v-model="matchForms[match.id].win_method"
                                                            placeholder="Contoh: Adu Penalti 5-4, Golden Goal"
                                                            class="h-8 text-xs bg-background"
                                                            :disabled="competition.is_results_locked"
                                                        />
                                                    </div>
                                                </div>

                                                <!-- Form Validation Error -->
                                                <div
                                                    v-if="matchForms[match.id] && Object.keys(matchForms[match.id].errors).length > 0"
                                                    class="p-2.5 rounded-lg bg-rose-500/10 border border-rose-500/30 text-xs font-semibold text-rose-600 flex items-center gap-1.5"
                                                >
                                                    <AlertCircle class="size-4 shrink-0" />
                                                    <span>{{ Object.values(matchForms[match.id].errors)[0] }}</span>
                                                </div>

                                                <!-- Card Action Bar: Live Toggle & Simpan Skor -->
                                                <div class="pt-2 border-t flex items-center justify-between gap-2">
                                                    <!-- Left: Result version & Live Broadcast button -->
                                                    <div class="flex items-center gap-1.5">
                                                        <Button
                                                            v-if="match.participant_id_home && match.participant_id_away && match.status !== 'completed'"
                                                            type="button"
                                                            size="sm"
                                                            class="h-9 px-3 rounded-lg text-xs font-bold transition-all shadow-2xs gap-1.5 cursor-pointer"
                                                            :class="match.is_ongoing
                                                                ? 'bg-rose-500 hover:bg-rose-600 text-white animate-pulse'
                                                                : 'border-rose-300 dark:border-rose-800 text-rose-600 dark:text-rose-400 hover:bg-rose-500/10'"
                                                            :variant="match.is_ongoing ? 'default' : 'outline'"
                                                            :disabled="toggleOngoingProcessing[match.id] || competition.is_results_locked"
                                                            @click="submitToggleOngoing(match.id)"
                                                        >
                                                            <Radio class="size-3.5" :class="{ 'animate-spin': toggleOngoingProcessing[match.id] }" />
                                                            <span>{{ match.is_ongoing ? 'LIVE ON' : 'SET LIVE' }}</span>
                                                        </Button>

                                                        <span class="text-[10px] font-mono text-muted-foreground hidden sm:inline">
                                                            v{{ match.result_version }}
                                                        </span>
                                                    </div>

                                                    <!-- Right: Save Score Button -->
                                                    <div class="flex items-center gap-2">
                                                        <Button
                                                            type="submit"
                                                            size="sm"
                                                            class="h-9 px-4 rounded-lg text-xs font-bold gap-1.5 transition-all shadow-2xs cursor-pointer"
                                                            :class="{
                                                                'bg-emerald-600 hover:bg-emerald-700 text-white': recentlySaved[match.id],
                                                            }"
                                                            :disabled="matchForms[match.id]?.processing || competition.is_results_locked"
                                                        >
                                                            <Check v-if="recentlySaved[match.id]" class="size-4 text-white" />
                                                            <Save v-else class="size-3.5" />
                                                            <span>
                                                                {{ matchForms[match.id]?.processing
                                                                    ? 'Menyimpan...'
                                                                    : (recentlySaved[match.id] ? 'Tersimpan!' : 'Simpan Skor')
                                                                }}
                                                            </span>
                                                        </Button>
                                                    </div>
                                                </div>
                                            </form>
                                        </CardContent>
                                    </Card>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Empty State (Filter / Search) -->
                <Card v-if="playableMatches.length === 0" class="p-8 text-center rounded-2xl">
                    <div class="max-w-xs mx-auto space-y-2">
                        <Trophy class="size-10 text-muted-foreground/40 mx-auto" />
                        <h3 class="font-bold text-sm text-foreground">Belum Ada Pertandingan</h3>
                        <p class="text-xs text-muted-foreground">
                            Pertandingan belum diundi. Lakukan pengundian (draw) terlebih dahulu di menu Undian.
                        </p>
                    </div>
                </Card>

                <Card v-else-if="filteredRounds.every(r => getVisibleMatchesCountForRound(r) === 0)" class="p-8 text-center rounded-2xl">
                    <div class="max-w-xs mx-auto space-y-3">
                        <Search class="size-8 text-muted-foreground/40 mx-auto" />
                        <div>
                            <h3 class="font-bold text-sm text-foreground">Tidak Ada Pertandingan yang Cocok</h3>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                Coba ubah filter status atau kata kunci pencarian tim.
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            class="text-xs"
                            @click="selectedStatus = 'all'; selectedRound = 'all'; searchQuery = ''"
                        >
                            Reset Semua Filter
                        </Button>
                    </div>
                </Card>
            </div>
        </main>

        <!-- Quick Schedule Modal Dialog (WITA Timezone) -->
        <Dialog v-model:open="scheduleModalOpen">
            <DialogContent class="sm:max-w-md rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-base">
                        <Clock class="size-4 text-primary" />
                        Atur Waktu Pertandingan (WITA)
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        <span class="font-semibold text-foreground">{{ selectedMatchForSchedule?.home_participant?.name ?? 'TBD' }}</span>
                        <span v-if="selectedMatchForSchedule?.home_participant?.short_name" class="text-muted-foreground"> ({{ selectedMatchForSchedule.home_participant.short_name }})</span>
                        <span class="mx-1.5 font-bold text-primary">VS</span>
                        <span class="font-semibold text-foreground">{{ selectedMatchForSchedule?.away_participant?.name ?? 'TBD' }}</span>
                        <span v-if="selectedMatchForSchedule?.away_participant?.short_name" class="text-muted-foreground"> ({{ selectedMatchForSchedule.away_participant.short_name }})</span>
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitQuickSchedule" class="space-y-4 py-2">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-foreground">Pilih Jam Cepat (WITA)</label>
                        <div class="grid grid-cols-4 gap-1.5">
                            <button
                                v-for="preset in quickTimePresets"
                                :key="preset"
                                type="button"
                                class="px-2 py-1.5 rounded-lg border text-xs font-semibold transition-all text-center cursor-pointer"
                                :class="scheduleForm.scheduled_time === preset
                                    ? 'bg-primary text-primary-foreground border-primary shadow-2xs'
                                    : 'bg-muted/50 text-foreground hover:bg-muted border-border'"
                                @click="setQuickTime(preset)"
                            >
                                {{ preset.replace(' WITA', '') }}
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-foreground">Kustom Waktu / Jam (WITA)</label>
                        <Input
                            v-model="scheduleForm.scheduled_time"
                            placeholder="Contoh: 14:30 WITA / Lapangan 2"
                            class="h-9 text-xs"
                            :disabled="competition.is_results_locked"
                        />
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0 pt-2">
                        <DialogClose as-child>
                            <Button type="button" variant="outline" size="sm" class="text-xs">
                                Batal
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            size="sm"
                            class="text-xs font-bold gap-1.5"
                            :disabled="scheduleForm.processing || competition.is_results_locked"
                        >
                            <Save class="size-3.5" />
                            <span>{{ scheduleForm.processing ? 'Menyimpan...' : 'Simpan Jadwal' }}</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Modal Konfirmasi Kunci / Buka Kunci Hasil Pertandingan (Admin) -->
        <Dialog :open="lockResultsDialogOpen" @update:open="lockResultsDialogOpen = $event">
            <DialogContent class="sm:max-w-md rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-base">
                        <component :is="competition.is_results_locked ? Unlock : Lock" class="size-4 text-primary" />
                        {{ competition.is_results_locked ? 'Konfirmasi Buka Kunci Hasil' : 'Konfirmasi Kunci Hasil Pertandingan' }}
                    </DialogTitle>
                    <DialogDescription class="space-y-2 pt-2 text-xs">
                        <template v-if="competition.is_results_locked">
                            <p>Membuka kunci hasil pertandingan akan mengizinkan Admin dan Operator untuk mengedit kembali skor pertandingan.</p>
                            <p class="font-medium text-foreground">Apakah Anda yakin ingin membuka kunci hasil pertandingan?</p>
                        </template>
                        <template v-else>
                            <p>Mengunci hasil pertandingan menandakan bahwa seluruh skor sudah final.</p>
                            <ul class="list-disc pl-5 text-xs text-muted-foreground space-y-1 my-2">
                                <li>Skor pertandingan tidak dapat diubah lagi oleh Admin maupun Operator.</li>
                                <li>Klasemen dan status kompetisi dinyatakan final.</li>
                                <li>Anda dapat membuka kunci ini kembali jika di kemudian hari diperlukan revisi.</li>
                            </ul>
                            <p class="font-medium text-foreground">Apakah Anda yakin ingin mengunci hasil pertandingan?</p>
                        </template>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0 pt-2">
                    <DialogClose as-child>
                        <Button variant="outline" size="sm" class="text-xs" :disabled="lockResultsForm.processing">
                            Batal
                        </Button>
                    </DialogClose>
                    <Button
                        :variant="competition.is_results_locked ? 'outline' : 'default'"
                        size="sm"
                        class="text-xs font-bold"
                        :disabled="lockResultsForm.processing"
                        @click="executeToggleResultsLock"
                    >
                        <component :is="competition.is_results_locked ? Unlock : Lock" class="mr-1.5 size-3.5" />
                        {{ lockResultsForm.processing ? 'Memproses...' : (competition.is_results_locked ? 'Ya, Buka Kunci' : 'Ya, Kunci Hasil') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
