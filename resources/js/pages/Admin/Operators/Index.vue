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
import { create, edit } from '@/routes/admin/operators';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    operators: Array<{
        id: number;
        name: string;
        email: string;
        is_active: boolean;
        created_at: string;
    }>;
}>();
</script>

<template>
    <Head title="Kelola Operator" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Kelola Operator</h1>
            <Link :href="create().url">
                <Button>Tambah Operator</Button>
            </Link>
        </div>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nama</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Dibuat</TableHead>
                        <TableHead>Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="op in operators" :key="op.id">
                        <TableCell class="font-medium">{{ op.name }}</TableCell>
                        <TableCell>{{ op.email }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    op.is_active ? 'default' : 'secondary'
                                "
                            >
                                {{ op.is_active ? 'Aktif' : 'Nonaktif' }}
                            </Badge>
                        </TableCell>
                        <TableCell>{{ op.created_at }}</TableCell>
                        <TableCell>
                            <div class="flex gap-2">
                                <Link :href="edit(op.id).url">
                                    <Button variant="outline" size="sm"
                                        >Edit</Button
                                    >
                                </Link>
                            </div>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="operators.length === 0">
                        <TableCell
                            colspan="5"
                            class="text-center text-muted-foreground"
                        >
                            Belum ada operator.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
