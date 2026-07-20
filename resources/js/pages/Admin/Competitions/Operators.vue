<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    competition: {
        id: number;
        name: string;
    };
    operators: Array<{
        id: number;
        name: string;
        email: string;
        is_active: boolean;
    }>;
    assigned_ids: number[];
}>();

const form = useForm({
    operator_ids: [...props.assigned_ids],
});

function toggleOperator(id: number) {
    const idx = form.operator_ids.indexOf(id);
    if (idx === -1) {
        form.operator_ids.push(id);
    } else {
        form.operator_ids.splice(idx, 1);
    }
}

function submit() {
    form.put(`/admin/competitions/${props.competition.id}/operators`);
}
</script>

<template>
    <Head :title="`Operator: ${competition.name}`" />

    <div class="flex flex-col gap-6 p-6">
        <div>
            <Link
                :href="`/admin/competitions/${competition.id}`"
                class="text-sm text-muted-foreground hover:underline"
            >
                &larr; {{ competition.name }}
            </Link>
            <h1 class="text-2xl font-bold">Kelola Operator Lomba</h1>
        </div>

        <form @submit.prevent="submit" class="max-w-lg space-y-4">
            <div
                v-for="op in operators"
                :key="op.id"
                class="flex items-center gap-3"
            >
                <input
                    :id="`op-${op.id}`"
                    type="checkbox"
                    :checked="form.operator_ids.includes(op.id)"
                    :disabled="!op.is_active"
                    class="h-4 w-4 rounded border-gray-300"
                    @change="toggleOperator(op.id)"
                />
                <Label :for="`op-${op.id}`" class="flex-1 cursor-pointer">
                    <span>{{ op.name }}</span>
                    <span class="ml-2 text-sm text-muted-foreground">{{
                        op.email
                    }}</span>
                </Label>
                <Badge v-if="!op.is_active" variant="secondary">Nonaktif</Badge>
            </div>

            <div
                v-if="operators.length === 0"
                class="text-sm text-muted-foreground"
            >
                Belum ada operator. Buat operator terlebih dahulu.
            </div>

            <div v-if="operators.length > 0" class="pt-4">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </Button>
            </div>
        </form>
    </div>
</template>
