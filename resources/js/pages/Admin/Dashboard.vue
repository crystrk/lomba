<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Trophy, Plus, FileText, Lock, CheckCircle2, Play, Users } from '@lucide/vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { create } from '@/routes/admin/competitions';

defineOptions({
    layout: AppLayout,
});

defineProps<{
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
            <div>
                <h1 class="text-2xl font-bold">Dashboard Admin</h1>
                <p class="text-sm text-muted-foreground">Ringkasan status dan statistik pengelolaan lomba</p>
            </div>
            <Link :href="create()">
                <Button>
                    <Plus class="mr-2 size-4" />
                    Tambah Lomba
                </Button>
            </Link>
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                    <CardTitle class="text-sm font-medium">Total</CardTitle>
                    <Trophy class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                    <CardTitle class="text-sm font-medium">Draft</CardTitle>
                    <FileText class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.draft }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                    <CardTitle class="text-sm font-medium">Diundi</CardTitle>
                    <Users class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.drawn }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                    <CardTitle class="text-sm font-medium">Terkunci</CardTitle>
                    <Lock class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.locked }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                    <CardTitle class="text-sm font-medium">Berlangsung</CardTitle>
                    <Play class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.in_progress }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                    <CardTitle class="text-sm font-medium">Selesai</CardTitle>
                    <CheckCircle2 class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.completed }}</div>
                </CardContent>
            </Card>
        </div>

        <Card v-if="stats.total === 0" class="p-8 text-center">
            <div class="flex flex-col items-center gap-4">
                <Trophy class="size-12 text-muted-foreground/50" />
                <p class="text-lg text-muted-foreground">Belum ada lomba yang terdaftar.</p>
                <Link :href="create()">
                    <Button>
                        <Plus class="mr-2 size-4" />
                        Buat Lomba Pertama
                    </Button>
                </Link>
            </div>
        </Card>
    </div>
</template>

