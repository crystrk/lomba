<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Trophy, Calendar, Users, ArrowRight, Activity, CheckCircle2, ShieldAlert } from '@lucide/vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import CompetitionSportIcon from '@/components/competitions/CompetitionSportIcon.vue';
import { scores } from '@/routes/operator/competitions';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    competitions: Array<{
        id: number;
        name: string;
        slug: string;
        sport: string | null;
        format: string;
        status: string;
        participants_count: number;
        matches_count: number;
        completed_matches_count: number;
    }>;
}>();

const statusLabel: Record<string, string> = {
    draft: 'Draft',
    drawn: 'Diundi',
    locked: 'Terkunci',
    in_progress: 'Sedang Berlangsung',
    completed: 'Selesai',
};

const statusBadgeClass: Record<string, string> = {
    draft: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-300 dark:border-slate-700',
    drawn: 'bg-blue-500/10 text-blue-700 dark:text-blue-300 border-blue-300 dark:border-blue-800',
    locked: 'bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-300 dark:border-purple-800',
    in_progress: 'bg-emerald-500 text-white font-bold animate-pulse',
    completed: 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-300 dark:border-amber-800',
};

const formatLabel: Record<string, string> = {
    knockout: 'Knockout (Gugur)',
    final_four: 'Final Four',
    group_final_four: 'Group Final Four',
    group_four_final: 'Group Final Four',
    full_competition: 'Liga Penuh',
    half_competition: 'Setengah Liga',
};

// Overview Statistics
const activeCompetitionsCount = computed(() =>
    props.competitions.filter(c => c.status === 'in_progress' || c.status === 'locked').length
);

const totalCompletedMatches = computed(() =>
    props.competitions.reduce((acc, c) => acc + (c.completed_matches_count || 0), 0)
);
</script>

