<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { create } from '@/routes/admin/competitions';

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
        starts_at: string | null;
        ends_at: string | null;
        created_at: string;
    }>;
}>();

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

const statusVariant = {
    draft: 'secondary',
    drawn: 'secondary',
    locked: 'default',
    in_progress: 'default',
    completed: 'outline',
} as const;
</script>

<template>
    <Head title="Daftar Lomba" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Daftar Lomba</h1>
            <Link :href="create().url">
                <Button>Tambah Lomba</Button>
            </Link>
        </div>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nama</TableHead>
                        <TableHead>Format</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Peserta</TableHead>
                        <TableHead>Tanggal Mulai</TableHead>
                        <TableHead>Tanggal Selesai</TableHead>
                        <TableHead>Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="c in competitions" :key="c.id">
                        <TableCell class="font-medium">{{ c.name }}</TableCell>
                        <TableCell>{{
                            labelFormat[
                                c.format as keyof typeof labelFormat
                            ] || c.format
                        }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    statusVariant[
                                        c.status as keyof typeof statusVariant
                                    ] || 'secondary'
                                "
                            >
                                {{
                                    labelStatus[
                                        c.status as keyof typeof labelStatus
                                    ] || c.status
                                }}
                            </Badge>
                        </TableCell>
                        <TableCell>{{ c.participants_count }}</TableCell>
                        <TableCell>{{ c.starts_at ?? '-' }}</TableCell>
                        <TableCell>{{ c.ends_at ?? '-' }}</TableCell>
                        <TableCell>
                            <div class="flex gap-2">
                                <Link
                                    :href="`/admin/competitions/${c.id}/edit`"
                                >
                                    <Button variant="outline" size="sm"
                                        >Edit</Button
                                    >
                                </Link>
                            </div>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="competitions.length === 0">
                        <TableCell
                            colspan="7"
                            class="text-center text-muted-foreground"
                        >
                            Belum ada lomba.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
