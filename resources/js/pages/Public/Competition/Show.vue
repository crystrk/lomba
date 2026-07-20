<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { login } from '@/routes';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

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
    matchesByRound: Record<number, Array<{
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

function matchResult(m: any): string {
    if (m.status === 'bye') return 'Bye';
    if (m.status !== 'completed') return 'Belum dimainkan';
    if (m.score_home === m.score_away && m.win_method) return `${m.score_home} - ${m.score_away} (${m.win_method})`;
    return `${m.score_home} - ${m.score_away}`;
}
</script>

<template>
    <Head :title="competition.name" />
    <div class="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a]">
        <header class="flex items-center justify-between px-6 py-4">
            <Link href="/" class="text-sm text-muted-foreground hover:underline">&larr; Kembali</Link>
            <Link
                v-if="!$page.props.auth.user"
                :href="login()"
                class="rounded-md border px-4 py-1.5 text-sm hover:bg-accent"
            >
                Login Staf
            </Link>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold">{{ competition.name }}</h1>
                <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                    <Badge variant="secondary">{{ competition.format === 'knockout' ? 'Gugur' : competition.format === 'full_competition' ? 'Kompetisi Penuh' : 'Setengah Kompetisi' }}</Badge>
                    <Badge :variant="statusVariant[competition.status] || 'secondary'">{{ statusLabel[competition.status] || competition.status }}</Badge>
                    <span>{{ props.participants.length }} peserta</span>
                </div>
                <div v-if="competition.win_points !== null" class="mt-1 text-sm text-muted-foreground">
                    Poin: {{ competition.win_points }}/{{ competition.draw_points }}/{{ competition.loss_points }}
                </div>
            </div>

            <div v-if="isKnockout">
                <!-- Knockout bracket view -->
                <div class="overflow-x-auto pb-4">
                    <div class="flex gap-8" style="min-width: max-content;">
                        <div v-for="round in sortedRounds" :key="round" class="flex flex-col gap-4">
                            <h3 class="text-sm font-semibold text-muted-foreground">{{ roundLabel(round, 1) }}</h3>
                            <div v-for="match in matchesByRound[round]" :key="match.id" class="w-56 rounded-lg border p-3">
                                <template v-if="match.status === 'bye'">
                                    <div class="text-sm text-muted-foreground italic">Bye</div>
                                    <div class="mt-1 font-medium">{{ match.home?.name || '??' }}</div>
                                </template>
                                <template v-else>
                                    <div class="flex items-center justify-between gap-2 py-0.5" :class="match.winner_id === match.home?.id ? 'font-bold' : ''">
                                        <span class="truncate text-sm">{{ match.home?.name || '??' }}</span>
                                        <span v-if="match.score_home !== null" class="text-sm">{{ match.score_home }}</span>
                                        <span v-else-if="match.status === 'completed'" class="text-xs text-muted-foreground">-</span>
                                    </div>
                                    <div class="border-t my-1" />
                                    <div class="flex items-center justify-between gap-2 py-0.5" :class="match.winner_id === match.away?.id ? 'font-bold' : ''">
                                        <span class="truncate text-sm">{{ match.away?.name || '??' }}</span>
                                        <span v-if="match.score_away !== null" class="text-sm">{{ match.score_away }}</span>
                                        <span v-else-if="match.status === 'completed'" class="text-xs text-muted-foreground">-</span>
                                    </div>
                                    <div v-if="match.status !== 'completed' && match.home && match.away" class="mt-1 text-xs text-muted-foreground">Belum dimainkan</div>
                                    <div v-if="match.home === null || match.away === null" class="mt-1 text-xs text-muted-foreground italic">Menunggu pemenang</div>
                                    <div v-if="match.win_method" class="mt-1 text-xs text-muted-foreground">{{ match.win_method }}</div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else>
                <!-- Competition format: tabs -->
                <div v-if="standings.length > 0" class="mb-6 flex gap-2">
                    <button
                        class="rounded-md px-4 py-2 text-sm font-medium transition-colors"
                        :class="activeTab === 'standings' ? 'bg-accent' : 'hover:bg-accent/50'"
                        @click="activeTab = 'standings'"
                    >
                        Klasemen
                    </button>
                    <button
                        class="rounded-md px-4 py-2 text-sm font-medium transition-colors"
                        :class="activeTab === 'matches' ? 'bg-accent' : 'hover:bg-accent/50'"
                        @click="activeTab = 'matches'"
                    >
                        Pertandingan
                    </button>
                </div>

                <div v-if="activeTab === 'standings' && standings.length > 0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-10">#</TableHead>
                                    <TableHead>Tim</TableHead>
                                    <TableHead class="text-center">M</TableHead>
                                    <TableHead class="text-center">M</TableHead>
                                    <TableHead class="text-center">S</TableHead>
                                    <TableHead class="text-center">K</TableHead>
                                    <TableHead class="text-center hidden sm:table-cell">SG+</TableHead>
                                    <TableHead class="text-center hidden sm:table-cell">SG-</TableHead>
                                    <TableHead class="text-center">S</TableHead>
                                    <TableHead class="text-center font-bold">Poin</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="entry in standings" :key="entry.participant_id">
                                    <TableCell class="font-medium">{{ entry.rank }}</TableCell>
                                    <TableCell>{{ entry.participant_name }}</TableCell>
                                    <TableCell class="text-center">{{ entry.played }}</TableCell>
                                    <TableCell class="text-center">{{ entry.won }}</TableCell>
                                    <TableCell class="text-center">{{ entry.drawn }}</TableCell>
                                    <TableCell class="text-center">{{ entry.lost }}</TableCell>
                                    <TableCell class="text-center hidden sm:table-cell">{{ entry.score_for }}</TableCell>
                                    <TableCell class="text-center hidden sm:table-cell">{{ entry.score_against }}</TableCell>
                                    <TableCell class="text-center" :class="entry.difference >= 0 ? 'text-green-600' : 'text-red-600'">{{ entry.difference >= 0 ? '+' : '' }}{{ entry.difference }}</TableCell>
                                    <TableCell class="text-center font-bold">{{ entry.points }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <div v-if="activeTab === 'matches'">
                    <div v-for="round in sortedRounds" :key="round" class="mb-6">
                        <h3 class="mb-2 text-sm font-semibold text-muted-foreground">{{ roundLabel(round, matchesByRound[round][0]?.leg ?? 1) }}</h3>
                        <div class="space-y-2">
                            <div v-for="match in matchesByRound[round]" :key="match.id" class="flex items-center gap-4 rounded-lg border px-4 py-3">
                                <template v-if="match.status === 'bye'">
                                    <span class="text-sm text-muted-foreground italic">Bye</span>
                                    <span class="font-medium">{{ match.home?.name || '??' }}</span>
                                </template>
                                <template v-else>
                                    <span v-if="match.home" class="w-36 text-right text-sm font-medium truncate" :class="match.winner_id === match.home.id ? 'font-bold' : ''">{{ match.home.name }}</span>
                                    <span v-else class="w-36 text-right text-sm text-muted-foreground italic truncate">Menunggu</span>

                                    <span v-if="match.score_home !== null" class="w-8 text-center text-sm font-bold">{{ match.score_home }}</span>
                                    <span v-else class="w-8 text-center text-muted-foreground">-</span>

                                    <span class="text-muted-foreground">:</span>

                                    <span v-if="match.score_away !== null" class="w-8 text-center text-sm font-bold">{{ match.score_away }}</span>
                                    <span v-else class="w-8 text-center text-muted-foreground">-</span>

                                    <span v-if="match.away" class="w-36 text-left text-sm font-medium truncate" :class="match.winner_id === match.away.id ? 'font-bold' : ''">{{ match.away.name }}</span>
                                    <span v-else class="w-36 text-left text-sm text-muted-foreground italic truncate">Menunggu</span>

                                    <span v-if="match.status !== 'completed' && match.home && match.away" class="ml-auto text-xs text-muted-foreground">Belum dimainkan</span>
                                    <span v-if="match.win_method" class="ml-auto text-xs text-muted-foreground">{{ match.win_method }}</span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="sortedRounds.length === 0" class="py-16 text-center">
                <p class="text-muted-foreground">Data pertandingan belum tersedia.</p>
            </div>
        </main>
    </div>
</template>
