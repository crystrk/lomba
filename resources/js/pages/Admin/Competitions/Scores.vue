<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { update as updateScore } from '@/routes/admin/matches/score';

defineOptions({
    layout: AppLayout,
});

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
        draw_version: number;
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
        winner_id: number | null;
        status: string;
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
}>();

const statusLabel: Record<string, string> = {
    draft: 'Draft',
    drawn: 'Diundi',
    locked: 'Terkunci',
    in_progress: 'Berlangsung',
    completed: 'Selesai',
};

const statusVariant: Record<string, 'default' | 'outline' | 'destructive' | 'secondary'> = {
    draft: 'secondary',
    drawn: 'default',
    locked: 'default',
    in_progress: 'default',
    completed: 'default',
};

const isKnockout = computed(() => props.competition.format === 'knockout');

const sortedRounds = computed(() => {
    return Object.keys(props.matchesByRound)
        .map(Number)
        .sort((a, b) => a - b);
});

const matchForms = computed(() => {
    const forms: Record<number, ReturnType<typeof useForm>> = {};
    for (const round of sortedRounds.value) {
        for (const match of props.matchesByRound[round]) {
            if (match.status === 'bye') continue;
            forms[match.id] = useForm({
                score_home: match.score_home ?? '',
                score_away: match.score_away ?? '',
                winner_id: match.winner_id ?? '',
                win_method: '',
                result_version: match.result_version,
            });
        }
    }
    return forms;
});

function submitScore(matchId: number) {
    const form = matchForms.value[matchId];
    form.post(updateScore.url({ competition: props.competition.id, match: matchId }), {
        preserveScroll: true,
        onSuccess: () => {
            // form will be reset by the page re-render
        },
    });
}

function roundLabel(round: number, leg: number): string {
    if (isKnockout.value) {
        const labels: Record<number, string> = { 1: 'Final', 2: 'Semifinal', 3: 'Perempat Final' };
        const totalRounds = sortedRounds.value.length;
        const fromEnd = totalRounds - round + 1;
        return labels[fromEnd] || `Babak ${round}`;
    }
    if (leg > 1) return `Pekan ${round} - Leg ${leg}`;
    return `Pekan ${round}`;
}
</script>

<template>
    <Head title="Skor - {{ competition.name }}" />
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <div>
                <Link :href="`/admin/competitions/${competition.id}`" class="text-sm text-muted-foreground hover:underline">
                    {{ competition.name }}
                </Link>
                <h1 class="text-2xl font-bold">Skor</h1>
            </div>
            <Badge :variant="statusVariant[competition.status] || 'secondary'">
                {{ statusLabel[competition.status] || competition.status }}
            </Badge>
        </div>

        <div v-if="!isKnockout && standings.length > 0" class="space-y-4">
            <Card>
                <CardHeader>
                    <CardTitle>Klasemen</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-12">#</TableHead>
                                <TableHead>Tim</TableHead>
                                <TableHead class="text-center">M</TableHead>
                                <TableHead class="text-center">M</TableHead>
                                <TableHead class="text-center">S</TableHead>
                                <TableHead class="text-center">K</TableHead>
                                <TableHead class="text-center">SG+</TableHead>
                                <TableHead class="text-center">SG-</TableHead>
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
                                <TableCell class="text-center">{{ entry.score_for }}</TableCell>
                                <TableCell class="text-center">{{ entry.score_against }}</TableCell>
                                <TableCell class="text-center" :class="entry.difference >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ entry.difference >= 0 ? '+' : '' }}{{ entry.difference }}
                                </TableCell>
                                <TableCell class="text-center font-bold">{{ entry.points }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <div v-for="round in sortedRounds" :key="round" class="space-y-2">
            <h2 class="text-lg font-semibold">{{ roundLabel(round, matchesByRound[round][0]?.leg ?? 1) }}</h2>
            <div v-for="match in matchesByRound[round]" :key="match.id" class="rounded-lg border p-4">
                <div v-if="match.status === 'bye'" class="text-sm text-muted-foreground">
                    {{ match.home_participant?.name ?? '??' }} — Bye
                </div>
                <div v-else>
                    <form @submit.prevent="submitScore(match.id)" class="space-y-3">
                        <div class="flex items-center gap-4">
                            <span class="w-40 text-right font-medium">{{ match.home_participant?.name ?? '??' }}</span>
                            <input
                                type="number"
                                min="0"
                                class="w-16 rounded-md border px-3 py-1 text-center"
                                v-model="matchForms[match.id].score_home"
                                :disabled="matchForms[match.id].processing"
                            />
                            <span class="text-muted-foreground">:</span>
                            <input
                                type="number"
                                min="0"
                                class="w-16 rounded-md border px-3 py-1 text-center"
                                v-model="matchForms[match.id].score_away"
                                :disabled="matchForms[match.id].processing"
                            />
                            <span class="w-40 font-medium">{{ match.away_participant?.name ?? '??' }}</span>
                        </div>
                        <div v-if="matchForms[match.id].errors.score_home || matchForms[match.id].errors.score_away || matchForms[match.id].errors.match || matchForms[match.id].errors.result_version" class="text-sm text-red-600">
                            {{ matchForms[match.id].errors.score_home || matchForms[match.id].errors.score_away || matchForms[match.id].errors.match || matchForms[match.id].errors.result_version }}
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge variant="outline" class="text-xs">{{ match.status === 'completed' ? 'Selesai' : 'Siap' }}</Badge>
                            <span class="text-xs text-muted-foreground">v{{ match.result_version }}</span>
                            <Button type="submit" size="sm" :disabled="matchForms[match.id].processing" class="ml-auto">
                                {{ matchForms[match.id].processing ? 'Menyimpan...' : 'Simpan' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div v-if="sortedRounds.length === 0" class="py-12 text-center text-muted-foreground">
            Belum ada pertandingan. Lakukan undian terlebih dahulu.
        </div>
    </div>
</template>
