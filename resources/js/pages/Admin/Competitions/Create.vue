<script setup lang="ts">
import { computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
import CompetitionSportIcon from '@/components/competitions/CompetitionSportIcon.vue';
import {
    competitionSports,
    type CompetitionSport,
} from '@/components/competitions/competitionSport';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index, store } from '@/routes/admin/competitions';

defineOptions({
    layout: AppLayout,
});

defineProps<{
    formats: string[];
    sports: string[];
}>();

const formatLabel: Record<string, string> = {
    knockout: 'Knockout',
    full_competition: 'Kompetisi Penuh',
    half_competition: 'Setengah Kompetisi',
};

const form = useForm({
    name: '',
    description: '',
    sport: '',
    format: 'half_competition',
    starts_at: '',
    ends_at: '',
    win_points: '3',
    draw_points: '1',
    loss_points: '0',
});

const isKnockout = computed(() => form.format === 'knockout');

watch(
    () => form.format,
    (newFormat) => {
        if (newFormat === 'knockout') {
            form.win_points = '';
            form.draw_points = '';
            form.loss_points = '';
        }
    },
);

function submit() {
    form.post(store().url);
}
</script>

<template>
    <Head title="Tambah Lomba" />

    <div class="flex flex-col gap-6 p-6">
        <div>
            <Link
                :href="index()"
                class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:underline"
            >
                <ArrowLeft class="size-4" />
                <span>Kembali ke daftar lomba</span>
            </Link>
            <h1 class="text-2xl font-bold">Tambah Lomba</h1>
        </div>

        <Card class="max-w-xl">
            <CardHeader>
                <CardTitle class="text-lg font-semibold"
                    >Informasi Lomba</CardTitle
                >
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <Label for="name">Nama Lomba</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Contoh: Turnamen Futsal 2026"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="description">Deskripsi (opsional)</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            class="min-h-[80px]"
                            placeholder="Penjelasan singkat mengenai lomba"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="space-y-2">
                        <Label for="sport">Cabang Olahraga</Label>
                        <Select v-model="form.sport">
                            <SelectTrigger id="sport">
                                <SelectValue
                                    placeholder="Pilih cabang olahraga"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="sport in sports"
                                    :key="sport"
                                    :value="sport"
                                >
                                    <span class="flex items-center gap-2">
                                        <CompetitionSportIcon
                                            :sport="sport"
                                            class="size-4"
                                        />
                                        {{
                                            competitionSports[
                                                sport as CompetitionSport
                                            ]?.label ?? sport
                                        }}
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <div
                            v-if="form.sport"
                            class="flex items-center gap-2 text-sm text-muted-foreground"
                        >
                            <CompetitionSportIcon
                                :sport="form.sport"
                                class="size-5 text-primary"
                            />
                            <span>{{
                                competitionSports[
                                    form.sport as CompetitionSport
                                ]?.label
                            }}</span>
                        </div>
                        <InputError :message="form.errors.sport" />
                    </div>

                    <div class="space-y-2">
                        <Label for="format">Format Lomba</Label>
                        <Select v-model="form.format">
                            <SelectTrigger id="format">
                                <SelectValue
                                    :placeholder="
                                        formatLabel[form.format] ||
                                        'Pilih format'
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="f in formats"
                                    :key="f"
                                    :value="f"
                                >
                                    {{ formatLabel[f] || f }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.format" />
                    </div>

                    <div v-if="!isKnockout" class="space-y-2">
                        <Label>Aturan Poin Klasemen</Label>
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
                                <InputError
                                    :message="form.errors.draw_points"
                                />
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
                                <InputError
                                    :message="form.errors.loss_points"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="starts_at"
                                >Tanggal Mulai (opsional)</Label
                            >
                            <Input
                                id="starts_at"
                                type="date"
                                v-model="form.starts_at"
                            />
                            <InputError :message="form.errors.starts_at" />
                        </div>
                        <div class="space-y-2">
                            <Label for="ends_at"
                                >Tanggal Selesai (opsional)</Label
                            >
                            <Input
                                id="ends_at"
                                type="date"
                                v-model="form.ends_at"
                            />
                            <InputError :message="form.errors.ends_at" />
                        </div>
                    </div>

                    <div class="flex gap-4 pt-2">
                        <Button type="submit" :disabled="form.processing">
                            <Save class="mr-2 size-4" />
                            {{
                                form.processing
                                    ? 'Menyimpan...'
                                    : 'Simpan Lomba'
                            }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
