<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Pencil, Trash2 } from '@lucide/vue';
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
import { show } from '@/routes/admin/competitions';
import { create, edit, destroy } from '@/routes/admin/competitions/participants';

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

const isEditable = computed(
    () => props.competition.status === 'draft' || props.competition.status === 'drawn',
);

const deleteDialogOpen = ref(false);
const selectedParticipant = ref<{ id: number; name: string } | null>(null);
const isDeleting = ref(false);

function confirmDelete(p: { id: number; name: string }) {
    selectedParticipant.value = p;
    deleteDialogOpen.value = true;
}

function handleDelete() {
    if (!selectedParticipant.value) return;
    isDeleting.value = true;
    router.delete(destroy([props.competition.id, selectedParticipant.value.id]).url, {
        onFinish: () => {
            isDeleting.value = false;
            deleteDialogOpen.value = false;
            selectedParticipant.value = null;
        },
    });
}
</script>

<template>
    <Head :title="`Peserta: ${competition.name}`" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <Link
                    :href="show(competition.id)"
                    class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:underline"
                >
                    <ArrowLeft class="size-4" />
                    <span>{{ competition.name }}</span>
                </Link>
                <div class="flex items-center gap-3 mt-1">
                    <h1 class="text-2xl font-bold">Daftar Peserta</h1>
                    <Badge variant="outline">{{ participants.length }} Peserta</Badge>
                </div>
            </div>
            <Link
                v-if="isEditable"
                :href="create(competition.id)"
            >
                <Button>
                    <Plus class="mr-2 size-4" />
                    Tambah Peserta
                </Button>
            </Link>
        </div>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nama</TableHead>
                        <TableHead>Nama Pendek</TableHead>
                        <TableHead>Logo</TableHead>
                        <TableHead class="text-right">Aksi</TableHead>
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
                                class="h-8 w-8 rounded object-cover border"
                            />
                            <span v-else class="text-muted-foreground">-</span>
                        </TableCell>
                        <TableCell>
                            <div class="flex justify-end gap-2">
                                <Link
                                    v-if="isEditable"
                                    :href="edit([competition.id, p.id])"
                                >
                                    <Button variant="outline" size="sm">
                                        <Pencil class="mr-1 size-3.5" />
                                        Edit
                                    </Button>
                                </Link>
                                <Button
                                    v-if="isEditable"
                                    variant="destructive"
                                    size="sm"
                                    @click="confirmDelete(p)"
                                >
                                    <Trash2 class="mr-1 size-3.5" />
                                    Hapus
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="participants.length === 0">
                        <TableCell
                            colspan="4"
                            class="text-center text-muted-foreground"
                        >
                            Belum ada peserta terdaftar.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Dialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Konfirmasi Hapus Peserta</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus peserta "<span class="font-semibold text-foreground">{{ selectedParticipant?.name }}</span>"? Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline" :disabled="isDeleting">Batal</Button>
                    </DialogClose>
                    <Button variant="destructive" :disabled="isDeleting" @click="handleDelete">
                        {{ isDeleting ? 'Menghapus...' : 'Hapus Peserta' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

