<script setup lang="ts">
import { computed } from 'vue';
import { Trophy, Medal, Clock, ArrowRight, ShieldAlert, CheckCircle2, Sparkles } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { getInitials } from '@/composables/useInitials';

interface StandingEntry {
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
}

interface MatchItem {
    id: number;
    round: number;
    leg: number;
    sequence: number;
    home: { id: number; name: string } | null;
    away: { id: number; name: string } | null;
    score_home: number | null;
    score_away: number | null;
    scheduled_time?: string | null;
    winner_id: number | null;
    status: string;
    win_method?: string | null;
    match_type?: string;
}

const props = defineProps<{
    finalMatch?: MatchItem | null;
    thirdPlaceMatch?: MatchItem | null;
    standingsA?: StandingEntry[] | null;
    standingsB?: StandingEntry[] | null;
}>();

const juaraA = computed(() => {
    if (props.finalMatch?.home) return props.finalMatch.home.name;
    if (props.standingsA && props.standingsA[0]) return props.standingsA[0].participant_name;
    return null;
});

const juaraB = computed(() => {
    if (props.finalMatch?.away) return props.finalMatch.away.name;
    if (props.standingsB && props.standingsB[0]) return props.standingsB[0].participant_name;
    return null;
});

const runnerUpA = computed(() => {
    if (props.thirdPlaceMatch?.home) return props.thirdPlaceMatch.home.name;
    if (props.standingsA && props.standingsA[1]) return props.standingsA[1].participant_name;
    return null;
});

const runnerUpB = computed(() => {
    if (props.thirdPlaceMatch?.away) return props.thirdPlaceMatch.away.name;
    if (props.standingsB && props.standingsB[1]) return props.standingsB[1].participant_name;
    return null;
});
</script>

