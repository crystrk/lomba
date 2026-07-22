<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Trophy,
    Plus,
    FileText,
    Lock,
    CheckCircle2,
    Play,
    Users,
    UserCheck,
    Swords,
    Shuffle,
    ArrowRight,
    ExternalLink,
    Clock,
    Sparkles,
    Activity,
    ChevronRight,
} from '@lucide/vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { index as competitionsIndex, create as createCompetition, show as showCompetition } from '@/routes/admin/competitions';
import { index as operatorsIndex } from '@/routes/admin/operators';

defineOptions({
    layout: AppLayout,
});

interface CompetitionItem {
    id: number;
    name: string;
    slug: string;
    format: string;
    status: string;
    participants_count: number;
    matches_count?: number;
    completed_matches_count?: number;
    created_at?: string;
    starts_at?: string | null;
}

const props = defineProps<{
    stats: {
        total: number;
        draft: number;
        drawn: number;
        locked: number;
        in_progress: number;
        completed: number;
        total_participants: number;
        total_operators: number;
        total_matches: number;
    };
    recent_competitions: CompetitionItem[];
    active_competitions: CompetitionItem[];
}>();

const page = usePage();
const userName = computed(() => {
    const user = page.props.auth?.user as { name?: string } | undefined;
    return user?.name || 'Admin';
});

// Dynamic Greeting based on time of day
const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour >= 5 && hour < 11) return 'Selamat Pagi';
    if (hour >= 11 && hour < 15) return 'Selamat Siang';
    if (hour >= 15 && hour < 18) return 'Selamat Sore';
    return 'Selamat Malam';
});

const labelFormat: Record<string, string> = {
    knockout: 'Knockout',
    full_competition: 'Kompetisi Penuh',
    half_competition: 'Setengah Kompetisi',
};

const labelStatus: Record<string, string> = {
    draft: 'Draft',
    drawn: 'Diundi',
    locked: 'Terkunci',
    in_progress: 'Berlangsung',
    completed: 'Selesai',
};

const statusBadgeClasses: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
    drawn: 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800',
    locked: 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800',
    in_progress: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
    completed: 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800',
};

// Percentage calculation for progress bar
const totalStats = computed(() => props.stats.total || 1);

const pctDraft = computed(() => Math.round((props.stats.draft / totalStats.value) * 100));
const pctDrawn = computed(() => Math.round((props.stats.drawn / totalStats.value) * 100));
const pctLocked = computed(() => Math.round((props.stats.locked / totalStats.value) * 100));
const pctInProgress = computed(() => Math.round((props.stats.in_progress / totalStats.value) * 100));
const pctCompleted = computed(() => Math.round((props.stats.completed / totalStats.value) * 100));

function calculateMatchPercentage(completed = 0, total = 0) {
    if (!total || total === 0) return 0;
    return Math.min(100, Math.round((completed / total) * 100));
}
</script>

