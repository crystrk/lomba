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

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    competition: {
        id: number;
        name: string;
        status: string;
    };
    participants: Array<{
        id: number;
        name: string;
        short_name: string | null;
        logo_url: string | null;
        draw_position: number | null;
    }>;
}>();

const isEditable = () =>
    props.competition.status === 'draft' ||
    props.competition.status === 'drawn';
</script>

<template>
    <Head :title="`Peserta: ${competition.name}`" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <Link
                    :href="`/admin/competitions/${competition.id}`"
                    class="text-sm text-muted-foreground hover:underline"
                >
                    &larr; {{ competition.name }}
                </Link>
                <h1 class="text-2xl font-bold">Peserta</h1>
            </div>
            <Link
                v-if="isEditable()"
                :href="`/admin/competitions/${competition.id}/participants/create`"
            >
                <Button>Tambah Peserta</Button>
            </Link>
        </div>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nama</TableHead>
                        <TableHead>Nama Pendek</TableHead>
                        <TableHead>Logo</TableHead>
                        <TableHead>Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="p in participants" :key="p.id">
                        <TableCell class="font-medium">{{ p.name }}</TableCell>
                        <TableCell>{{ p.short_name ?? '-' }}</TableCell>
                        <TableCell>
                            <img
                                v-if="p.logo_url"
                                :src="p.logo_url"
                                :alt="p.name"
                                class="h-8 w-8 rounded object-cover"
                            />
                            <span v-else class="text-muted-foreground">-</span>
                        </TableCell>
                        <TableCell>
                            <div class="flex gap-2">
                                <Link
                                    v-if="isEditable()"
                                    :href="`/admin/competitions/${competition.id}/participants/${p.id}/edit`"
                                >
                                    <Button variant="outline" size="sm"
                                        >Edit</Button
                                    >
                                </Link>
                            </div>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="participants.length === 0">
                        <TableCell
                            colspan="4"
                            class="text-center text-muted-foreground"
                        >
                            Belum ada peserta.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
