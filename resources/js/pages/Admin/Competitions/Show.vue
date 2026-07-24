<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Pencil,
    Users,
    Shuffle,
    Trophy,
    UserCheck,
    Calendar,
    ShieldCheck,
    CheckCircle2,
    Info,
    ChevronRight,
} from '@lucide/vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import CompetitionSportIcon from '@/components/competitions/CompetitionSportIcon.vue';
import { index, edit, scores, operators } from '@/routes/admin/competitions';
import draw from '@/routes/admin/competitions/draw';
import participants from '@/routes/admin/competitions/participants';

defineOptions({
    layout: AppLayout,
});

interface Operator {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
}

const props = defineProps<{
    competition: {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        sport: string | null;
        format: string;
        status: string;
        win_points: number | null;
        draw_points: number | null;
        loss_points: number | null;
        participants_count: number;
        matches_count: number;
        starts_at: string | null;
        ends_at: string | null;
        operators: Operator[];
    };
}>();

const formatLabel: Record<string, string> = {
    knockout: 'Knockout System',
    full_competition: 'Kompetisi Penuh (Full League)',
    half_competition: 'Setengah Kompetisi (Half League)',
};

const labelStatus: Record<string, string> = {
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

function getInitials(name: string): string {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
}
</script>

<template>
    <Head :title="competition.name" />

    <div class="flex flex-col gap-6 p-4 sm:p-6 max-w-7xl mx-auto w-full">
        <!-- Header & Navigation -->
        <div class="relative overflow-hidden flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <!-- Decorative Sport Icon Background -->
            <div
                class="pointer-events-none absolute -top-4 -right-4 z-0 text-muted-foreground"
            >
                <CompetitionSportIcon
                    :sport="competition.sport"
                    class="size-32 opacity-60"
                />
            </div>

            <div class="relative z-10">
                <Link
                    :href="index()"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
                >
                    <ArrowLeft class="size-4" />
                    <span>Kembali ke daftar lomba</span>
                </Link>
                <div class="flex flex-wrap items-center gap-3 mt-1.5">
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-foreground">
                        {{ competition.name }}
                    </h1>
                    <Badge :variant="statusVariant[competition.status] || 'secondary'" class="text-xs px-2.5 py-0.5 font-semibold">
                        {{ labelStatus[competition.status] || competition.status }}
                    </Badge>
                </div>
            </div>
            <Link :href="edit(competition.id)" class="relative z-10 w-full sm:w-auto">
                <Button variant="outline" class="w-full sm:w-auto shadow-xs">
                    <Pencil class="mr-2 size-4" />
                    Edit Lomba
                </Button>
            </Link>
        </div>

        <!-- Metric Stat Cards (Compact) -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="flex items-center gap-3 p-3 rounded-lg border bg-card shadow-2xs">
                <div class="p-2 rounded-md bg-primary/10 text-primary shrink-0">
                    <Trophy class="size-4" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-muted-foreground truncate">Format Lomba</p>
                    <p class="text-sm font-bold text-foreground truncate">
                        {{ formatLabel[competition.format] || competition.format }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 p-3 rounded-lg border bg-card shadow-2xs">
                <div class="p-2 rounded-md bg-blue-500/10 text-blue-600 dark:text-blue-400 shrink-0">
                    <Users class="size-4" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-muted-foreground truncate">Jumlah Peserta</p>
                    <p class="text-sm font-bold text-foreground truncate">
                        {{ competition.participants_count }} <span class="text-xs font-normal text-muted-foreground">Peserta</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 p-3 rounded-lg border bg-card shadow-2xs">
                <div class="p-2 rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 shrink-0">
                    <CheckCircle2 class="size-4" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-muted-foreground truncate">Total Match</p>
                    <p class="text-sm font-bold text-foreground truncate">
                        {{ competition.matches_count }} <span class="text-xs font-normal text-muted-foreground">Match</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 p-3 rounded-lg border bg-card shadow-2xs">
                <div class="p-2 rounded-md bg-purple-500/10 text-purple-600 dark:text-purple-400 shrink-0">
                    <UserCheck class="size-4" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-muted-foreground truncate">Operator</p>
                    <p class="text-sm font-bold text-foreground truncate">
                        {{ competition.operators.length }} <span class="text-xs font-normal text-muted-foreground">Petugas</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Main Content Area: Grid 2 Columns on desktop -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left Column (2 cols wide): Detail Lomba & Operator Terpilih -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Card 1: Informasi Detail & Aturan -->
                <Card class="shadow-xs overflow-hidden">
                    <CardHeader class="border-b bg-muted/20 py-4">
                        <CardTitle class="text-base font-semibold flex items-center gap-2">
                            <Info class="size-4 text-primary" />
                            Informasi & Aturan Lomba
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="rounded-lg border p-3.5 bg-card">
                                <span class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Format Lomba</span>
                                <p class="mt-1 font-semibold text-foreground text-sm">
                                    {{ formatLabel[competition.format] || competition.format }}
                                </p>
                            </div>

                            <div class="rounded-lg border p-3.5 bg-card">
                                <span class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Status Lomba</span>
                                <div class="mt-1">
                                    <Badge :variant="statusVariant[competition.status] || 'secondary'">
                                        {{ labelStatus[competition.status] || competition.status }}
                                    </Badge>
                                </div>
                            </div>

                            <div v-if="competition.win_points !== null" class="rounded-lg border p-3.5 bg-card sm:col-span-2">
                                <span class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Sistem Poin (Menang / Seri / Kalah)</span>
                                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                    <Badge variant="outline" class="bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20 font-mono">
                                        Menang: +{{ competition.win_points }}
                                    </Badge>
                                    <Badge variant="outline" class="bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20 font-mono">
                                        Seri: +{{ competition.draw_points }}
                                    </Badge>
                                    <Badge variant="outline" class="bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/20 font-mono">
                                        Kalah: +{{ competition.loss_points }}
                                    </Badge>
                                </div>
                            </div>

                            <div v-if="competition.starts_at || competition.ends_at" class="rounded-lg border p-3.5 bg-card sm:col-span-2 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <Calendar class="size-4 text-muted-foreground shrink-0" />
                                    <span class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Jadwal Pelaksanaan</span>
                                </div>
                                <div class="text-sm font-semibold text-foreground">
                                    {{ competition.starts_at || 'TBA' }} &mdash; {{ competition.ends_at || 'TBA' }}
                                </div>
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div v-if="competition.description" class="mt-4 pt-4 border-t">
                            <span class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Deskripsi Lomba</span>
                            <p class="mt-1.5 text-sm text-muted-foreground whitespace-pre-wrap leading-relaxed">
                                {{ competition.description }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card 2: Operator Terpilih (Assigned Operators) -->
                <Card class="shadow-xs overflow-hidden">
                    <CardHeader class="border-b bg-muted/20 py-4 flex flex-row items-center justify-between">
                        <div>
                            <CardTitle class="text-base font-semibold flex items-center gap-2">
                                <ShieldCheck class="size-4 text-purple-600 dark:text-purple-400" />
                                Operator Terpilih
                            </CardTitle>
                            <CardDescription class="text-xs mt-0.5">
                                Petugas yang memiliki hak akses mengelola dan menginput skor pada pertandingan lomba ini.
                            </CardDescription>
                        </div>
                        <Link :href="operators(competition.id)">
                            <Button variant="ghost" size="sm" class="text-xs gap-1 text-primary hover:text-primary">
                                <span>Kelola</span>
                                <ChevronRight class="size-3.5" />
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent class="p-5">
                        <div v-if="competition.operators.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div
                                v-for="op in competition.operators"
                                :key="op.id"
                                class="flex items-center gap-3 p-3 rounded-lg border bg-card hover:bg-accent/40 transition-colors"
                            >
                                <div class="size-9 rounded-full bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 font-bold text-xs flex items-center justify-center shrink-0 border border-purple-200 dark:border-purple-800">
                                    {{ getInitials(op.name) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-sm font-semibold text-foreground truncate">{{ op.name }}</p>
                                        <span
                                            class="inline-block size-2 rounded-full shrink-0"
                                            :class="op.is_active ? 'bg-emerald-500' : 'bg-muted-foreground/40'"
                                            :title="op.is_active ? 'Aktif' : 'Nonaktif'"
                                        />
                                    </div>
                                    <p class="text-xs text-muted-foreground truncate">{{ op.email }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Empty state if no operators assigned -->
                        <div v-else class="text-center py-6 px-4 rounded-lg border border-dashed bg-muted/10">
                            <UserCheck class="size-8 mx-auto text-muted-foreground/60" />
                            <p class="mt-2 text-sm font-medium text-foreground">Belum ada operator terpilih</p>
                            <p class="text-xs text-muted-foreground max-w-md mx-auto mt-1">
                                Lomba ini belum ditugaskan kepada operator mana pun. Tugaskan operator agar mereka dapat menginput skor pertandingan.
                            </p>
                            <Link :href="operators(competition.id)" class="inline-block mt-4">
                                <Button size="sm" variant="outline" class="gap-1.5 text-xs">
                                    <UserCheck class="size-3.5" />
                                    Tugaskan Operator Sekarang
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Right Column (1 col wide): Quick Action Navigation Hub -->
            <div class="space-y-6">
                <Card class="shadow-xs">
                    <CardHeader class="border-b bg-muted/20 py-4">
                        <CardTitle class="text-base font-semibold">Menu Pengelolaan</CardTitle>
                        <CardDescription class="text-xs">Akses cepat fitur manajemen lomba</CardDescription>
                    </CardHeader>
                    <CardContent class="p-4 space-y-2.5">
                        <Link :href="participants.index(competition.id)" class="block">
                            <Button variant="outline" class="w-full justify-between h-auto py-3 px-3.5 text-left group">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-md bg-blue-500/10 text-blue-600 dark:text-blue-400 group-hover:bg-blue-500/20 transition-colors">
                                        <Users class="size-4" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-foreground">Kelola Peserta</div>
                                        <div class="text-xs text-muted-foreground">{{ competition.participants_count }} peserta terdaftar</div>
                                    </div>
                                </div>
                                <ChevronRight class="size-4 text-muted-foreground group-hover:translate-x-0.5 transition-transform" />
                            </Button>
                        </Link>

                        <Link :href="draw.show(competition.id)" class="block">
                            <Button variant="outline" class="w-full justify-between h-auto py-3 px-3.5 text-left group">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-md bg-amber-500/10 text-amber-600 dark:text-amber-400 group-hover:bg-amber-500/20 transition-colors">
                                        <Shuffle class="size-4" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-foreground">Undian & Jadwal</div>
                                        <div class="text-xs text-muted-foreground">Bagan & urutan pertandingan</div>
                                    </div>
                                </div>
                                <ChevronRight class="size-4 text-muted-foreground group-hover:translate-x-0.5 transition-transform" />
                            </Button>
                        </Link>

                        <Link :href="scores(competition.id)" class="block">
                            <Button variant="outline" class="w-full justify-between h-auto py-3 px-3.5 text-left group">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 group-hover:bg-emerald-500/20 transition-colors">
                                        <Trophy class="size-4" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-foreground">Kelola Skor</div>
                                        <div class="text-xs text-muted-foreground">Input hasil pertandingan</div>
                                    </div>
                                </div>
                                <ChevronRight class="size-4 text-muted-foreground group-hover:translate-x-0.5 transition-transform" />
                            </Button>
                        </Link>

                        <Link :href="operators(competition.id)" class="block">
                            <Button variant="outline" class="w-full justify-between h-auto py-3 px-3.5 text-left group">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-md bg-purple-500/10 text-purple-600 dark:text-purple-400 group-hover:bg-purple-500/20 transition-colors">
                                        <UserCheck class="size-4" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-foreground">Tugaskan Operator</div>
                                        <div class="text-xs text-muted-foreground">{{ competition.operators.length }} operator aktif</div>
                                    </div>
                                </div>
                                <ChevronRight class="size-4 text-muted-foreground group-hover:translate-x-0.5 transition-transform" />
                            </Button>
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
