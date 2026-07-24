<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    Trophy,
    Calendar,
    Users,
    Search,
    Filter,
    Sparkles,
    CheckCircle2,
    ChevronRight,
    Flame,
    Layers,
} from '@lucide/vue';
import { show } from '@/routes/public/competitions';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import CompetitionSportIcon from '@/components/competitions/CompetitionSportIcon.vue';
import {
    competitionSports,
    type CompetitionSport,
} from '@/components/competitions/competitionSport';

const props = defineProps<{
    competitions: Array<{
        id: number;
        name: string;
        slug: string;
        sport: string | null;
        format: string;
        status: string;
        participants_count: number;
        completed_matches: number;
        total_matches: number;
    }>;
}>();

const formatLabel: Record<string, string> = {
    knockout: 'Knockout (Gugur)',
    full_competition: 'Kompetisi Penuh (Liga)',
    half_competition: 'Setengah Kompetisi',
};

const statusLabel: Record<string, string> = {
    locked: 'Terkunci / Siap',
    in_progress: 'Sedang Berlangsung',
    completed: 'Selesai',
};

// Filter states
const searchQuery = ref('');
const statusFilter = ref<'all' | 'in_progress' | 'locked' | 'completed'>('all');
const formatFilter = ref<
    'all' | 'knockout' | 'full_competition' | 'half_competition'
>('all');

const filteredCompetitions = computed(() => {
    return props.competitions.filter((comp) => {
        const matchesSearch = comp.name
            .toLowerCase()
            .includes(searchQuery.value.toLowerCase());
        const matchesStatus =
            statusFilter.value === 'all' || comp.status === statusFilter.value;
        const matchesFormat =
            formatFilter.value === 'all' || comp.format === formatFilter.value;
        return matchesSearch && matchesStatus && matchesFormat;
    });
});

const inProgressCount = computed(
    () => props.competitions.filter((c) => c.status === 'in_progress').length,
);
const totalParticipants = computed(() =>
    props.competitions.reduce((acc, c) => acc + c.participants_count, 0),
);

function sportLabel(sport: string | null): string {
    return (
        competitionSports[sport as CompetitionSport]?.label ??
        'Cabang olahraga belum ditentukan'
    );
}
</script>

