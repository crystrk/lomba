<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Trophy, Users, LogIn, Medal, CheckCircle2 } from '@lucide/vue';
import { index as homeLanding } from '@/actions/App/Http/Controllers/PublicCompetitionController';
import { login } from '@/routes';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

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
    locked: 'Terkunci',
    in_progress: 'Berlangsung',
    completed: 'Selesai',
};

const statusVariant: Record<string, 'default' | 'outline' | 'secondary'> = {
    locked: 'default',
    in_progress: 'default',
    completed: 'outline',
};

const isKnockout = computed(() => props.competition.format === 'knockout');

const sortedRounds = computed(() => {
    return Object.keys(props.matchesByRound)
        .map(Number)
        .sort((a, b) => a - b);
});

const activeTab = ref<'standings' | 'matches'>('matches');

function roundLabel(round: number, leg: number): string {
    if (isKnockout.value) {
        const total = sortedRounds.value.length;
        const mapping: Record<number, string> = { 1: 'Final', 2: 'Semifinal', 3: 'Perempat Final' };
        const fromEnd = total - round + 1;
        return mapping[fromEnd] || `Babak ${round}`;
    }
    return leg > 1 ? `Pekan ${round} - Leg ${leg}` : `Pekan ${round}`;
}
</script>

<template>
    <Head :title="competition.name" />
    <div class="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a]">
        <header class="flex items-center justify-between border-b px-6 py-4 bg-background/80 backdrop-blur">
            <Link :href="homeLanding()" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:underline">
                <ArrowLeft class="size-4" />
                <span>Kembali ke Beranda</span>
            </Link>
            <Link
                v-if="!$page.props.auth.user"
                :href="login()"
                class="inline-flex items-center gap-1.5 rounded-md border px-4 py-1.5 text-sm font-medium transition-colors hover:bg-accent"
            >
                <LogIn class="size-4" />
                <span>Login Staf</span>
            </Link>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-8">
            <div class="mb-6 space-y-2">
                <h1 class="text-3xl font-bold tracking-tight">{{ competition.name }}</h1>
                <div class="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                    <Badge variant="secondary">
                        {{ competition.format === 'knockout' ? 'Knockout' : competition.format === 'full_competition' ? 'Kompetisi Penuh' : 'Setengah Kompetisi' }}
                    </Badge>
                    <Badge :variant="statusVariant[competition.status] || 'secondary'">
                        {{ statusLabel[competition.status] || competition.status }}
                    </Badge>
                    <span class="flex items-center gap-1">
                        <Users class="size-4" />
                        {{ props.participants.length }} Peserta
                    </span>
                </div>
                <div v-if="competition.win_points !== null" class="text-xs text-muted-foreground font-mono">
                    Scoring Rule: Menang {{ competition.win_points }} | Seri {{ competition.draw_points }} | Kalah {{ competition.loss_points }}
                </div>
            </div>

            <div v-if="isKnockout">
                <!-- Knockout bracket view -->
                <div class="overflow-x-auto pb-4">
                    <div class="flex gap-8" style="min-width: max-content;">
                        <div v-for="round in sortedRounds" :key="round" class="flex flex-col gap-4">
                            <h3 class="text-sm font-semibold text-muted-foreground flex items-center gap-1.5">
                                <Medal class="size-4" />
                                {{ roundLabel(round, 1) }}
                            </h3>
                            <div v-for="match in matchesByRound[round]" :key="match.id" class="w-60 rounded-xl border bg-card p-3 shadow-sm">
                                <template v-if="match.status === 'bye'">
                                    <div class="text-xs text-muted-foreground italic">Bye</div>
                                    <div class="mt-1 font-semibold text-foreground">{{ match.home?.name || '??' }}</div>
                                </template>
                                <template v-else>
                                    <div class="flex items-center justify-between gap-2 py-1" :class="match.winner_id === match.home?.id ? 'font-bold text-primary' : ''">
                                        <span class="truncate text-sm">{{ match.home?.name || '??' }}</span>
                                        <span v-if="match.score_home !== null" class="text-sm font-mono font-bold">{{ match.score_home }}</span>
                                        <span v-else-if="match.status === 'completed'" class="text-xs text-muted-foreground">-</span>
                                    </div>
                                    <div class="border-t my-1" />
                                    <div class="flex items-center justify-between gap-2 py-1" :class="match.winner_id === match.away?.id ? 'font-bold text-primary' : ''">
                                        <span class="truncate text-sm">{{ match.away?.name || '??' }}</span>
                                        <span v-if="match.score_away !== null" class="text-sm font-mono font-bold">{{ match.score_away }}</span>
                                        <span v-else-if="match.status === 'completed'" class="text-xs text-muted-foreground">-</span>
                                    </div>
                                    <div v-if="match.status !== 'completed' && match.home && match.away" class="mt-1 text-xs text-muted-foreground">Belum dimainkan</div>
                                    <div v-if="match.home === null || match.away === null" class="mt-1 text-xs text-muted-foreground italic">Menunggu pemenang</div>
                                    <div v-if="match.win_method" class="mt-1 text-xs font-semibold text-amber-600 dark:text-amber-400">{{ match.win_method }}</div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else>
                <!-- Competition format: tabs -->
                <div v-if="standings.length > 0" class="mb-6 flex gap-2">
                    <Button
                        :variant="activeTab === 'standings' ? 'default' : 'outline'"
                        size="sm"
                        @click="activeTab = 'standings'"
                    >
                        <Trophy class="mr-1.5 size-4" />
                        Klasemen
                    </Button>
                    <Button
                        :variant="activeTab === 'matches' ? 'default' : 'outline'"
                        size="sm"
                        @click="activeTab = 'matches'"
                    >
                        <CheckCircle2 class="mr-1.5 size-4" />
                        Pertandingan
                    </Button>
                </div>

                <div v-if="activeTab === 'standings' && standings.length > 0">
                    <Card>
                        <CardHeader class="border-b py-3 bg-muted/20">
                            <CardTitle class="text-base font-semibold flex items-center gap-2">
                                <Trophy class="size-4 text-amber-500" />
                                Klasemen Lomba
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-12 text-center">#</TableHead>
                                        <TableHead>Tim</TableHead>
                                        <TableHead class="text-center">Main</TableHead>
                                        <TableHead class="text-center">Menang</TableHead>
                                        <TableHead class="text-center">Seri</TableHead>
                                        <TableHead class="text-center">Kalah</TableHead>
                                        <TableHead class="text-center hidden sm:table-cell">GM</TableHead>
                                        <TableHead class="text-center hidden sm:table-cell">GK</TableHead>
                                        <TableHead class="text-center">Selisih</TableHead>
                                        <TableHead class="text-center font-bold">Poin</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="entry in standings" :key="entry.participant_id">
                                        <TableCell class="font-medium text-center">
                                            <Badge v-if="entry.rank === 1" class="bg-amber-500 hover:bg-amber-600">1</Badge>
                                            <span v-else>{{ entry.rank }}</span>
                                        </TableCell>
                                        <TableCell class="font-semibold">{{ entry.participant_name }}</TableCell>
                                        <TableCell class="text-center">{{ entry.played }}</TableCell>
                                        <TableCell class="text-center text-emerald-600 font-medium">{{ entry.won }}</TableCell>
                                        <TableCell class="text-center text-amber-600">{{ entry.drawn }}</TableCell>
                                        <TableCell class="text-center text-rose-600">{{ entry.lost }}</TableCell>
                                        <TableCell class="text-center hidden sm:table-cell">{{ entry.score_for }}</TableCell>
                                        <TableCell class="text-center hidden sm:table-cell">{{ entry.score_against }}</TableCell>
                                        <TableCell class="text-center font-mono" :class="entry.difference >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                                            {{ entry.difference >= 0 ? '+' : '' }}{{ entry.difference }}
                                        </TableCell>
                                        <TableCell class="text-center font-bold text-base">{{ entry.points }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                <div v-if="activeTab === 'matches'">
                    <div v-for="round in sortedRounds" :key="round" class="mb-6 space-y-3">
                        <h3 class="text-sm font-semibold text-muted-foreground flex items-center gap-1.5">
                            <Medal class="size-4" />
                            {{ roundLabel(round, matchesByRound[round][0]?.leg ?? 1) }}
                        </h3>
                        <Card>
                            <CardContent class="p-0 divide-y">
                                <div v-for="match in matchesByRound[round]" :key="match.id" class="flex items-center gap-4 px-4 py-3 text-sm">
                                    <template v-if="match.status === 'bye'">
                                        <span class="text-muted-foreground italic">Bye</span>
                                        <span class="font-medium">{{ match.home?.name || '??' }}</span>
                                    </template>
                                    <template v-else>
                                        <span v-if="match.home" class="flex-1 text-right font-medium truncate" :class="match.winner_id === match.home.id ? 'font-bold text-primary' : ''">{{ match.home.name }}</span>
                                        <span v-else class="flex-1 text-right text-muted-foreground italic truncate">Menunggu</span>

                                        <span v-if="match.score_home !== null" class="w-8 text-center font-mono font-bold">{{ match.score_home }}</span>
                                        <span v-else class="w-8 text-center text-muted-foreground">-</span>

                                        <span class="text-muted-foreground font-bold">:</span>

                                        <span v-if="match.score_away !== null" class="w-8 text-center font-mono font-bold">{{ match.score_away }}</span>
                                        <span v-else class="w-8 text-center text-muted-foreground">-</span>

                                        <span v-if="match.away" class="flex-1 text-left font-medium truncate" :class="match.winner_id === match.away.id ? 'font-bold text-primary' : ''">{{ match.away.name }}</span>
                                        <span v-else class="flex-1 text-left text-muted-foreground italic truncate">Menunggu</span>

                                        <span v-if="match.status !== 'completed' && match.home && match.away" class="text-xs text-muted-foreground shrink-0">Belum dimainkan</span>
                                        <span v-if="match.win_method" class="text-xs text-amber-600 font-semibold shrink-0">{{ match.win_method }}</span>
                                    </template>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <Card v-if="sortedRounds.length === 0" class="p-8 text-center text-muted-foreground">
                Data pertandingan belum tersedia.
            </Card>
        </main>
    </div>
</template>

