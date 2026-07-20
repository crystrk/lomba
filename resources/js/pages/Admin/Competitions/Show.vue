<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    competition: {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        format: string;
        status: string;
        win_points: number | null;
        draw_points: number | null;
        loss_points: number | null;
        participants_count: number;
        starts_at: string | null;
        ends_at: string | null;
    };
}>();

const formatLabel: Record<string, string> = {
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
</script>

<template>
    <Head :title="competition.name" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">{{ competition.name }}</h1>
            <Link :href="`/admin/competitions/${competition.id}/edit`">
                <Button variant="outline">Edit</Button>
            </Link>
        </div>

        <div class="grid grid-cols-2 gap-4 rounded-lg border p-4">
            <div>
                <span class="text-sm text-muted-foreground">Format</span>
                <p class="font-medium">
                    {{ formatLabel[competition.format] || competition.format }}
                </p>
            </div>
            <div>
                <span class="text-sm text-muted-foreground">Status</span>
                <p>
                    <Badge>{{
                        labelStatus[competition.status] || competition.status
                    }}</Badge>
                </p>
            </div>
            <div v-if="competition.description">
                <span class="text-sm text-muted-foreground">Deskripsi</span>
                <p class="whitespace-pre-wrap">{{ competition.description }}</p>
            </div>
            <div>
                <span class="text-sm text-muted-foreground"
                    >Jumlah Peserta</span
                >
                <p class="font-medium">{{ competition.participants_count }}</p>
            </div>
            <div v-if="competition.win_points !== null">
                <span class="text-sm text-muted-foreground"
                    >Poin Menang / Seri / Kalah</span
                >
                <p class="font-medium">
                    {{ competition.win_points }} /
                    {{ competition.draw_points }} /
                    {{ competition.loss_points }}
                </p>
            </div>
            <div v-if="competition.starts_at">
                <span class="text-sm text-muted-foreground">Tanggal Mulai</span>
                <p class="font-medium">{{ competition.starts_at }}</p>
            </div>
            <div v-if="competition.ends_at">
                <span class="text-sm text-muted-foreground"
                    >Tanggal Selesai</span
                >
                <p class="font-medium">{{ competition.ends_at }}</p>
            </div>
        </div>

        <div class="flex gap-3">
            <Link :href="`/admin/competitions/${competition.id}/draw`">
                <Button variant="outline">Atur Undian</Button>
            </Link>
            <Link :href="`/admin/competitions/${competition.id}/scores`">
                <Button variant="outline">Kelola Skor</Button>
            </Link>
        </div>
    </div>
</template>
