<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Eye, Pencil, Trash2 } from '@lucide/vue';
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
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
    DialogClose,
} from '@/components/ui/dialog';
import { create, edit, show as competitionShow, destroy } from '@/routes/admin/competitions';

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

const deleteDialogOpen = ref(false);
const selectedCompetition = ref<{ id: number; name: string } | null>(null);
const isDeleting = ref(false);

function confirmDelete(c: { id: number; name: string }) {
    selectedCompetition.value = c;
    deleteDialogOpen.value = true;
}

function handleDelete() {
    if (!selectedCompetition.value) return;
    isDeleting.value = true;
    router.delete(destroy(selectedCompetition.value.id).url, {
        onFinish: () => {
            isDeleting.value = false;
            deleteDialogOpen.value = false;
            selectedCompetition.value = null;
        },
    });
}
</script>

<template>
    <Head title="Daftar Lomba" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Daftar Lomba</h1>
                <p class="text-sm text-muted-foreground">Kelola seluruh lomba, peserta, dan penugasan operator</p>
            </div>
            <Link :href="create()">
                <Button>
                    <Plus class="mr-2 size-4" />
                    Tambah Lomba
                </Button>
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
                        <TableHead class="text-right">Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="c in competitions" :key="c.id">
                        <TableCell class="font-medium">
                            <Link :href="competitionShow(c.id)" class="hover:underline">
                                {{ c.name }}
                            </Link>
                        </TableCell>
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
                            <div class="flex justify-end gap-2">
                                <Link :href="competitionShow(c.id)">
                                    <Button variant="outline" size="sm">
                                        <Eye class="mr-1 size-3.5" />
                                        Detail
                                    </Button>
                                </Link>
                                <Link :href="edit(c.id)">
                                    <Button variant="outline" size="sm">
                                        <Pencil class="mr-1 size-3.5" />
                                        Edit
                                    </Button>
                                </Link>
                                <Button
                                    v-if="c.status === 'draft' || c.status === 'drawn'"
                                    variant="destructive"
                                    size="sm"
                                    @click="confirmDelete(c)"
                                >
                                    <Trash2 class="mr-1 size-3.5" />
                                    Hapus
                                </Button>
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

        <Dialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Konfirmasi Hapus Lomba</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus lomba "<span class="font-semibold text-foreground">{{ selectedCompetition?.name }}</span>"? Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline" :disabled="isDeleting">Batal</Button>
                    </DialogClose>
                    <Button variant="destructive" :disabled="isDeleting" @click="handleDelete">
                        {{ isDeleting ? 'Menghapus...' : 'Hapus Lomba' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

