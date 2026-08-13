<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, LayoutGrid, Medal, Trophy, CheckCircle2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { index as homeLanding } from '@/actions/App/Http/Controllers/PublicCompetitionController';
import CompetitionOverviewCard from '@/components/Public/Competition/CompetitionOverviewCard.vue';
import GroupFinalFourStageView from '@/components/Public/Competition/GroupFinalFourStageView.vue';
import KnockoutBracketView from '@/components/Public/Competition/KnockoutBracketView.vue';
import KnockoutRoundsView from '@/components/Public/Competition/KnockoutRoundsView.vue';
import LeagueMatchesList from '@/components/Public/Competition/LeagueMatchesList.vue';
import LeagueStandingsTable from '@/components/Public/Competition/LeagueStandingsTable.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

import PublicLayout from '@/layouts/PublicLayout.vue';

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
        sport: string | null;
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
    locked: 'Terkunci / Siap',
    in_progress: 'Sedang Berlangsung',
    completed: 'Selesai',
};

const formatLabel: Record<string, string> = {
    knockout: 'Knockout (Gugur)',
    final_four: 'Final Four',
    group_final_four: 'Group Final Four',
    group_four_final: 'Group Final Four',
    full_competition: 'Kompetisi Penuh (Liga)',
    half_competition: 'Setengah Kompetisi',
};

const isKnockout = computed(() => props.competition.format === 'knockout' || props.competition.format === 'final_four');
const isGroupFinalFour = computed(() => props.competition.format === 'group_final_four' || props.competition.format === 'group_four_final');

const sortedRounds = computed(() => {
    return Object.keys(props.matchesByRound)
        .map(Number)
        .sort((a, b) => a - b);
});

// Knockout specific state & refs
const selectedKnockoutRound = ref<number>(sortedRounds.value[0] || 1);
const knockoutViewMode = ref<'rounds' | 'bracket'>('rounds');
const knockoutStatusFilter = ref<'all' | 'completed' | 'ready' | 'bye'>('all');

// League specific state & refs
const activeTab = ref<'standings' | 'bracket' | 'matches'>(props.standings?.length ? 'standings' : 'matches');
const selectedLeagueRound = ref<number | 'all'>('all');
const leagueStatusFilter = ref<'all' | 'completed' | 'ready'>('all');

// All matches count & progress
const allMatchesList = computed(() => {
    return Object.values(props.matchesByRound).flat();
});

const finalMatch = computed(() => {
    return allMatchesList.value.find(m => m.match_type === 'final');
});

const thirdPlaceMatch = computed(() => {
    return allMatchesList.value.find(m => m.match_type === 'third_place');
});

const completedMatchesCount = computed(() => {
    return allMatchesList.value.filter(m => m.status === 'completed' || m.status === 'bye').length;
});

const totalScorableMatchesCount = computed(() => {
    return allMatchesList.value.filter(m => m.status !== 'bye').length;
});

