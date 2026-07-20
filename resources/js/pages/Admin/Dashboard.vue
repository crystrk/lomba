<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Button } from '@/components/ui/button';
import { create } from '@/routes/admin/competitions';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    stats: {
        total: number;
        draft: number;
        drawn: number;
        locked: number;
        in_progress: number;
        completed: number;
    };
}>();
</script>

<template>
    <Head title="Dashboard Admin" />
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Dashboard Admin</h1>
            <Link :href="create().url">
                <Button>Tambah Lomba</Button>
            </Link>
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
            <div class="rounded-lg border p-4 text-center">
                <p class="text-3xl font-bold">{{ stats.total }}</p>
                <p class="text-sm text-muted-foreground">Total</p>
            </div>
            <div class="rounded-lg border p-4 text-center">
                <p class="text-3xl font-bold">{{ stats.draft }}</p>
                <p class="text-sm text-muted-foreground">Draft</p>
            </div>
            <div class="rounded-lg border p-4 text-center">
                <p class="text-3xl font-bold">{{ stats.drawn }}</p>
                <p class="text-sm text-muted-foreground">Diundi</p>
            </div>
            <div class="rounded-lg border p-4 text-center">
                <p class="text-3xl font-bold">{{ stats.locked }}</p>
                <p class="text-sm text-muted-foreground">Terkunci</p>
            </div>
            <div class="rounded-lg border p-4 text-center">
                <p class="text-3xl font-bold">{{ stats.in_progress }}</p>
                <p class="text-sm text-muted-foreground">Berlangsung</p>
            </div>
            <div class="rounded-lg border p-4 text-center">
                <p class="text-3xl font-bold">{{ stats.completed }}</p>
                <p class="text-sm text-muted-foreground">Selesai</p>
            </div>
        </div>

        <div
            v-if="stats.total === 0"
            class="flex flex-col items-center gap-4 rounded-lg border p-12 text-center"
        >
            <p class="text-lg text-muted-foreground">Belum ada lomba.</p>
            <Link :href="create().url">
                <Button>Buat Lomba Pertama</Button>
            </Link>
        </div>
    </div>
</template>