<template>
    <div class="space-y-6">
        <!-- Banner Penjelasan Format & Bagan Final -->
        <Card class="rounded-2xl border-2 border-primary/30 bg-gradient-to-r from-primary/10 via-amber-500/5 to-purple-500/10 shadow-xs overflow-hidden">
            <CardHeader class="pb-3 border-b bg-card/60">
                <CardTitle class="text-base font-extrabold flex items-center justify-between">
                    <span class="flex items-center gap-2 text-foreground">
                        <Trophy class="size-5 text-amber-500 shrink-0" />
                        Bagan & Stage Final Placement (Group Final Four)
                    </span>
                    <Badge class="bg-amber-500 text-white font-bold text-xs">
                        Stage 2: Playoff Final
                    </Badge>
                </CardTitle>
            </CardHeader>
            <CardContent class="p-5 space-y-3">
                <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed">
                    Setelah penyisihan 2 grup (Grup A & Grup B) selesai, 4 tim terbaik otomatis melaju ke <strong>Babak Playoff Final Placement</strong> untuk memperebutkan peringkat 1 hingga 4:
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="flex items-center gap-2.5 p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-950 dark:text-amber-100">
                        <Trophy class="size-5 text-amber-500 shrink-0" />
                        <div>
                            <div class="font-extrabold text-amber-700 dark:text-amber-300">Final 1 vs 2 (Grand Final)</div>
                            <div class="text-[11px] text-muted-foreground">Juara Grup A vs Juara Grup B (Perebutan Juara 1 & 2)</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 p-3 rounded-xl bg-orange-500/10 border border-orange-500/30 text-orange-950 dark:text-orange-100">
                        <Medal class="size-5 text-orange-500 shrink-0" />
                        <div>
                            <div class="font-extrabold text-orange-700 dark:text-orange-300">Final 3 vs 4 (Perebutan Juara 3)</div>
                            <div class="text-[11px] text-muted-foreground">Runner-up Grup A vs Runner-up Grup B (Perebutan Juara 3 & 4)</div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- VISUAL BAGAN UTUH (POKOK BRACKET DIAGRAM) -->
        <Card class="rounded-2xl border bg-card p-4 sm:p-6 shadow-xs space-y-4 overflow-hidden">
            <div class="flex items-center justify-between border-b pb-3">
                <h3 class="text-sm font-extrabold flex items-center gap-2 text-foreground">
                    <Sparkles class="size-4 text-amber-500" />
                    Diagram Bagan Playoff Final Placement
                </h3>
                <span class="text-xs text-muted-foreground italic">Visual Alur Kualifikasi Grup ke Final</span>
            </div>

            <div class="overflow-x-auto pb-4 pt-2">
                <div class="min-w-[650px] flex items-center justify-between gap-6">
                    <!-- KOLOM 1: KUALIFIKASI PENYISIHAN GRUP -->
                    <div class="w-72 space-y-6 shrink-0">
                        <div class="text-xs font-bold text-muted-foreground uppercase tracking-wider text-center bg-muted/60 py-1 rounded-lg border">
                            Stage 1: Penyisihan 2 Grup
                        </div>

                        <!-- KOTAK KUALIFIKASI GRUP A -->
                        <div class="rounded-xl border-2 border-blue-500/40 bg-blue-500/5 p-3 space-y-2">
                            <div class="flex items-center justify-between border-b border-blue-500/30 pb-1.5">
                                <span class="font-extrabold text-xs text-blue-700 dark:text-blue-300 flex items-center gap-1.5">
                                    <span class="size-2 rounded-full bg-blue-500" />
                                    Klasemen Grup A
                                </span>
                                <Badge variant="outline" class="text-[10px] border-blue-300">Grup A</Badge>
                            </div>
                            <!-- Peringkat 1 Grup A -->
                            <div class="flex items-center justify-between p-1.5 rounded-lg bg-amber-500/10 border border-amber-500/30 text-xs">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="font-extrabold text-amber-600 text-[11px] shrink-0">🥇 1A</span>
                                    <span class="font-bold truncate text-foreground">{{ juaraA || 'Juara Grup A' }}</span>
                                </div>
                                <Badge class="bg-amber-500 text-white text-[9px] px-1 py-0 shrink-0">Ke Final 1v2</Badge>
                            </div>
                            <!-- Peringkat 2 Grup A -->
                            <div class="flex items-center justify-between p-1.5 rounded-lg bg-slate-500/10 border border-slate-500/30 text-xs">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="font-extrabold text-slate-500 text-[11px] shrink-0">🥈 2A</span>
                                    <span class="font-bold truncate text-foreground">{{ runnerUpA || 'Runner-up Grup A' }}</span>
                                </div>
                                <Badge variant="outline" class="border-orange-500/50 text-orange-700 dark:text-orange-300 text-[9px] px-1 py-0 shrink-0">Ke Final 3v4</Badge>
                            </div>
                        </div>

                        <!-- KOTAK KUALIFIKASI GRUP B -->
                        <div class="rounded-xl border-2 border-purple-500/40 bg-purple-500/5 p-3 space-y-2">
                            <div class="flex items-center justify-between border-b border-purple-500/30 pb-1.5">
                                <span class="font-extrabold text-xs text-purple-700 dark:text-purple-300 flex items-center gap-1.5">
                                    <span class="size-2 rounded-full bg-purple-500" />
                                    Klasemen Grup B
                                </span>
                                <Badge variant="outline" class="text-[10px] border-purple-300">Grup B</Badge>
                            </div>
                            <!-- Peringkat 1 Grup B -->
                            <div class="flex items-center justify-between p-1.5 rounded-lg bg-amber-500/10 border border-amber-500/30 text-xs">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="font-extrabold text-amber-600 text-[11px] shrink-0">🥇 1B</span>
                                    <span class="font-bold truncate text-foreground">{{ juaraB || 'Juara Grup B' }}</span>
                                </div>
                                <Badge class="bg-amber-500 text-white text-[9px] px-1 py-0 shrink-0">Ke Final 1v2</Badge>
                            </div>
                            <!-- Peringkat 2 Grup B -->
                            <div class="flex items-center justify-between p-1.5 rounded-lg bg-slate-500/10 border border-slate-500/30 text-xs">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="font-extrabold text-slate-500 text-[11px] shrink-0">🥈 2B</span>
                                    <span class="font-bold truncate text-foreground">{{ runnerUpB || 'Runner-up Grup B' }}</span>
                                </div>
                                <Badge variant="outline" class="border-orange-500/50 text-orange-700 dark:text-orange-300 text-[9px] px-1 py-0 shrink-0">Ke Final 3v4</Badge>
                            </div>
                        </div>
                    </div>

                    <!-- ARROW CONNECTORS CONNECTOR -->
                    <div class="flex flex-col justify-around h-full py-12 shrink-0 text-muted-foreground">
                        <ArrowRight class="size-6 text-amber-500 animate-pulse" />
                        <ArrowRight class="size-6 text-orange-500 animate-pulse" />
                    </div>

                    <!-- KOLOM 2: MATCH PLAYOFF FINAL PLACEMENT -->
                    <div class="flex-1 space-y-6 min-w-[320px]">
                        <div class="text-xs font-bold text-muted-foreground uppercase tracking-wider text-center bg-muted/60 py-1 rounded-lg border">
                            Stage 2: Match Final Placement
                        </div>

                        <!-- MATCH KARTU GRAND FINAL 1v2 -->
                        <div class="rounded-xl border-2 border-amber-500 bg-background p-4 shadow-2xs space-y-3">
                            <div class="flex items-center justify-between border-b border-amber-500/30 pb-2">
                                <div class="flex items-center gap-1.5">
                                    <Trophy class="size-4 text-amber-500" />
                                    <span class="font-extrabold text-xs text-amber-700 dark:text-amber-300">GRAND FINAL (1 vs 2)</span>
                                </div>
                                <Badge class="bg-amber-500 text-white text-[10px] font-bold">Cari Juara 1 & 2</Badge>
                            </div>

                            <div class="space-y-2 text-xs">
                                <!-- Home: 1A -->
                                <div class="flex items-center justify-between p-2 rounded-lg" :class="finalMatch?.winner_id && finalMatch.home?.id === finalMatch.winner_id ? 'bg-emerald-500/15 font-extrabold text-emerald-600' : 'bg-muted/50 font-bold'">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <Badge variant="outline" class="border-amber-500 text-amber-600 text-[9px] px-1 shrink-0">1A</Badge>
                                        <span class="truncate">{{ finalMatch?.home?.name || (juaraA ? juaraA + ' (Proyeksi 1A)' : 'Juara Grup A') }}</span>
                                    </div>
                                    <span class="font-mono text-sm font-extrabold">{{ finalMatch?.score_home !== null ? finalMatch.score_home : '-' }}</span>
                                </div>

                                <!-- Away: 1B -->
                                <div class="flex items-center justify-between p-2 rounded-lg" :class="finalMatch?.winner_id && finalMatch.away?.id === finalMatch.winner_id ? 'bg-emerald-500/15 font-extrabold text-emerald-600' : 'bg-muted/50 font-bold'">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <Badge variant="outline" class="border-purple-500 text-purple-600 text-[9px] px-1 shrink-0">1B</Badge>
                                        <span class="truncate">{{ finalMatch?.away?.name || (juaraB ? juaraB + ' (Proyeksi 1B)' : 'Juara Grup B') }}</span>
                                    </div>
                                    <span class="font-mono text-sm font-extrabold">{{ finalMatch?.score_away !== null ? finalMatch.score_away : '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- MATCH KARTU FINAL 3v4 -->
                        <div class="rounded-xl border-2 border-orange-500 bg-background p-4 shadow-2xs space-y-3">
                            <div class="flex items-center justify-between border-b border-orange-500/30 pb-2">
                                <div class="flex items-center gap-1.5">
                                    <Medal class="size-4 text-orange-500" />
                                    <span class="font-extrabold text-xs text-orange-700 dark:text-orange-300">FINAL 3 vs 4 (JUARA 3)</span>
                                </div>
                                <Badge variant="outline" class="border-orange-500/60 text-orange-700 dark:text-orange-300 text-[10px] font-bold">Cari Juara 3 & 4</Badge>
                            </div>

                            <div class="space-y-2 text-xs">
                                <!-- Home: 2A -->
                                <div class="flex items-center justify-between p-2 rounded-lg" :class="thirdPlaceMatch?.winner_id && thirdPlaceMatch.home?.id === thirdPlaceMatch.winner_id ? 'bg-emerald-500/15 font-extrabold text-emerald-600' : 'bg-muted/50 font-bold'">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <Badge variant="outline" class="border-blue-500 text-blue-600 text-[9px] px-1 shrink-0">2A</Badge>
                                        <span class="truncate">{{ thirdPlaceMatch?.home?.name || (runnerUpA ? runnerUpA + ' (Proyeksi 2A)' : 'Runner-up Grup A') }}</span>
                                    </div>
                                    <span class="font-mono text-sm font-extrabold">{{ thirdPlaceMatch?.score_home !== null ? thirdPlaceMatch.score_home : '-' }}</span>
                                </div>

                                <!-- Away: 2B -->
                                <div class="flex items-center justify-between p-2 rounded-lg" :class="thirdPlaceMatch?.winner_id && thirdPlaceMatch.away?.id === thirdPlaceMatch.winner_id ? 'bg-emerald-500/15 font-extrabold text-emerald-600' : 'bg-muted/50 font-bold'">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <Badge variant="outline" class="border-purple-500 text-purple-600 text-[9px] px-1 shrink-0">2B</Badge>
                                        <span class="truncate">{{ thirdPlaceMatch?.away?.name || (runnerUpB ? runnerUpB + ' (Proyeksi 2B)' : 'Runner-up Grup B') }}</span>
                                    </div>
                                    <span class="font-mono text-sm font-extrabold">{{ thirdPlaceMatch?.score_away !== null ? thirdPlaceMatch.score_away : '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Card>
    </div>
</template>
