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
    Layers
} from '@lucide/vue';
import { show } from '@/routes/public/competitions';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    competitions: Array<{
        id: number;
        name: string;
        slug: string;
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
const formatFilter = ref<'all' | 'knockout' | 'full_competition' | 'half_competition'>('all');

const filteredCompetitions = computed(() => {
    return props.competitions.filter((comp) => {
        const matchesSearch = comp.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesStatus = statusFilter.value === 'all' || comp.status === statusFilter.value;
        const matchesFormat = formatFilter.value === 'all' || comp.format === formatFilter.value;
        return matchesSearch && matchesStatus && matchesFormat;
    });
});

const inProgressCount = computed(() => props.competitions.filter(c => c.status === 'in_progress').length);
const totalParticipants = computed(() => props.competitions.reduce((acc, c) => acc + c.participants_count, 0));
</script>

<template>
    <Head title="Beranda Lomba & Turnamen Live" />

    <PublicLayout>
        <!-- Hero Banner Section -->
        <section class="relative overflow-hidden border-b border-border/50 bg-linear-to-b from-amber-500/5 via-transparent to-transparent py-12 sm:py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="max-w-3xl space-y-4">
                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-600 dark:text-amber-400 backdrop-blur">
                        <Flame class="size-3.5 fill-amber-500" />
                        <span>Portal Informasi & Hasil Pertandingan Real-Time</span>
                    </div>

                    <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-foreground leading-tight">
                        Pantau Jalannya <span class="bg-linear-to-r from-amber-500 to-orange-500 bg-clip-text text-transparent">Turnamen & Lomba</span> Secara Real-Time
                    </h1>

                    <p class="text-base sm:text-lg text-muted-foreground leading-relaxed">
                        Lihat bagan gugur (knockout), klasemen perolehan poin, jadwal, dan skor akhir seluruh pertandingan dari satu genggaman.
                    </p>

                    <!-- Quick Stats Badges -->
                    <div class="pt-2 flex flex-wrap items-center gap-4 text-xs sm:text-sm font-medium">
                        <div class="flex items-center gap-2 rounded-xl bg-card border border-border/80 px-3.5 py-2 shadow-xs">
                            <div class="flex size-7 items-center justify-center rounded-lg bg-amber-500/10 text-amber-500">
                                <Trophy class="size-4" />
                            </div>
                            <div>
                                <span class="block text-xs text-muted-foreground">Total Lomba Aktif</span>
                                <span class="font-bold text-foreground text-sm">{{ competitions.length }} Lomba</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 rounded-xl bg-card border border-border/80 px-3.5 py-2 shadow-xs">
                            <div class="flex size-7 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-500">
                                <Flame class="size-4" />
                            </div>
                            <div>
                                <span class="block text-xs text-muted-foreground">Sedang Berlangsung</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400 text-sm">{{ inProgressCount }} Lomba</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 rounded-xl bg-card border border-border/80 px-3.5 py-2 shadow-xs">
                            <div class="flex size-7 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-500">
                                <Users class="size-4" />
                            </div>
                            <div>
                                <span class="block text-xs text-muted-foreground">Total Peserta</span>
                                <span class="font-bold text-foreground text-sm">{{ totalParticipants }} Peserta</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Search & Filter Controls + Competition List -->
        <section id="lomba-aktif" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border/60 pb-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                        <Trophy class="size-5 text-amber-500" />
                        Daftar Lomba Aktif
                    </h2>
                    <p class="text-xs sm:text-sm text-muted-foreground">Pilih salah satu lomba di bawah untuk melihat detail match & klasemen.</p>
                </div>

                <!-- Search Input -->
                <div class="relative w-full sm:w-72">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery" 
                        type="text" 
                        placeholder="Cari nama lomba..." 
                        class="pl-9 text-sm rounded-lg"
                    />
                </div>
            </div>

            <!-- Format & Status Pill Filters -->
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-muted-foreground flex items-center gap-1 mr-1">
                    <Filter class="size-3.5" />
                    Filter:
                </span>

                <button
                    @click="statusFilter = 'all'"
                    class="rounded-full px-3 py-1 text-xs font-medium transition-colors border"
                    :class="statusFilter === 'all' ? 'bg-primary text-primary-foreground border-primary' : 'bg-background text-muted-foreground hover:bg-accent border-border'"
                >
                    Semua Status
                </button>
                <button
                    @click="statusFilter = 'in_progress'"
                    class="rounded-full px-3 py-1 text-xs font-medium transition-colors border"
                    :class="statusFilter === 'in_progress' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-background text-muted-foreground hover:bg-accent border-border'"
                >
                    🟢 Sedang Berlangsung
                </button>
                <button
                    @click="statusFilter = 'locked'"
                    class="rounded-full px-3 py-1 text-xs font-medium transition-colors border"
                    :class="statusFilter === 'locked' ? 'bg-amber-600 text-white border-amber-600' : 'bg-background text-muted-foreground hover:bg-accent border-border'"
                >
                    🔒 Terkunci / Siap
                </button>
                <button
                    @click="statusFilter = 'completed'"
                    class="rounded-full px-3 py-1 text-xs font-medium transition-colors border"
                    :class="statusFilter === 'completed' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-background text-muted-foreground hover:bg-accent border-border'"
                >
                    🏆 Selesai
                </button>

                <div class="h-4 w-px bg-border my-auto mx-1 hidden sm:block"></div>

                <button
                    @click="formatFilter = 'all'"
                    class="rounded-full px-3 py-1 text-xs font-medium transition-colors border"
                    :class="formatFilter === 'all' ? 'bg-primary text-primary-foreground border-primary' : 'bg-background text-muted-foreground hover:bg-accent border-border'"
                >
                    Semua Format
                </button>
                <button
                    @click="formatFilter = 'knockout'"
                    class="rounded-full px-3 py-1 text-xs font-medium transition-colors border"
                    :class="formatFilter === 'knockout' ? 'bg-amber-600 text-white border-amber-600' : 'bg-background text-muted-foreground hover:bg-accent border-border'"
                >
                    Knockout
                </button>
                <button
                    @click="formatFilter = 'full_competition'"
                    class="rounded-full px-3 py-1 text-xs font-medium transition-colors border"
                    :class="formatFilter === 'full_competition' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-background text-muted-foreground hover:bg-accent border-border'"
                >
                    Liga (Penuh)
                </button>
                <button
                    @click="formatFilter = 'half_competition'"
                    class="rounded-full px-3 py-1 text-xs font-medium transition-colors border"
                    :class="formatFilter === 'half_competition' ? 'bg-sky-600 text-white border-sky-600' : 'bg-background text-muted-foreground hover:bg-accent border-border'"
                >
                    Setengah Kompetisi
                </button>
            </div>

            <!-- Empty State -->
            <div v-if="filteredCompetitions.length === 0" class="flex flex-col items-center justify-center py-16 text-center rounded-2xl border border-dashed border-border bg-card/40 p-6">
                <div class="flex size-16 items-center justify-center rounded-2xl bg-muted text-muted-foreground/50 mb-4">
                    <Trophy class="size-8" />
                </div>
                <h3 class="text-base font-bold text-foreground">Tidak Ada Lomba Ditemukan</h3>
                <p class="mt-1 text-sm text-muted-foreground max-w-md">
                    {{ searchQuery || statusFilter !== 'all' || formatFilter !== 'all' ? 'Coba sesuaikan kata kunci atau filter pencarian Anda.' : 'Belum ada lomba yang aktif saat ini. Pantau terus halaman ini untuk undian lomba terbaru!' }}
                </p>
                <button 
                    v-if="searchQuery || statusFilter !== 'all' || formatFilter !== 'all'"
                    @click="searchQuery = ''; statusFilter = 'all'; formatFilter = 'all'"
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
                    class="group block focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary rounded-2xl transition-transform hover:-translate-y-1 duration-200"
                >
                    <Card class="h-full border border-border/80 bg-card shadow-xs group-hover:border-primary/50 group-hover:shadow-md transition-all rounded-2xl overflow-hidden flex flex-col">
                        <!-- Top Accent Banner -->
                        <div 
                            class="h-1.5 w-full"
                            :class="comp.status === 'in_progress' ? 'bg-linear-to-r from-emerald-500 to-teal-500' : comp.status === 'completed' ? 'bg-linear-to-r from-indigo-500 to-purple-500' : 'bg-linear-to-r from-amber-500 to-orange-500'"
                        ></div>

                        <CardHeader class="pb-3 pt-5">
                            <div class="flex items-start justify-between gap-3">
                                <CardTitle class="text-lg font-bold tracking-tight text-foreground group-hover:text-primary transition-colors line-clamp-2">
                                    {{ comp.name }}
                                </CardTitle>
                                <Badge 
                                    :variant="comp.status === 'in_progress' ? 'default' : 'secondary'"
                                    class="shrink-0 text-xs font-semibold px-2.5 py-0.5"
                                    :class="comp.status === 'in_progress' ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : comp.status === 'completed' ? 'bg-indigo-600 text-white' : ''"
                                >
                                    <span v-if="comp.status === 'in_progress'" class="size-1.5 rounded-full bg-white animate-pulse mr-1.5 inline-block"></span>
                                    {{ statusLabel[comp.status] || comp.status }}
                                </Badge>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-4 text-sm text-muted-foreground flex-1 flex flex-col justify-between">
                            <div class="space-y-2.5">
                                <div class="flex items-center gap-2.5 text-xs font-medium">
                                    <Layers class="size-4 text-amber-500 shrink-0" />
                                    <span class="text-foreground font-semibold">{{ formatLabel[comp.format] || comp.format }}</span>
                                </div>

                                <div class="flex items-center gap-2.5 text-xs">
                                    <Users class="size-4 text-indigo-500 shrink-0" />
                                    <span>{{ comp.participants_count }} Peserta Terdaftar</span>
                                </div>

                                <div class="flex items-center gap-2.5 text-xs">
                                    <Calendar class="size-4 text-emerald-500 shrink-0" />
                                    <span v-if="comp.total_matches > 0">
                                        <strong class="text-foreground">{{ comp.completed_matches }}</strong> / {{ comp.total_matches }} Pertandingan Selesai
                                    </span>
                                    <span v-else class="italic text-muted-foreground">Menunggu Undian Pertandingan</span>
                                </div>
                            </div>

                            <!-- Match Progress Bar -->
                            <div v-if="comp.total_matches > 0" class="pt-3 border-t border-border/60">
                                <div class="flex items-center justify-between text-[11px] font-medium text-muted-foreground mb-1.5">
                                    <span>Progress Lomba</span>
                                    <span class="font-bold text-foreground">
                                        {{ Math.round((comp.completed_matches / comp.total_matches) * 100) }}%
                                    </span>
                                </div>
                                <div class="h-2 w-full rounded-full bg-muted overflow-hidden">
                                    <div 
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="comp.completed_matches === comp.total_matches ? 'bg-emerald-500' : 'bg-amber-500'"
                                        :style="{ width: `${Math.min(100, Math.round((comp.completed_matches / comp.total_matches) * 100))}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Footer Action Link -->
                            <div class="pt-2 flex items-center justify-between text-xs font-bold text-primary group-hover:translate-x-0.5 transition-transform">
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
