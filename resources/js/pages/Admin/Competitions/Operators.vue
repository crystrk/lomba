<script setup lang="ts">
import { watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, UserCheck } from '@lucide/vue';
import { toast } from 'vue-sonner';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { show } from '@/routes/admin/competitions';
import { sync } from '@/routes/admin/competitions/operators';

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

const form = useForm<{
    operator_ids: number[];
}>({
    operator_ids: [],
});

watch(
    () => props.assigned_ids,
    (newIds) => {
        const ids = Array.isArray(newIds) ? newIds.map(Number) : [];
        form.defaults('operator_ids', [...ids]);
        form.reset('operator_ids');
    },
    { immediate: true, deep: true },
);

function toggleOperator(id: number, checked: boolean | 'indeterminate') {
    const numericId = Number(id);
    const isChecked = checked === true;
    if (isChecked) {
        if (!form.operator_ids.includes(numericId)) {
            form.operator_ids = [...form.operator_ids, numericId];
        }
    } else {
        form.operator_ids = form.operator_ids.filter((opId) => Number(opId) !== numericId);
    }
}

function submit() {
    form.put(sync(props.competition.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            form.defaults('operator_ids', [...form.operator_ids]);
            toast.success('Penugasan operator berhasil disimpan.');
        },
    });
}
</script>

<template>
    <Head :title="`Operator: ${competition.name}`" />

    <div class="flex flex-col gap-6 p-6">
        <div>
            <Link
                :href="show(competition.id)"
                class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:underline"
            >
                <ArrowLeft class="size-4" />
                <span>{{ competition.name }}</span>
            </Link>
            <h1 class="text-2xl font-bold">Kelola Operator Lomba</h1>
        </div>

        <Card class="max-w-xl">
            <CardHeader class="flex flex-row items-center justify-between pb-3">
                <CardTitle class="text-lg font-semibold flex items-center gap-2">
                    <UserCheck class="size-5 text-primary" />
                    Pilih Operator Bertugas
                </CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-4">
                    <div
                        v-for="op in operators"
                        :key="op.id"
                        class="flex items-center gap-3 rounded-lg border p-3 hover:bg-muted/50 transition-colors"
                    >
                        <Checkbox
                            :id="`op-${op.id}`"
                            :model-value="form.operator_ids.includes(op.id)"
                            :disabled="!op.is_active"
                            @update:model-value="(checked: boolean | 'indeterminate') => toggleOperator(op.id, checked)"
                        />
                        <Label :for="`op-${op.id}`" class="flex-1 cursor-pointer">
                            <span class="font-medium">{{ op.name }}</span>
                            <span class="ml-2 text-sm text-muted-foreground">{{ op.email }}</span>
                        </Label>
                        <Badge v-if="!op.is_active" variant="secondary">Nonaktif</Badge>
                    </div>

                    <div
                        v-if="operators.length === 0"
                        class="text-sm text-muted-foreground py-4 text-center"
                    >
                        Belum ada operator. Buat operator terlebih dahulu.
                    </div>

                    <div v-if="form.errors.operator_ids" class="text-sm font-medium text-rose-600">
                        {{ form.errors.operator_ids }}
                    </div>

                    <div v-if="operators.length > 0" class="pt-4">
                        <Button type="submit" :disabled="form.processing">
                            <Save class="mr-2 size-4" />
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Penugasan' }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>