<template>
    <Head title="Beranda Lomba & Turnamen Live" />

    <PublicLayout>
        <!-- Hero Banner Section -->
        <section
            class="relative overflow-hidden border-b border-border/50 bg-linear-to-b from-amber-500/5 via-transparent to-transparent py-12 sm:py-16"
        >
            <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl space-y-4">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-600 backdrop-blur dark:text-amber-400"
                    >
                        <Flame class="size-3.5 fill-amber-500" />
                        <span
                            >Portal Informasi & Hasil Pertandingan
                            Real-Time</span
                        >
                    </div>

                    <h1
                        class="text-3xl leading-tight font-extrabold tracking-tight text-foreground sm:text-5xl"
                    >
                        Pantau Jalannya
                        <span
                            class="bg-linear-to-r from-amber-500 to-orange-500 bg-clip-text text-transparent"
                            >Turnamen & Lomba</span
                        >
                        Secara Real-Time
                    </h1>

                    <p
                        class="text-base leading-relaxed text-muted-foreground sm:text-lg"
                    >
                        Lihat bagan gugur (knockout), klasemen perolehan poin,
                        jadwal, dan skor akhir seluruh pertandingan dari satu
                        genggaman.
                    </p>

                    <!-- Quick Stats Badges -->
                    <div
                        class="flex flex-wrap items-center gap-4 pt-2 text-xs font-medium sm:text-sm"
                    >
                        <div
                            class="flex items-center gap-2 rounded-xl border border-border/80 bg-card px-3.5 py-2 shadow-xs"
                        >
                            <div
                                class="flex size-7 items-center justify-center rounded-lg bg-amber-500/10 text-amber-500"
                            >
                                <Trophy class="size-4" />
                            </div>
                            <div>
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >Total Lomba Aktif</span
                                >
                                <span class="text-sm font-bold text-foreground"
                                    >{{ competitions.length }} Lomba</span
                                >
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-2 rounded-xl border border-border/80 bg-card px-3.5 py-2 shadow-xs"
                        >
                            <div
                                class="flex size-7 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-500"
                            >
                                <Flame class="size-4" />
                            </div>
                            <div>
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >Sedang Berlangsung</span
                                >
                                <span
                                    class="text-sm font-bold text-emerald-600 dark:text-emerald-400"
                                    >{{ inProgressCount }} Lomba</span
                                >
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-2 rounded-xl border border-border/80 bg-card px-3.5 py-2 shadow-xs"
                        >
                            <div
                                class="flex size-7 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-500"
                            >
                                <Users class="size-4" />
                            </div>
                            <div>
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >Total Peserta</span
                                >
                                <span class="text-sm font-bold text-foreground"
                                    >{{ totalParticipants }} Peserta</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Search & Filter Controls + Competition List -->
        <section
            id="lomba-aktif"
            class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 sm:py-12 lg:px-8"
        >
            <div
                class="flex flex-col justify-between gap-4 border-b border-border/60 pb-4 sm:flex-row sm:items-center"
            >
                <div>
                    <h2
                        class="flex items-center gap-2 text-xl font-bold tracking-tight text-foreground sm:text-2xl"
                    >
                        <Trophy class="size-5 text-amber-500" />
                        Daftar Lomba Aktif
                    </h2>
                    <p class="text-xs text-muted-foreground sm:text-sm">
                        Pilih salah satu lomba di bawah untuk melihat detail
                        match & klasemen.
                    </p>
                </div>

                <!-- Search Input -->
                <div class="relative w-full sm:w-72">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama lomba..."
                        class="rounded-lg pl-9 text-sm"
                    />
                </div>
            </div>

            <!-- Format & Status Pill Filters -->
            <div class="flex flex-wrap items-center gap-2">
                <span
                    class="mr-1 flex items-center gap-1 text-xs font-semibold text-muted-foreground"
                >
                    <Filter class="size-3.5" />
                    Filter:
                </span>

                <button
                    @click="statusFilter = 'all'"
                    class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                    :class="
                        statusFilter === 'all'
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border bg-background text-muted-foreground hover:bg-accent'
                    "
                >
                    Semua Status
                </button>
                <button
                    @click="statusFilter = 'in_progress'"
                    class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                    :class="
                        statusFilter === 'in_progress'
                            ? 'border-emerald-600 bg-emerald-600 text-white'
                            : 'border-border bg-background text-muted-foreground hover:bg-accent'
                    "
                >
                    🟢 Sedang Berlangsung
                </button>
                <button
                    @click="statusFilter = 'locked'"
                    class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                    :class="
                        statusFilter === 'locked'
                            ? 'border-amber-600 bg-amber-600 text-white'
                            : 'border-border bg-background text-muted-foreground hover:bg-accent'
                    "
                >
                    🔒 Terkunci / Siap
                </button>
                <button
                    @click="statusFilter = 'completed'"
                    class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                    :class="
                        statusFilter === 'completed'
                            ? 'border-indigo-600 bg-indigo-600 text-white'
                            : 'border-border bg-background text-muted-foreground hover:bg-accent'
                    "
                >
                    🏆 Selesai
                </button>

                <div
                    class="mx-1 my-auto hidden h-4 w-px bg-border sm:block"
                ></div>

                <button
                    @click="formatFilter = 'all'"
                    class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                    :class="
                        formatFilter === 'all'
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border bg-background text-muted-foreground hover:bg-accent'
                    "
                >
                    Semua Format
                </button>
                <button
                    @click="formatFilter = 'knockout'"
                    class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                    :class="
                        formatFilter === 'knockout'
                            ? 'border-amber-600 bg-amber-600 text-white'
                            : 'border-border bg-background text-muted-foreground hover:bg-accent'
                    "
                >
                    Knockout
                </button>
                <button
                    @click="formatFilter = 'full_competition'"
                    class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                    :class="
                        formatFilter === 'full_competition'
                            ? 'border-indigo-600 bg-indigo-600 text-white'
                            : 'border-border bg-background text-muted-foreground hover:bg-accent'
                    "
                >
                    Liga (Penuh)
                </button>
                <button
                    @click="formatFilter = 'half_competition'"
                    class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                    :class="
                        formatFilter === 'half_competition'
                            ? 'border-sky-600 bg-sky-600 text-white'
                            : 'border-border bg-background text-muted-foreground hover:bg-accent'
                    "
                >
                    Setengah Kompetisi
                </button>
            </div>

            <!-- Empty State -->
            <div
                v-if="filteredCompetitions.length === 0"
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-card/40 p-6 py-16 text-center"
            >
                <div
                    class="mb-4 flex size-16 items-center justify-center rounded-2xl bg-muted text-muted-foreground/50"
                >
                    <Trophy class="size-8" />
                </div>
                <h3 class="text-base font-bold text-foreground">
                    Tidak Ada Lomba Ditemukan
                </h3>
                <p class="mt-1 max-w-md text-sm text-muted-foreground">
                    {{
                        searchQuery ||
                        statusFilter !== 'all' ||
                        formatFilter !== 'all'
                            ? 'Coba sesuaikan kata kunci atau filter pencarian Anda.'
                            : 'Belum ada lomba yang aktif saat ini. Pantau terus halaman ini untuk undian lomba terbaru!'
                    }}
                </p>
                <button
                    v-if="
                        searchQuery ||
                        statusFilter !== 'all' ||
                        formatFilter !== 'all'
                    "
                    @click="
                        searchQuery = '';
                        statusFilter = 'all';
                        formatFilter = 'all';
                    "
                    class="mt-4 text-xs font-semibold text-primary hover:underline"
                >
                    Reset Filter
                </button>
            </div>

            <!-- Competition Cards Grid -->
            <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="comp in filteredCompetitions"
                    :key="comp.id"
                    :href="show(comp.slug)"
                    class="group block rounded-2xl transition-transform duration-200 hover:-translate-y-1 focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                >
                    <Card
                        class="relative flex h-full flex-col overflow-hidden rounded-2xl border border-border/80 bg-card shadow-xs transition-all group-hover:border-primary/50 group-hover:shadow-md"
                    >
                        <!-- Top Accent Banner -->
                        <div
                            class="h-1.5 w-full"
                            :class="
                                comp.status === 'in_progress'
                                    ? 'bg-linear-to-r from-emerald-500 to-teal-500'
                                    : comp.status === 'completed'
                                      ? 'bg-linear-to-r from-indigo-500 to-purple-500'
                                      : 'bg-linear-to-r from-amber-500 to-orange-500'
                            "
                        ></div>

                        <!-- Decorative Sport Icon Background -->
                        <div
                            class="pointer-events-none absolute bottom-0 right-0 z-0 translate-x-4 translate-y-4 text-muted-foreground"
                        >
                            <CompetitionSportIcon
                                :sport="comp.sport"
                                class="size-40 opacity-50"
                            />
                        </div>

                        <CardHeader class="relative z-10 pt-5 pb-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 space-y-0.5">
                                    <CardTitle
                                        class="line-clamp-2 text-lg font-bold tracking-tight text-foreground transition-colors group-hover:text-primary"
                                    >
                                        {{ comp.name }}
                                    </CardTitle>
                                    <p
                                        class="text-xs font-medium text-muted-foreground"
                                    >
                                    </p>
                                </div>
                                <Badge
                                    :variant="
                                        comp.status === 'in_progress'
                                            ? 'default'
                                            : 'secondary'
                                    "
                                    class="shrink-0 px-2.5 py-0.5 text-xs font-semibold"
                                    :class="
                                        comp.status === 'in_progress'
                                            ? 'bg-emerald-600 text-white hover:bg-emerald-700'
                                            : comp.status === 'completed'
                                              ? 'bg-indigo-600 text-white'
                                              : ''
                                    "
                                >
                                    <span
                                        v-if="comp.status === 'in_progress'"
                                        class="mr-1.5 inline-block size-1.5 animate-pulse rounded-full bg-white"
                                    ></span>
                                    {{
                                        statusLabel[comp.status] || comp.status
                                    }}
                                </Badge>
                            </div>
                        </CardHeader>

                        <CardContent
                            class="relative z-10 flex flex-1 flex-col justify-between space-y-4 text-sm text-muted-foreground"
                        >
                            <div class="space-y-2.5">
                                <div
                                    class="flex items-center gap-2.5 text-xs font-medium"
                                >
                                    <Layers
                                        class="size-4 shrink-0 text-amber-500"
                                    />
                                    <span
                                        class="font-semibold text-foreground"
                                        >{{
                                            formatLabel[comp.format] ||
                                            comp.format
                                        }}</span
                                    >
                                </div>

                                <div class="flex items-center gap-2.5 text-xs">
                                    <Users
                                        class="size-4 shrink-0 text-indigo-500"
                                    />
                                    <span
                                        >{{ comp.participants_count }} Peserta
                                        Terdaftar</span
                                    >
                                </div>

                                <div class="flex items-center gap-2.5 text-xs">
                                    <Calendar
                                        class="size-4 shrink-0 text-emerald-500"
                                    />
                                    <span v-if="comp.total_matches > 0">
                                        <strong class="text-foreground">{{
                                            comp.completed_matches
                                        }}</strong>
                                        / {{ comp.total_matches }} Pertandingan
                                        Selesai
                                    </span>
                                    <span
                                        v-else
                                        class="text-muted-foreground italic"
                                        >Menunggu Undian Pertandingan</span
                                    >
                                </div>
                            </div>

                            <!-- Match Progress Bar -->
                            <div
                                v-if="comp.total_matches > 0"
                                class="border-t border-border/60 pt-3"
                            >
                                <div
                                    class="mb-1.5 flex items-center justify-between text-[11px] font-medium text-muted-foreground"
                                >
                                    <span>Progress Lomba</span>
                                    <span class="font-bold text-foreground">
                                        {{
                                            Math.round(
                                                (comp.completed_matches /
                                                    comp.total_matches) *
                                                    100,
                                            )
                                        }}%
                                    </span>
                                </div>
                                <div
                                    class="h-2 w-full overflow-hidden rounded-full bg-muted"
                                >
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="
                                            comp.completed_matches ===
                                            comp.total_matches
                                                ? 'bg-emerald-500'
                                                : 'bg-amber-500'
                                        "
                                        :style="{
                                            width: `${Math.min(100, Math.round((comp.completed_matches / comp.total_matches) * 100))}%`,
                                        }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Footer Action Link -->
                            <div
                                class="flex items-center justify-between pt-2 text-xs font-bold text-primary transition-transform group-hover:translate-x-0.5"
                            >
                                <span>Lihat Match & Detail</span>
                                <ChevronRight class="size-4" />
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </section>
    </PublicLayout>
</template>
