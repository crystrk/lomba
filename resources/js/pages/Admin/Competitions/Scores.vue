<script setup lang="ts">
import { ref, computed, shallowRef, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Trophy, Medal, AlertCircle, Filter } from '@lucide/vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { update as updateScore } from '@/routes/admin/matches/score';
import { show } from '@/routes/admin/competitions';

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
        win_method: string | null;
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
    drawn: 'secondary',
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

const selectedRound = ref<string>('all');
const selectedStatus = ref<string>('all');

const filteredRounds = computed(() => {
    if (selectedRound.value === 'all') return sortedRounds.value;
    return sortedRounds.value.filter((r) => String(r) === selectedRound.value);
});

function isMatchVisible(match: typeof props.matchesByRound[number][0]): boolean {
    if (selectedStatus.value === 'all') return true;
    if (selectedStatus.value === 'completed') return match.status === 'completed';
    if (selectedStatus.value === 'pending') return match.status === 'ready' || match.status === 'pending';
    return true;
}

const matchForms = shallowRef<Record<number, ReturnType<typeof useForm>>>({});

watch(sortedRounds, () => {
    const forms = { ...matchForms.value };
    for (const round of sortedRounds.value) {
        for (const match of props.matchesByRound[round]) {
            if (match.status === 'bye') continue;
            if (!forms[match.id]) {
                forms[match.id] = useForm({
                    score_home: match.score_home ?? '',
                    score_away: match.score_away ?? '',
                    winner_id: match.winner_id ? String(match.winner_id) : '',
                    win_method: match.win_method ?? '',
                    result_version: match.result_version,
                });
            }
        }
    }
    matchForms.value = forms;
}, { immediate: true });

function submitScore(matchId: number) {
    const form = matchForms.value[matchId];
    form.post(updateScore.url({ competition: props.competition.id, match: matchId }), {
        preserveScroll: true,
    });
}

