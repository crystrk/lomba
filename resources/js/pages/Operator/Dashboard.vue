<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Trophy, Calendar, Users } from '@lucide/vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { scores } from '@/routes/operator/competitions';

defineOptions({
    layout: AppLayout,
});

defineProps<{
    competitions: Array<{
        id: number;
        name: string;
        slug: string;
        format: string;
        status: string;
        participants_count: number;
        matches_count: number;
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

const formatLabel: Record<string, string> = {
    knockout: 'Knockout',
    full_competition: 'Kompetisi Penuh',
    half_competition: 'Setengah Kompetisi',
};
</script>

<template>
    <Head title="Dashboard Operator" />
    <div class="flex flex-col gap-6 p-6">
        <div>
            <h1 class="text-2xl font-bold">Dashboard Operator</h1>
            <p class="text-sm text-muted-foreground">Daftar lomba yang ditugaskan kepada Anda untuk pengelolaan skor</p>
        </div>

        <Card v-if="competitions.length === 0" class="p-8 text-center text-muted-foreground">
            <Trophy class="size-12 mx-auto mb-3 text-muted-foreground/40" />
            <p>Belum ada lomba yang ditugaskan kepada Anda saat ini.</p>
        </Card>

        <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="comp in competitions"
                :key="comp.id"
                :href="scores(comp.id)"
                class="block group"
            >
                <Card class="h-full transition-all group-hover:border-primary/50 group-hover:shadow-sm">
                    <CardHeader class="pb-3">
                        <div class="flex items-start justify-between gap-2">
                            <CardTitle class="text-lg font-bold group-hover:text-primary transition-colors">{{ comp.name }}</CardTitle>
                            <Badge :variant="statusVariant[comp.status] || 'secondary'" class="text-xs shrink-0">
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
                            <span>{{ comp.matches_count }} Pertandingan</span>
                        </div>
                    </CardContent>
                </Card>
            </Link>
        </div>
    </div>
</template>