<template>
    <Head title="Dashboard Operator" />

    <div class="flex flex-col gap-6 p-4 sm:p-6 max-w-7xl mx-auto w-full">
        <!-- Hero Header & Ringkasan Tugas Operator -->
        <Card class="relative overflow-hidden rounded-2xl border-2 border-primary/20 bg-gradient-to-br from-primary/10 via-background to-amber-500/5 p-4 sm:p-6 shadow-xs">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
                <div class="space-y-1">
                    <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-primary/10 text-primary text-xs font-extrabold border border-primary/20 mb-1">
                        <Activity class="size-3.5 animate-pulse" />
                        <span>Portal Operator Skor</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-foreground">
                        Selamat Datang, Operator! 👋
                    </h1>
                    <p class="text-xs sm:text-sm text-muted-foreground">
                        Kelola skor dan jadwal pertandingan pada lomba-lomba yang ditugaskan kepada Anda.
                    </p>
                </div>

                <!-- Stats Badges Bar (Mobile Compact Grid) -->
                <div class="grid grid-cols-3 gap-2 sm:gap-3 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-border/60">
                    <div class="flex flex-col items-center justify-center p-2.5 rounded-xl bg-card border shadow-2xs text-center">
                        <span class="text-xs text-muted-foreground font-medium">Total Lomba</span>
                        <span class="text-base sm:text-lg font-extrabold text-foreground">{{ competitions.length }}</span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-center">
                        <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Aktif</span>
                        <span class="text-base sm:text-lg font-extrabold text-emerald-600 dark:text-emerald-400">{{ activeCompetitionsCount }}</span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-center">
                        <span class="text-xs text-amber-600 dark:text-amber-400 font-medium">Match Selesai</span>
                        <span class="text-base sm:text-lg font-extrabold text-amber-600 dark:text-amber-400">{{ totalCompletedMatches }}</span>
                    </div>
                </div>
            </div>
        </Card>

        <!-- EMPTY STATE -->
        <Card v-if="competitions.length === 0" class="p-8 sm:p-12 text-center text-muted-foreground rounded-2xl border-dashed">
            <Trophy class="size-12 sm:size-16 mx-auto mb-3 text-muted-foreground/30" />
            <h3 class="text-base sm:text-lg font-bold text-foreground">Belum Ada Penugasan Lomba</h3>
            <p class="text-xs sm:text-sm mt-1 max-w-md mx-auto">
                Anda belum di-assign ke lomba manapun saat ini. Silakan hubungi Administrator untuk menugaskan akun Anda ke lomba.
            </p>
        </Card>

        <!-- DAFTAR KARTU LOMBA (MOBILE COMPACT & RESPONSIVE GRID) -->
        <div v-else class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-extrabold text-foreground uppercase tracking-wider flex items-center gap-2">
                    <Trophy class="size-4 text-amber-500" />
                    Daftar Lomba Ditugaskan ({{ competitions.length }})
                </h2>
                <span class="text-xs text-muted-foreground hidden sm:inline-block">Pilih lomba untuk menginput skor</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link
                    v-for="comp in competitions"
                    :key="comp.id"
                    :href="scores(comp.id)"
                    class="group block"
                >
                    <Card class="relative h-full overflow-hidden rounded-2xl border-2 transition-all duration-200 group-hover:border-primary/60 group-hover:shadow-md bg-card">
                        <!-- Watermark Background SVG Icon Olahraga -->
                        <div class="pointer-events-none absolute -bottom-4 -right-4 z-0 text-muted-foreground/15 dark:text-muted-foreground/10 group-hover:scale-110 transition-transform duration-300">
                            <CompetitionSportIcon :sport="comp.sport" class="size-32" />
                        </div>

                        <CardContent class="p-4 sm:p-5 relative z-10 flex flex-col justify-between h-full space-y-4">
                            <!-- Header Card: Icon, Status, Title -->
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between gap-2">
                                    <!-- Circular Icon Box Category -->
                                    <div class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary border border-primary/20 shrink-0 group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
                                        <CompetitionSportIcon :sport="comp.sport" class="size-5" />
                                    </div>

                                    <!-- Badges: Status & Format -->
                                    <div class="flex flex-wrap items-center justify-end gap-1.5 shrink-0">
                                        <Badge
                                            variant="outline"
                                            class="text-[10px] sm:text-xs font-semibold px-2 py-0.5 rounded-lg border"
                                            :class="statusBadgeClass[comp.status] || 'bg-muted text-muted-foreground'"
                                        >
                                            {{ statusLabel[comp.status] || comp.status }}
                                        </Badge>
                                    </div>
                                </div>

                                <!-- Nama Lomba & Format -->
                                <div>
                                    <h3 class="text-base sm:text-lg font-extrabold text-foreground group-hover:text-primary transition-colors line-clamp-2">
                                        {{ comp.name }}
                                    </h3>
                                    <span class="text-xs text-muted-foreground font-medium flex items-center gap-1.5 mt-0.5">
                                        <Badge variant="secondary" class="text-[10px] px-1.5 py-0 font-bold border">
                                            {{ formatLabel[comp.format] || comp.format }}
                                        </Badge>
                                    </span>
                                </div>
                            </div>

                            <!-- Metrics & Progress Section (Mobile Friendly Compact Grid) -->
                            <div class="space-y-3 pt-2 border-t border-border/60">
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div class="flex items-center gap-2 p-2 rounded-lg bg-muted/50 border border-border/40">
                                        <Users class="size-4 text-indigo-500 shrink-0" />
                                        <div class="min-w-0">
                                            <span class="text-[10px] text-muted-foreground block leading-none">Peserta</span>
                                            <span class="font-bold text-foreground text-xs">{{ comp.participants_count }} Peserta</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 p-2 rounded-lg bg-muted/50 border border-border/40">
                                        <Calendar class="size-4 text-emerald-500 shrink-0" />
                                        <div class="min-w-0">
                                            <span class="text-[10px] text-muted-foreground block leading-none">Match Selesai</span>
                                            <span class="font-bold text-foreground text-xs">{{ comp.completed_matches_count || 0 }} / {{ comp.matches_count }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Progress Bar Pertandingan -->
                                <div v-if="comp.matches_count > 0" class="space-y-1">
                                    <div class="flex items-center justify-between text-[11px] font-semibold text-muted-foreground">
                                        <span>Progres Match</span>
                                        <span class="font-bold text-foreground">
                                            {{ Math.round(((comp.completed_matches_count || 0) / comp.matches_count) * 100) }}%
                                        </span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-muted overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-300"
                                            :class="comp.completed_matches_count === comp.matches_count ? 'bg-emerald-500' : 'bg-primary'"
                                            :style="{ width: `${Math.round(((comp.completed_matches_count || 0) / comp.matches_count) * 100)}%` }"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Action Button -->
                            <div class="flex items-center justify-between text-xs font-bold text-primary group-hover:underline pt-1">
                                <span>Input & Kelola Skor</span>
                                <ArrowRight class="size-4 transition-transform group-hover:translate-x-1" />
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>
    </div>
</template>
