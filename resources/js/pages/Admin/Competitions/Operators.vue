<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
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

const form = useForm({
    operator_ids: [...props.assigned_ids],
});

function onCheckedChange(checked: boolean | 'indeterminate', id: number) {
    const isChecked = checked === true;
    const idx = form.operator_ids.indexOf(id);
    if (isChecked && idx === -1) {
        form.operator_ids.push(id);
    } else if (!isChecked && idx !== -1) {
        form.operator_ids.splice(idx, 1);
    }
}

function submit() {
    form.put(sync(props.competition.id).url);
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
            <CardHeader>
                <CardTitle class="text-lg font-semibold">Pilih Operator Bertugas</CardTitle>
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
                            :checked="form.operator_ids.includes(op.id)"
                            :disabled="!op.is_active"
                            @update:checked="(checked: boolean | 'indeterminate') => onCheckedChange(checked, op.id)"
                        />
                        <Label :for="`op-${op.id}`" class="flex-1 cursor-pointer">
                            <span class="font-medium">{{ op.name }}</span>
                            <span class="ml-2 text-sm text-muted-foreground">{{
                                op.email
                            }}</span>
                        </Label>
                        <Badge v-if="!op.is_active" variant="secondary">Nonaktif</Badge>
                    </div>

                    <div
                        v-if="operators.length === 0"
                        class="text-sm text-muted-foreground py-4 text-center"
                    >
                        Belum ada operator. Buat operator terlebih dahulu.
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


