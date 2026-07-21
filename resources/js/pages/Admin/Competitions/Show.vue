<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Users, Shuffle, Trophy, UserCheck } from '@lucide/vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index, edit, scores, operators } from '@/routes/admin/competitions';
import draw from '@/routes/admin/competitions/draw';
import participants from '@/routes/admin/competitions/participants';

defineOptions({
    layout: AppLayout,
});

defineProps<{
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

const statusVariant: Record<string, 'default' | 'outline' | 'destructive' | 'secondary'> = {
    draft: 'secondary',
    drawn: 'secondary',
    locked: 'default',
    in_progress: 'default',
    completed: 'outline',
};
</script>

<template>
    <Head :title="competition.name" />

    <div class="flex flex-col gap-6 p-6">
        <div>
            <Link
                :href="index()"
                class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:underline"
            >
                <ArrowLeft class="size-4" />
                <span>Kembali ke daftar lomba</span>
            </Link>
            <div class="flex items-center justify-between mt-1">
                <h1 class="text-2xl font-bold">{{ competition.name }}</h1>
                <Link :href="edit(competition.id)">
                    <Button variant="outline">
                        <Pencil class="mr-2 size-4" />
                        Edit Lomba
                    </Button>
                </Link>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-lg font-semibold">Detail Lomba</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <span class="text-sm font-medium text-muted-foreground">Format</span>
                        <p class="mt-1 font-semibold text-foreground">
                            {{ formatLabel[competition.format] || competition.format }}
                        </p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-muted-foreground">Status</span>
                        <p class="mt-1">
                            <Badge :variant="statusVariant[competition.status] || 'secondary'">{{
                                labelStatus[competition.status] || competition.status
                            }}</Badge>
                        </p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-muted-foreground">Jumlah Peserta</span>
                        <p class="mt-1 font-semibold text-foreground">{{ competition.participants_count }} Peserta</p>
                    </div>
                    <div v-if="competition.win_points !== null">
                        <span class="text-sm font-medium text-muted-foreground">Poin Menang / Seri / Kalah</span>
                        <p class="mt-1 font-semibold text-foreground">
                            {{ competition.win_points }} /
                            {{ competition.draw_points }} /
                            {{ competition.loss_points }}
                        </p>
                    </div>
                    <div v-if="competition.starts_at">
                        <span class="text-sm font-medium text-muted-foreground">Tanggal Mulai</span>
                        <p class="mt-1 font-semibold text-foreground">{{ competition.starts_at }}</p>
                    </div>
                    <div v-if="competition.ends_at">
                        <span class="text-sm font-medium text-muted-foreground">Tanggal Selesai</span>
                        <p class="mt-1 font-semibold text-foreground">{{ competition.ends_at }}</p>
                    </div>
                    <div v-if="competition.description" class="sm:col-span-2 lg:col-span-3">
                        <span class="text-sm font-medium text-muted-foreground">Deskripsi</span>
                        <p class="mt-1 whitespace-pre-wrap text-sm text-foreground">{{ competition.description }}</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="flex flex-wrap gap-3">
            <Link :href="participants.index(competition.id)">
                <Button variant="outline">
                    <Users class="mr-2 size-4" />
                    Kelola Peserta
                </Button>
            </Link>
            <Link :href="operators(competition.id)">
                <Button variant="outline">
                    <UserCheck class="mr-2 size-4" />
                    Tugaskan Operator
                </Button>
            </Link>
            <Link :href="draw.show(competition.id)">
                <Button variant="outline">
                    <Shuffle class="mr-2 size-4" />
                    Atur Undian
                </Button>
            </Link>
            <Link :href="scores(competition.id)">
                <Button variant="outline">
                    <Trophy class="mr-2 size-4" />
                    Kelola Skor
                </Button>
            </Link>
        </div>
    </div>
</template>

