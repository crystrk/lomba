<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { login, dashboard } from '@/routes';
import { show } from '@/routes/public/competitions';
import { Badge } from '@/components/ui/badge';

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
    knockout: 'Gugur',
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
        <header class="flex items-center justify-between px-6 py-4">
            <h1 class="text-lg font-bold tracking-tight">Sistem Lomba</h1>
            <nav class="flex items-center gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="rounded-md border px-4 py-1.5 text-sm hover:bg-accent"
                >
                    Dashboard
                </Link>
                <Link
                    v-else
                    :href="login()"
                    class="rounded-md border px-4 py-1.5 text-sm hover:bg-accent"
                >
                    Login Staf
                </Link>
            </nav>
        </header>

        <main class="flex-1 px-6 py-8">
            <div v-if="competitions.length === 0" class="flex flex-col items-center justify-center py-24 text-center">
                <p class="text-lg text-muted-foreground">Belum ada lomba yang aktif.</p>
                <p class="mt-1 text-sm text-muted-foreground">Pantau terus untuk informasi lomba terbaru.</p>
            </div>

            <div v-else class="mx-auto grid max-w-5xl gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="comp in competitions"
                    :key="comp.id"
                    :href="show(comp.slug).url"
                    class="block rounded-xl border p-5 transition-colors hover:bg-accent/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <div class="flex items-start justify-between gap-2">
                        <h2 class="text-lg font-semibold">{{ comp.name }}</h2>
                        <Badge :variant="statusVariant[comp.status] || 'secondary'" class="shrink-0 text-xs">
                            {{ statusLabel[comp.status] || comp.status }}
                        </Badge>
                    </div>
                    <div class="mt-3 space-y-1 text-sm text-muted-foreground">
                        <p class="flex items-center gap-2">
                            <span>{{ formatLabel[comp.format] || comp.format }}</span>
                            <span aria-hidden="true">·</span>
                            <span>{{ comp.participants_count }} peserta</span>
                        </p>
                        <p v-if="comp.total_matches > 0">
                            {{ comp.completed_matches }} / {{ comp.total_matches }} pertandingan
                        </p>
                        <p v-else>Menunggu undian</p>
                    </div>
                </Link>
            </div>
        </main>
    </div>
</template>
