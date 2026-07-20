<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

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
    name: props.participant.name,
    short_name: props.participant.short_name ?? '',
    logo: null as File | null,
});

function submit() {
    form.post(
        `/admin/competitions/${props.competition.id}/participants/${props.participant.id}`,
        {
            method: 'put',
            headers: { 'Content-Type': 'multipart/form-data' },
        },
    );
}

function onLogoChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files?.[0]) {
        form.logo = target.files[0];
    }
}
</script>

<template>
    <Head :title="`Edit Peserta: ${participant.name}`" />

    <div class="flex flex-col gap-6 p-6">
        <div>
            <Link
                :href="`/admin/competitions/${competition.id}/participants`"
                class="text-sm text-muted-foreground hover:underline"
            >
                &larr; Kembali ke daftar peserta
            </Link>
            <h1 class="text-2xl font-bold">Edit Peserta</h1>
        </div>

        <form @submit.prevent="submit" class="max-w-md space-y-6">
            <div class="space-y-2">
                <Label for="name">Nama Peserta</Label>
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

            <div v-if="participant.logo_url" class="space-y-2">
                <Label>Logo Saat Ini</Label>
                <div>
                    <img
                        :src="participant.logo_url"
                        :alt="participant.name"
                        class="h-16 w-16 rounded object-cover"
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

            <div class="flex gap-4">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </Button>
            </div>
        </form>
    </div>
</template>
