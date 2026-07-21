<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
import { index, update } from '@/routes/admin/competitions/participants';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    competition: {
        id: number;
        name: string;
    };
    participant: {
        id: number;
        name: string;
        short_name: string | null;
        logo_url: string | null;
    };
}>();

const form = useForm({
    _method: 'put',
    name: props.participant.name,
    short_name: props.participant.short_name ?? '',
    logo: null as File | null,
});

const previewUrl = ref<string | null>(null);

function submit() {
    form.post(update([props.competition.id, props.participant.id]).url);
}

function onLogoChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files?.[0]) {
        const file = target.files[0];
        form.logo = file;
        previewUrl.value = URL.createObjectURL(file);
    } else {
        form.logo = null;
        previewUrl.value = null;
    }
}
</script>

<template>
    <Head :title="`Edit Peserta: ${participant.name}`" />

    <div class="flex flex-col gap-6 p-6">
        <div>
            <Link
                :href="index(competition.id)"
                class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:underline"
            >
                <ArrowLeft class="size-4" />
                <span>Kembali ke daftar peserta</span>
            </Link>
            <h1 class="text-2xl font-bold">Edit Peserta</h1>
        </div>

        <Card class="max-w-md">
            <CardHeader>
                <CardTitle class="text-lg font-semibold">Ubah Informasi Peserta</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <Label for="name">Nama Peserta / Tim</Label>
                        <Input id="name" v-model="form.name" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="short_name">Nama Pendek (opsional)</Label>
                        <Input
                            id="short_name"
                            v-model="form.short_name"
                            maxlength="10"
                        />
                        <InputError :message="form.errors.short_name" />
                    </div>

                    <div v-if="previewUrl || participant.logo_url" class="space-y-2">
                        <Label>{{ previewUrl ? 'Pratinjau Logo Baru' : 'Logo Saat Ini' }}</Label>
                        <div>
                            <img
                                :src="previewUrl || participant.logo_url!"
                                :alt="participant.name"
                                class="h-16 w-16 rounded object-cover border"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="logo">Ganti Logo (opsional)</Label>
                        <Input
                            id="logo"
                            type="file"
                            accept="image/png,image/jpg,image/jpeg,image/webp"
                            @change="onLogoChange"
                        />
                        <InputError :message="form.errors.logo" />
                    </div>

                    <div class="flex gap-4 pt-2">
                        <Button type="submit" :disabled="form.processing">
                            <Save class="mr-2 size-4" />
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>