<template>
    <Head title="Dashboard Admin" />

    <div class="flex flex-col gap-6 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
        <!-- Banner Header Welcome -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 p-6 sm:p-8 text-white shadow-xl">
            <!-- Background Decorative Elements -->
            <div class="absolute -right-10 -top-10 size-64 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>
            <div class="absolute right-1/4 -bottom-10 size-48 rounded-full bg-blue-500/10 blur-2xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-indigo-200 text-xs font-medium backdrop-blur-md border border-white/10">
                        <Sparkles class="size-3.5 text-amber-300" />
                        <span>Panel Kontrol Admin</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                        {{ greeting }}, <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-200 via-sky-200 to-white">{{ userName }}</span>!
                    </h1>
                    <p class="text-sm sm:text-base text-slate-300 max-w-2xl leading-relaxed">
                        Kelola kompetisi, pantau jalannya pertandingan secara real-time, dan atur penugasan operator dari satu tempat.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <Button as-child size="lg" class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold shadow-lg shadow-indigo-600/30 border-0 transition-all duration-200">
                        <Link :href="createCompetition().url">
                            <Plus class="mr-2 size-5" />
                            Tambah Lomba
                        </Link>
                    </Button>
                    <Button as-child variant="outline" size="lg" class="bg-white/10 hover:bg-white/20 text-white border-white/20 backdrop-blur-md transition-all duration-200">
                        <Link :href="operatorsIndex().url">
                            <Users class="mr-2 size-5" />
                            Kelola Operator
                        </Link>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Metric Summary Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Total Lomba -->
            <Card class="relative overflow-hidden border border-border/60 hover:border-indigo-500/40 transition-all duration-200 shadow-sm hover:shadow-md">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Lomba</CardTitle>
                    <div class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <Trophy class="size-5" />
                    </div>
                </CardHeader>
                <CardContent class="space-y-1">
                    <div class="text-3xl font-bold tracking-tight text-foreground">{{ stats.total }}</div>
                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                        <span class="font-semibold text-foreground">{{ stats.completed }}</span> selesai
                        <span class="text-slate-300 dark:text-slate-700">•</span>
                        <span class="font-semibold text-foreground">{{ stats.in_progress }}</span> aktif
                    </div>
                </CardContent>
            </Card>

            <!-- Card 2: Lomba Berlangsung & Terkunci -->
            <Card class="relative overflow-hidden border border-border/60 hover:border-emerald-500/40 transition-all duration-200 shadow-sm hover:shadow-md">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Sedang Aktif</CardTitle>
                    <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                        <Activity class="size-5" />
                    </div>
                </CardHeader>
                <CardContent class="space-y-1">
                    <div class="text-3xl font-bold tracking-tight text-foreground">
                        {{ stats.in_progress + stats.locked }}
                    </div>
                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                        <span class="inline-flex items-center gap-1 font-semibold text-emerald-600 dark:text-emerald-400">
                            <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            {{ stats.in_progress }} Berlangsung
                        </span>
                        <span class="text-slate-300 dark:text-slate-700">•</span>
                        <span>{{ stats.locked }} Terkunci</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Card 3: Total Peserta -->
            <Card class="relative overflow-hidden border border-border/60 hover:border-sky-500/40 transition-all duration-200 shadow-sm hover:shadow-md">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Peserta</CardTitle>
                    <div class="p-2.5 rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400">
                        <Users class="size-5" />
                    </div>
                </CardHeader>
                <CardContent class="space-y-1">
                    <div class="text-3xl font-bold tracking-tight text-foreground">{{ stats.total_participants }}</div>
                    <p class="text-xs text-muted-foreground">Total partisipan di semua lomba</p>
                </CardContent>
            </Card>

            <!-- Card 4: Total Operator -->
            <Card class="relative overflow-hidden border border-border/60 hover:border-amber-500/40 transition-all duration-200 shadow-sm hover:shadow-md">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Operator Field</CardTitle>
                    <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                        <UserCheck class="size-5" />
                    </div>
                </CardHeader>
                <CardContent class="space-y-1">
                    <div class="text-3xl font-bold tracking-tight text-foreground">{{ stats.total_operators }}</div>
                    <p class="text-xs text-muted-foreground">Petugas pengelola skor pertandingan</p>
                </CardContent>
            </Card>
        </div>

        <!-- Status Distribution Pipeline -->
        <Card class="border border-border/60 shadow-sm">
            <CardHeader class="pb-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <CardTitle class="text-base font-bold flex items-center gap-2">
                            <Layers class="size-4 text-indigo-500" />
                            Distribusi Status Lomba
                        </CardTitle>
                        <CardDescription>Visualisasi jumlah turnamen berdasarkan tahapan status</CardDescription>
                    </div>
                    <div class="text-xs font-semibold px-2.5 py-1 rounded-md bg-muted text-muted-foreground self-start sm:self-auto">
                        Total: {{ stats.total }} Lomba
                    </div>
                </div>
            </CardHeader>
            <CardContent class="space-y-4">
                <!-- Stacked Progress Bar -->
                <div class="h-3 w-full bg-secondary rounded-full overflow-hidden flex gap-0.5 p-0.5">
                    <div
                        v-if="stats.draft > 0"
                        :style="{ width: `${pctDraft}%` }"
                        class="h-full bg-slate-400 dark:bg-slate-600 rounded-sm transition-all duration-500"
                        :title="`Draft: ${stats.draft}`"
                    ></div>
                    <div
                        v-if="stats.drawn > 0"
                        :style="{ width: `${pctDrawn}%` }"
                        class="h-full bg-blue-500 rounded-sm transition-all duration-500"
                        :title="`Diundi: ${stats.drawn}`"
                    ></div>
                    <div
                        v-if="stats.locked > 0"
                        :style="{ width: `${pctLocked}%` }"
                        class="h-full bg-amber-500 rounded-sm transition-all duration-500"
                        :title="`Terkunci: ${stats.locked}`"
                    ></div>
                    <div
                        v-if="stats.in_progress > 0"
                        :style="{ width: `${pctInProgress}%` }"
                        class="h-full bg-emerald-500 rounded-sm transition-all duration-500"
                        :title="`Berlangsung: ${stats.in_progress}`"
                    ></div>
                    <div
                        v-if="stats.completed > 0"
                        :style="{ width: `${pctCompleted}%` }"
                        class="h-full bg-purple-500 rounded-sm transition-all duration-500"
                        :title="`Selesai: ${stats.completed}`"
                    ></div>
                </div>

                <!-- Status Cards Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 pt-1">
                    <!-- Draft -->
                    <div class="flex flex-col p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/40">
                        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-1">
                            <span class="text-xs font-medium">Draft</span>
                            <FileText class="size-4 text-slate-400" />
                        </div>
                        <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ stats.draft }}</div>
                        <div class="text-[11px] text-muted-foreground mt-1">{{ pctDraft }}% dari total</div>
                    </div>

                    <!-- Diundi -->
                    <div class="flex flex-col p-3 rounded-xl border border-blue-200 dark:border-blue-900/60 bg-blue-50/40 dark:bg-blue-950/20">
                        <div class="flex items-center justify-between text-blue-600 dark:text-blue-400 mb-1">
                            <span class="text-xs font-medium">Diundi</span>
                            <Shuffle class="size-4 text-blue-500" />
                        </div>
                        <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ stats.drawn }}</div>
                        <div class="text-[11px] text-muted-foreground mt-1">{{ pctDrawn }}% dari total</div>
                    </div>

                    <!-- Terkunci -->
                    <div class="flex flex-col p-3 rounded-xl border border-amber-200 dark:border-amber-900/60 bg-amber-50/40 dark:bg-amber-950/20">
                        <div class="flex items-center justify-between text-amber-600 dark:text-amber-400 mb-1">
                            <span class="text-xs font-medium">Terkunci</span>
                            <Lock class="size-4 text-amber-500" />
                        </div>
                        <div class="text-2xl font-bold text-amber-900 dark:text-amber-100">{{ stats.locked }}</div>
                        <div class="text-[11px] text-muted-foreground mt-1">{{ pctLocked }}% dari total</div>
                    </div>

                    <!-- Berlangsung -->
                    <div class="flex flex-col p-3 rounded-xl border border-emerald-200 dark:border-emerald-900/60 bg-emerald-50/40 dark:bg-emerald-950/20">
                        <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400 mb-1">
                            <span class="text-xs font-medium">Berlangsung</span>
                            <Play class="size-4 text-emerald-500" />
                        </div>
                        <div class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ stats.in_progress }}</div>
                        <div class="text-[11px] text-muted-foreground mt-1">{{ pctInProgress }}% dari total</div>
                    </div>

                    <!-- Selesai -->
                    <div class="flex flex-col p-3 rounded-xl border border-purple-200 dark:border-purple-900/60 bg-purple-50/40 dark:bg-purple-950/20 col-span-2 sm:col-span-1">
                        <div class="flex items-center justify-between text-purple-600 dark:text-purple-400 mb-1">
                            <span class="text-xs font-medium">Selesai</span>
                            <CheckCircle2 class="size-4 text-purple-500" />
                        </div>
                        <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ stats.completed }}</div>
                        <div class="text-[11px] text-muted-foreground mt-1">{{ pctCompleted }}% dari total</div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Main Content Area: Active Competitions & Recent List Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Side: Active Competitions Widget (7 Cols) -->
            <div class="lg:col-span-7 flex flex-col gap-4">
                <Card class="border border-border/60 shadow-sm flex-1 flex flex-col">
                    <CardHeader class="pb-3 border-b border-border/40">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="relative flex size-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full size-3 bg-emerald-500"></span>
                                </span>
                                <CardTitle class="text-base font-bold">Lomba Berlangsung & Terkunci</CardTitle>
                            </div>
                            <Badge variant="outline" class="text-xs">
                                {{ active_competitions ? active_competitions.length : 0 }} Aktif
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="p-4 flex-1 flex flex-col justify-between">
                        <div v-if="active_competitions && active_competitions.length > 0" class="divide-y divide-border/40 space-y-3">
                            <div
                                v-for="comp in active_competitions"
                                :key="comp.id"
                                class="pt-3 first:pt-0 group flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 rounded-xl hover:bg-muted/50 transition-colors"
                            >
                                <div class="space-y-1.5 flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <Link
                                            :href="showCompetition(comp.id).url"
                                            class="font-semibold text-foreground hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors truncate"
                                        >
                                            {{ comp.name }}
                                        </Link>
                                        <Badge
                                            :class="statusBadgeClasses[comp.status] || 'bg-secondary text-secondary-foreground'"
                                            class="text-[11px] font-medium border"
                                        >
                                            {{ labelStatus[comp.status] || comp.status }}
                                        </Badge>
                                    </div>

                                    <div class="flex items-center gap-3 text-xs text-muted-foreground flex-wrap">
                                        <span class="inline-flex items-center gap-1">
                                            <Swords class="size-3.5" />
                                            {{ labelFormat[comp.format] || comp.format }}
                                        </span>
                                        <span>•</span>
                                        <span class="inline-flex items-center gap-1">
                                            <Users class="size-3.5" />
                                            {{ comp.participants_count }} Peserta
                                        </span>
                                    </div>

                                    <!-- Match Progress Bar -->
                                    <div v-if="comp.matches_count && comp.matches_count > 0" class="space-y-1 pt-1 max-w-xs">
                                        <div class="flex justify-between text-[11px] text-muted-foreground font-medium">
                                            <span>Progress Pertandingan</span>
                                            <span>{{ comp.completed_matches_count || 0 }} / {{ comp.matches_count }} ({{ calculateMatchPercentage(comp.completed_matches_count, comp.matches_count) }}%)</span>
                                        </div>
                                        <div class="h-1.5 w-full bg-secondary rounded-full overflow-hidden">
                                            <div
                                                class="h-full bg-emerald-500 rounded-full transition-all duration-300"
                                                :style="{ width: `${calculateMatchPercentage(comp.completed_matches_count, comp.matches_count)}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                                    <Button as-child variant="outline" size="sm" class="group-hover:border-indigo-500/40">
                                        <Link :href="showCompetition(comp.id).url">
                                            Kelola
                                            <ChevronRight class="ml-1 size-3.5 text-muted-foreground group-hover:translate-x-0.5 transition-transform" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State for Active Competitions -->
                        <div v-else class="flex flex-col items-center justify-center py-8 text-center my-auto">
                            <div class="size-12 rounded-full bg-muted flex items-center justify-center mb-3">
                                <Play class="size-6 text-muted-foreground/60" />
                            </div>
                            <p class="text-sm font-medium text-foreground">Tidak Ada Lomba Aktif</p>
                            <p class="text-xs text-muted-foreground max-w-xs mt-1">Saat ini belum ada lomba dengan status Berlangsung atau Terkunci.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Right Side: Recent Competitions List (5 Cols) -->
            <div class="lg:col-span-5 flex flex-col gap-4">
                <Card class="border border-border/60 shadow-sm flex-1 flex flex-col">
                    <CardHeader class="pb-3 border-b border-border/40">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Clock class="size-4 text-indigo-500" />
                                <CardTitle class="text-base font-bold">Lomba Terbaru</CardTitle>
                            </div>
                            <Link :href="competitionsIndex().url" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-0.5">
                                Lihat Semua
                                <ArrowRight class="size-3" />
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent class="p-4 flex-1">
                        <div v-if="recent_competitions && recent_competitions.length > 0" class="space-y-3">
                            <div
                                v-for="c in recent_competitions"
                                :key="c.id"
                                class="flex items-center justify-between p-2.5 rounded-lg border border-transparent hover:border-border hover:bg-muted/40 transition-all"
                            >
                                <div class="space-y-0.5 min-w-0 pr-2">
                                    <Link :href="showCompetition(c.id).url" class="font-medium text-sm text-foreground hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors block truncate">
                                        {{ c.name }}
                                    </Link>
                                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                        <span>{{ c.participants_count }} Peserta</span>
                                        <span>•</span>
                                        <span>{{ c.created_at }}</span>
                                    </div>
                                </div>
                                <Badge :class="statusBadgeClasses[c.status] || 'bg-secondary'" class="text-[11px] shrink-0 border">
                                    {{ labelStatus[c.status] || c.status }}
                                </Badge>
                            </div>
                        </div>

                        <!-- Empty State for Recent Competitions -->
                        <div v-else class="flex flex-col items-center justify-center py-8 text-center my-auto">
                            <Trophy class="size-8 text-muted-foreground/40 mb-2" />
                            <p class="text-sm font-medium">Belum Ada Lomba</p>
                            <p class="text-xs text-muted-foreground mt-0.5">Mulai dengan membuat lomba pertama Anda.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Quick Access Shortcuts Grid -->
        <div class="space-y-3 pt-2">
            <h2 class="text-sm font-bold uppercase tracking-wider text-muted-foreground">Akses Cepat Admin</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Shortcut 1: Buat Lomba -->
                <Link
                    :href="createCompetition().url"
                    class="group relative p-4 rounded-xl border border-border/60 bg-card hover:border-indigo-500/50 hover:bg-indigo-500/[0.02] dark:hover:bg-indigo-500/[0.05] transition-all duration-200 shadow-sm hover:shadow-md flex flex-col justify-between gap-4"
                >
                    <div class="flex items-center justify-between">
                        <div class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
                            <Plus class="size-5" />
                        </div>
                        <ArrowRight class="size-4 text-muted-foreground/50 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all" />
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-foreground group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Buat Lomba Baru</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Formulir pendaftaran turnamen baru</p>
                    </div>
                </Link>

                <!-- Shortcut 2: Kelola Lomba -->
                <Link
                    :href="competitionsIndex().url"
                    class="group relative p-4 rounded-xl border border-border/60 bg-card hover:border-blue-500/50 hover:bg-blue-500/[0.02] dark:hover:bg-blue-500/[0.05] transition-all duration-200 shadow-sm hover:shadow-md flex flex-col justify-between gap-4"
                >
                    <div class="flex items-center justify-between">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                            <Trophy class="size-5" />
                        </div>
                        <ArrowRight class="size-4 text-muted-foreground/50 group-hover:text-blue-500 group-hover:translate-x-1 transition-all" />
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-foreground group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Daftar & Kelola Lomba</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Kelola peserta, undian, dan jadwal</p>
                    </div>
                </Link>

                <!-- Shortcut 3: Kelola Operator -->
                <Link
                    :href="operatorsIndex().url"
                    class="group relative p-4 rounded-xl border border-border/60 bg-card hover:border-amber-500/50 hover:bg-amber-500/[0.02] dark:hover:bg-amber-500/[0.05] transition-all duration-200 shadow-sm hover:shadow-md flex flex-col justify-between gap-4"
                >
                    <div class="flex items-center justify-between">
                        <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform">
                            <Users class="size-5" />
                        </div>
                        <ArrowRight class="size-4 text-muted-foreground/50 group-hover:text-amber-500 group-hover:translate-x-1 transition-all" />
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-foreground group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">Kelola Operator</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Atur akun & penugasan petugas skor</p>
                    </div>
                </Link>

                <!-- Shortcut 4: Halaman Publik -->
                <a
                    href="/"
                    target="_blank"
                    class="group relative p-4 rounded-xl border border-border/60 bg-card hover:border-purple-500/50 hover:bg-purple-500/[0.02] dark:hover:bg-purple-500/[0.05] transition-all duration-200 shadow-sm hover:shadow-md flex flex-col justify-between gap-4"
                >
                    <div class="flex items-center justify-between">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform">
                            <ExternalLink class="size-5" />
                        </div>
                        <ArrowRight class="size-4 text-muted-foreground/50 group-hover:text-purple-500 group-hover:translate-x-1 transition-all" />
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-foreground group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">Pratinjau Publik</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Lihat papan skor & klasemen pengunjung</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</template>
