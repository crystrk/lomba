<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { update, toggleActive } from '@/routes/admin/operators';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    operator: {
        id: number;
        name: string;
        email: string;
        is_active: boolean;
    };
}>();

const form = useForm({
    name: props.operator.name,
    email: props.operator.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.put(update(props.operator.id).url, {
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
}

function toggleActiveFn() {
    form.patch(toggleActive(props.operator.id).url);
}
</script>

<template>
    <Head title="Edit Operator" />

    <div class="flex flex-col gap-6 p-6">
        <h1 class="text-2xl font-bold">Edit Operator</h1>

        <form @submit.prevent="submit" class="max-w-md space-y-6">
            <div class="space-y-2">
                <Label for="name">Nama</Label>
                <Input id="name" v-model="form.name" />
                <InputError :message="form.errors.name" />
            </div>

            <div class="space-y-2">
                <Label for="email">Email</Label>
                <Input id="email" type="email" v-model="form.email" />
                <InputError :message="form.errors.email" />
            </div>

            <div class="space-y-2">
                <Label for="password">Password (biarkan kosong jika tidak diubah)</Label>
                <Input id="password" type="password" v-model="form.password" />
                <InputError :message="form.errors.password" />
            </div>

            <div class="flex gap-4">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </Button>

                <Button type="button" variant="outline" @click="toggleActiveFn">
                    {{ operator.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </Button>
            </div>
        </form>
    </div>
</template>
