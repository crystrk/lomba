<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trophy } from '@lucide/vue';
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

defineProps<{
    standings: StandingEntry[];
}>();
</script>

<template>
    <div class="space-y-4">
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
</template>
