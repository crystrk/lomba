<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Trophy, Calendar, Users, LogIn, LayoutDashboard } from '@lucide/vue';
import { login, dashboard } from '@/routes';
import { show } from '@/routes/public/competitions';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineProps<{
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
    knockout: 'Knockout',
    full_competition: 'Kompetisi Penuh',
    half_competition: 'Setengah Kompetisi',
};

const statusLabel: Record<string, string> = {
    locked: 'Terkunci',
    in_progress: 'Berlangsung',
};

const statusVariant: Record<string, 'default' | 'outline' | 'secondary'> = {
    locked: 'default',
    in_progress: 'default',
};
</script>

<template>
    <Head title="Beranda" />
    <div class="flex min-h-screen flex-col bg-[#FDFDFC] dark:bg-[#0a0a0a]">
        <header class="flex items-center justify-between border-b px-6 py-4 bg-background/80 backdrop-blur">
            <div class="flex items-center gap-2">
                <Trophy class="size-6 text-amber-500" />
                <h1 class="text-lg font-bold tracking-tight">Sistem Manajemen Lomba</h1>
            </div>
            <nav class="flex items-center gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-flex items-center gap-1.5 rounded-md border px-4 py-1.5 text-sm font-medium transition-colors hover:bg-accent"
                >
                    <LayoutDashboard class="size-4" />
                    <span>Dashboard</span>
                </Link>
                <Link
                    v-else
                    :href="login()"
                    class="inline-flex items-center gap-1.5 rounded-md border px-4 py-1.5 text-sm font-medium transition-colors hover:bg-accent"
                >
                    <LogIn class="size-4" />
                    <span>Login Staf</span>
                </Link>
            </nav>
        </header>

        <main class="flex-1 px-6 py-8">
            <div v-if="competitions.length === 0" class="flex flex-col items-center justify-center py-24 text-center">
                <Trophy class="size-16 text-muted-foreground/30 mb-4" />
                <p class="text-lg font-medium text-muted-foreground">Belum ada lomba yang aktif saat ini.</p>
                <p class="mt-1 text-sm text-muted-foreground">Pantau terus halaman ini untuk jadwal dan undian lomba terbaru.</p>
            </div>

            <div v-else class="mx-auto grid max-w-5xl gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="comp in competitions"
                    :key="comp.id"
                    :href="show(comp.slug)"
                    class="block group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring rounded-xl"
                >
                    <Card class="h-full transition-all group-hover:border-primary/50 group-hover:shadow-md">
                        <CardHeader class="pb-3">
                            <div class="flex items-start justify-between gap-2">
                                <CardTitle class="text-lg font-bold group-hover:text-primary transition-colors">{{ comp.name }}</CardTitle>
                                <Badge :variant="statusVariant[comp.status] || 'secondary'" class="shrink-0 text-xs">
                                    {{ statusLabel[comp.status] || comp.status }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-2 text-sm text-muted-foreground">
                            <div class="flex items-center gap-2">
                                <Trophy class="size-4 text-muted-foreground/70" />
                                <span>{{ formatLabel[comp.format] || comp.format }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Users class="size-4 text-muted-foreground/70" />
                                <span>{{ comp.participants_count }} Peserta</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Calendar class="size-4 text-muted-foreground/70" />
                                <span v-if="comp.total_matches > 0">
                                    {{ comp.completed_matches }} / {{ comp.total_matches }} Pertandingan
                                </span>
                                <span v-else>Menunggu Undian</span>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </main>
    </div>
</template>

