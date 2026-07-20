<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { scores } from '@/routes/operator/competitions';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
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
    knockout: 'Gugur',
    full_competition: 'Kompetisi Penuh',
    half_competition: 'Setengah Kompetisi',
};
</script>

<template>
    <Head title="Dashboard Operator" />
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <h1 class="text-2xl font-bold">Dashboard Operator</h1>

        <div v-if="competitions.length === 0" class="py-12 text-center text-muted-foreground">
            Belum ada kompetisi yang ditugaskan.
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="comp in competitions"
                :key="comp.id"
                :href="scores(comp.id).url"
                class="block"
            >
                <Card class="transition-colors hover:bg-accent/50">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <CardTitle class="text-lg">{{ comp.name }}</CardTitle>
                            <Badge :variant="statusVariant[comp.status] || 'secondary'" class="text-xs">{{ statusLabel[comp.status] || comp.status }}</Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-1 text-sm text-muted-foreground">
                            <p>{{ formatLabel[comp.format] || comp.format }}</p>
                            <p>{{ comp.participants_count }} peserta, {{ comp.matches_count }} pertandingan</p>
                        </div>
                    </CardContent>
                </Card>
            </Link>
        </div>
    </div>
</template>