function isTieInKnockout(matchId: number): boolean {
    if (!isKnockout.value) return false;
    const form = matchForms.value[matchId];
    if (!form) return false;
    const home = form.score_home;
    const away = form.score_away;
    return home !== '' && away !== '' && Number(home) === Number(away);
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
    <Head :title="`Skor - ${competition.name}`" />
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <div>
            <Link :href="show(competition.id)" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:underline">
                <ArrowLeft class="size-4" />
                <span>{{ competition.name }}</span>
            </Link>
            <div class="flex items-center justify-between mt-1">
                <h1 class="text-2xl font-bold">Input & Koreksi Skor</h1>
                <Badge :variant="statusVariant[competition.status] || 'secondary'">
                    {{ statusLabel[competition.status] || competition.status }}
                </Badge>
            </div>
        </div>

        <div v-if="competition.status === 'draft' || competition.status === 'drawn'" class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-4 text-amber-800 dark:text-amber-300 flex items-start gap-3">
            <AlertCircle class="size-5 shrink-0 mt-0.5 text-amber-600 dark:text-amber-400" />
            <div>
                <h4 class="font-semibold text-sm">Input Skor Belum Diizinkan</h4>
                <p class="text-xs mt-0.5">Status lomba saat ini adalah <strong>{{ statusLabel[competition.status] || competition.status }}</strong>. Lomba harus dikunci (Locked) terlebih dahulu sebelum skor pertandingan dapat diinput.</p>
            </div>
        </div>

        <div v-if="!isKnockout && standings.length > 0" class="space-y-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-lg font-semibold flex items-center gap-2">
                        <Trophy class="size-5 text-amber-500" />
                        Klasemen Sementara
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
                                <TableHead class="text-center">GM</TableHead>
                                <TableHead class="text-center">GK</TableHead>
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
                                <TableCell class="text-center">{{ entry.score_for }}</TableCell>
                                <TableCell class="text-center">{{ entry.score_against }}</TableCell>
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

        <div v-if="sortedRounds.length > 0" class="flex flex-wrap items-center justify-between gap-4 rounded-lg border bg-muted/20 p-3">
            <div class="flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                <Filter class="size-4" />
                <span>Filter Pertandingan:</span>
            </div>
            <div class="flex items-center gap-3">
                <Select v-model="selectedRound">
                    <SelectTrigger class="w-[180px] bg-background">
                        <SelectValue placeholder="Pilih Babak/Pekan" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua Babak</SelectItem>
                        <SelectItem v-for="r in sortedRounds" :key="r" :value="String(r)">
                            {{ roundLabel(r, matchesByRound[r][0]?.leg ?? 1) }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="selectedStatus">
                    <SelectTrigger class="w-[160px] bg-background">
                        <SelectValue placeholder="Status Match" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua Status</SelectItem>
                        <SelectItem value="pending">Belum Dimainkan</SelectItem>
                        <SelectItem value="completed">Selesai</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div v-for="round in filteredRounds" :key="round" class="space-y-3">
            <h2 class="text-lg font-bold flex items-center gap-2">
                <Medal class="size-4 text-muted-foreground" />
                {{ roundLabel(round, matchesByRound[round][0]?.leg ?? 1) }}
            </h2>
            <template v-for="match in matchesByRound[round]" :key="match.id">
                <div v-if="isMatchVisible(match)">
                <Card>
                    <CardContent class="p-4">
                        <div v-if="match.status === 'bye'" class="text-sm italic text-muted-foreground">
                            {{ match.home_participant?.name ?? '??' }} — Lolos Otomatis (Bye)
                        </div>
                        <div v-else>
                            <form @submit.prevent="submitScore(match.id)" class="space-y-4">
                                <div class="flex items-center justify-center gap-4 sm:gap-6">
                                    <span class="flex-1 text-right font-semibold text-base sm:text-lg">
                                        {{ match.home_participant?.name ?? 'TBD' }}
                                    </span>

                                    <div class="flex items-center gap-2">
                                        <Input
                                            type="number"
                                            min="0"
                                            class="w-16 text-center text-lg font-bold"
                                            v-model="matchForms[match.id].score_home"
                                            :disabled="matchForms[match.id].processing"
                                        />
                                        <span class="font-bold text-muted-foreground">:</span>
                                        <Input
                                            type="number"
                                            min="0"
                                            class="w-16 text-center text-lg font-bold"
                                            v-model="matchForms[match.id].score_away"
                                            :disabled="matchForms[match.id].processing"
                                        />
                                    </div>

                                    <span class="flex-1 text-left font-semibold text-base sm:text-lg">
                                        {{ match.away_participant?.name ?? 'TBD' }}
                                    </span>
                                </div>

                                <div v-if="isTieInKnockout(match.id)" class="rounded-md border bg-amber-500/10 p-3 space-y-3">
                                    <div class="flex items-center gap-2 text-xs font-semibold text-amber-700 dark:text-amber-400">
                                        <AlertCircle class="size-4 shrink-0" />
                                        <span>Skor imbang pada sistem gugur. Pilih pemenang adu penalti / tie-break:</span>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div class="space-y-1">
                                            <span class="text-xs text-muted-foreground">Pemenang Tie-break</span>
                                            <Select v-model="matchForms[match.id].winner_id">
                                                <SelectTrigger class="w-full bg-background">
                                                    <SelectValue placeholder="Pilih Tim Pemenang" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-if="match.home_participant" :value="String(match.home_participant.id)">
                                                        {{ match.home_participant.name }}
                                                    </SelectItem>
                                                    <SelectItem v-if="match.away_participant" :value="String(match.away_participant.id)">
                                                        {{ match.away_participant.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div class="space-y-1">
                                            <span class="text-xs text-muted-foreground">Keterangan (opsional)</span>
                                            <Input
                                                v-model="matchForms[match.id].win_method"
                                                placeholder="Contoh: Penalti (5-4)"
                                                class="bg-background"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div v-if="Object.keys(matchForms[match.id].errors).length > 0" class="text-sm font-medium text-rose-600">
                                    {{ Object.values(matchForms[match.id].errors)[0] }}
                                </div>

                                <div class="flex items-center justify-between pt-1 border-t">
                                    <div class="flex items-center gap-2">
                                        <Badge :variant="match.status === 'completed' ? 'default' : 'outline'" class="text-xs">
                                            {{ match.status === 'completed' ? 'Selesai' : 'Belum Dimainkan' }}
                                        </Badge>
                                        <span class="text-xs font-mono text-muted-foreground">v{{ match.result_version }}</span>
                                    </div>

                                    <Button type="submit" size="sm" :disabled="matchForms[match.id].processing">
                                        <Save class="mr-1.5 size-3.5" />
                                        {{ matchForms[match.id].processing ? 'Menyimpan...' : 'Simpan Skor' }}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </CardContent>
                </Card>
                </div>
            </template>
        </div>

        <Card v-if="sortedRounds.length === 0" class="p-8 text-center text-muted-foreground">
            Belum ada pertandingan. Lakukan undian terlebih dahulu.
        </Card>
    </div>
</template>