const matchProgressPercentage = computed(() => {
    if (totalScorableMatchesCount.value === 0) {
return 0;
}

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

const filteredKnockoutMatches = computed(() => {
    const roundMatches = props.matchesByRound[selectedKnockoutRound.value] || [];

    if (knockoutStatusFilter.value === 'all') {
return roundMatches;
}

    if (knockoutStatusFilter.value === 'completed') {
return roundMatches.filter(m => m.status === 'completed');
}

    if (knockoutStatusFilter.value === 'ready') {
return roundMatches.filter(m => m.status === 'ready' || m.status === 'in_progress');
}

    if (knockoutStatusFilter.value === 'bye') {
return roundMatches.filter(m => m.status === 'bye');
}

    return roundMatches;
});

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
            <CompetitionOverviewCard
                :competition="competition"
                :sport="competition.sport"
                :participants-count="props.participants.length"
                :completed-matches-count="completedMatchesCount"
                :total-scorable-matches-count="totalScorableMatchesCount"
                :match-progress-percentage="matchProgressPercentage"
                :format-label="formatLabel"
                :status-label="statusLabel"
            />

            <!-- KNOCKOUT FORMAT VIEW -->
            <div v-if="isKnockout" class="space-y-6">
                <!-- Knockout View Switcher -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-card border border-border/80 p-3 rounded-2xl shadow-2xs">
                    <div class="flex items-center gap-1.5 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                        <button
                            type="button"
                            @click="knockoutViewMode = 'rounds'"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all shrink-0"
                            :class="knockoutViewMode === 'rounds' ? 'bg-primary text-primary-foreground shadow-xs' : 'bg-muted/50 text-muted-foreground hover:bg-accent'"
                            :aria-pressed="knockoutViewMode === 'rounds'"
                        >
                            <LayoutGrid class="size-4" />
                            <span>📱 Tampilan Per Babak (Mobile Friendly)</span>
                        </button>
                        <button
                            type="button"
                            @click="knockoutViewMode = 'bracket'"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all shrink-0"
                            :class="knockoutViewMode === 'bracket' ? 'bg-primary text-primary-foreground shadow-xs' : 'bg-muted/50 text-muted-foreground hover:bg-accent'"
                            :aria-pressed="knockoutViewMode === 'bracket'"
                        >
                            <Medal class="size-4" />
                            <span>🖥️ Bagan Utuh (Bracket)</span>
                        </button>
                    </div>

                    <div class="text-xs text-muted-foreground hidden lg:block">
                        <span class="italic">Tip: Gunakan "Tampilan Per Babak" untuk HP agar mudah memilih match</span>
                    </div>
                </div>

                <!-- MODE 1: TAMPILAN PER BABAK -->
                <KnockoutRoundsView
                    v-if="knockoutViewMode === 'rounds'"
                    :sorted-rounds="sortedRounds"
                    :selected-round="selectedKnockoutRound"
                    :status-filter="knockoutStatusFilter"
                    :filtered-matches="filteredKnockoutMatches"
                    :matches-by-round="matchesByRound"
                    :round-label="roundLabel"
                    @update:selected-round="selectedKnockoutRound = $event"
                    @update:status-filter="knockoutStatusFilter = $event"
                />

                <!-- MODE 2: BAGAN UTUH (BRACKET TREE VIEW) -->
                <KnockoutBracketView
                    v-else
                    :sorted-rounds="sortedRounds"
                    :matches-by-round="matchesByRound"
                    :round-label="roundLabel"
                />
            </div>

            <!-- LIGA / ROUND ROBIN FORMAT VIEW -->
            <div v-else class="space-y-6">
                <!-- Navigation Tabs (Klasemen vs Bagan Final vs Pertandingan) -->
                <div class="flex flex-wrap items-center gap-2 border-b border-border pb-3">
                    <Button
                        v-if="standings.length > 0"
                        :variant="activeTab === 'standings' ? 'default' : 'outline'"
                        size="sm"
                        @click="activeTab = 'standings'"
                        class="rounded-xl px-4 font-bold text-xs sm:text-sm"
                    >
                        <Trophy class="mr-1.5 size-4 text-amber-400" />
                        {{ isGroupFinalFour ? 'Klasemen 2 Grup' : 'Klasemen Perolehan Poin' }}
                    </Button>
                    <Button
                        v-if="isGroupFinalFour"
                        :variant="activeTab === 'bracket' ? 'default' : 'outline'"
                        size="sm"
                        @click="activeTab = 'bracket'"
                        class="rounded-xl px-4 font-bold text-xs sm:text-sm"
                    >
                        <Medal class="mr-1.5 size-4 text-emerald-400" />
                        Bagan & Stage Final Placement (1v2 & 3v4)
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

                <!-- TAB 1: KLASEMEN -->
                <div v-if="activeTab === 'standings' && standings.length > 0" class="space-y-6">
                    <LeagueStandingsTable
                        :standings="standings"
                        :group-standings="groupStandings"
                    />

                    <!-- Detail Info Stage Selanjutnya untuk Group Final Four (Final 1v2 & Final 3v4) -->
                    <GroupFinalFourStageView
                        v-if="isGroupFinalFour"
                        :final-match="finalMatch"
                        :third-place-match="thirdPlaceMatch"
                        :standings-a="groupStandings?.group_a"
                        :standings-b="groupStandings?.group_b"
                    />
                </div>

                <!-- TAB 2: BAGAN FINAL PLACEMENT (GROUP FINAL FOUR) -->
                <div v-if="activeTab === 'bracket' && isGroupFinalFour" class="space-y-6">
                    <GroupFinalFourStageView
                        :final-match="finalMatch"
                        :third-place-match="thirdPlaceMatch"
                        :standings-a="groupStandings?.group_a"
                        :standings-b="groupStandings?.group_b"
                    />
                </div>

                <!-- TAB 3: PERTANDINGAN -->
                <LeagueMatchesList
                    v-if="activeTab === 'matches'"
                    :sorted-rounds="sortedRounds"
                    :selected-round="selectedLeagueRound"
                    :status-filter="leagueStatusFilter"
                    :filtered-matches="filteredLeagueMatches"
                    :matches-by-round="matchesByRound"
                    :round-label="roundLabel"
                    @update:selected-round="selectedLeagueRound = $event"
                    @update:status-filter="leagueStatusFilter = $event"
                />
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
