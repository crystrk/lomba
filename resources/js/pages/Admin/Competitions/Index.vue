<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head title="Lomba" />
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <h1 class="text-2xl font-bold">Daftar Lomba</h1>
        <div class="relative min-h-[50vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <PlaceholderPattern />
        </div>
    </div>
</template>
