<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { store } from '@/routes/admin/competitions';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    formats: string[];
}>();

const formatLabel: Record<string, string> = {
    knockout: 'Knockout',
    full_competition: 'Kompetisi Penuh',
    half_competition: 'Setengah Kompetisi',
};

const form = useForm({
    name: '',
    description: '',
    format: 'half_competition',
    starts_at: '',
    ends_at: '',
    win_points: '3',
    draw_points: '1',
    loss_points: '0',
});

const isKnockout = () => form.format === 'knockout';

function submit() {
    form.post(store().url);
}
</script>

<template>
    <Head title="Tambah Lomba" />

    <div class="flex flex-col gap-6 p-6">
        <h1 class="text-2xl font-bold">Tambah Lomba</h1>

        <form @submit.prevent="submit" class="max-w-lg space-y-6">
            <div class="space-y-2">
                <Label for="name">Nama Lomba</Label>
                <Input id="name" v-model="form.name" />
                <InputError :message="form.errors.name" />
            </div>

            <div class="space-y-2">
                <Label for="description">Deskripsi (opsional)</Label>
                <textarea
                    id="description"
                    v-model="form.description"
                    class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                />
                <InputError :message="form.errors.description" />
            </div>

            <div class="space-y-2">
                <Label for="format">Format</Label>
                <Select v-model="form.format">
                    <SelectTrigger id="format">
                        <SelectValue
                            :placeholder="
                                formatLabel[form.format] || 'Pilih format'
                            "
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="f in formats" :key="f" :value="f">
                            {{ formatLabel[f] || f }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.format" />
            </div>

            <div v-if="!isKnockout()" class="space-y-2">
                <Label>Poin</Label>
                <div class="flex gap-4">
                    <div class="flex-1 space-y-2">
                        <Label
                            for="win_points"
                            class="text-sm text-muted-foreground"
                            >Menang</Label
                        >
                        <Input
                            id="win_points"
                            type="number"
                            v-model="form.win_points"
                        />
                        <InputError :message="form.errors.win_points" />
                    </div>
                    <div class="flex-1 space-y-2">
                        <Label
                            for="draw_points"
                            class="text-sm text-muted-foreground"
                            >Seri</Label
                        >
                        <Input
                            id="draw_points"
                            type="number"
                            v-model="form.draw_points"
                        />
                        <InputError :message="form.errors.draw_points" />
                    </div>
                    <div class="flex-1 space-y-2">
                        <Label
                            for="loss_points"
                            class="text-sm text-muted-foreground"
                            >Kalah</Label
                        >
                        <Input
                            id="loss_points"
                            type="number"
                            v-model="form.loss_points"
                        />
                        <InputError :message="form.errors.loss_points" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="starts_at">Tanggal Mulai (opsional)</Label>
                    <Input
                        id="starts_at"
                        type="date"
                        v-model="form.starts_at"
                    />
                    <InputError :message="form.errors.starts_at" />
                </div>
                <div class="space-y-2">
                    <Label for="ends_at">Tanggal Selesai (opsional)</Label>
                    <Input id="ends_at" type="date" v-model="form.ends_at" />
                    <InputError :message="form.errors.ends_at" />
                </div>
            </div>

            <div class="flex gap-4">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </Button>
            </div>
        </form>
    </div>
</template>
