<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Trophy, UserCog } from '@lucide/vue';
import { watch } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { update, toggleActive, syncCompetitions } from '@/routes/admin/operators';
import { index as operatorsIndex } from '@/routes/admin/operators';

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
    competitions: Array<{
        id: number;
        name: string;
        format: string;
        status: string;
    }>;
    assigned_competition_ids: number[];
}>();

const profileForm = useForm({
    name: props.operator.name,
    email: props.operator.email,
    password: '',
    password_confirmation: '',
});

const competitionForm = useForm<{
    competition_ids: number[];
}>({
    competition_ids: [],
});

watch(
    () => props.assigned_competition_ids,
    (newIds) => {
        const ids = Array.isArray(newIds) ? newIds.map(Number) : [];
        competitionForm.defaults('competition_ids', [...ids]);
        competitionForm.reset('competition_ids');
    },
    { immediate: true, deep: true },
);

function submitProfile() {
    profileForm.put(update(props.operator.id).url, {
        onSuccess: () => {
            profileForm.reset('password', 'password_confirmation');
            toast.success('Akun operator berhasil diperbarui.');
        },
    });
}

function toggleActiveFn() {
    profileForm.patch(toggleActive(props.operator.id).url, {
        onSuccess: () => toast.success(`Operator berhasil ${props.operator.is_active ? 'dinonaktifkan' : 'diaktifkan'}.`),
    });
}

function toggleCompetition(id: number, checked: boolean | 'indeterminate') {
    const numericId = Number(id);
    const isChecked = checked === true;

    if (isChecked) {
        if (!competitionForm.competition_ids.includes(numericId)) {
            competitionForm.competition_ids = [...competitionForm.competition_ids, numericId];
        }
    } else {
        competitionForm.competition_ids = competitionForm.competition_ids.filter((cId) => Number(cId) !== numericId);
    }
}

function submitCompetitions() {
    competitionForm.put(syncCompetitions(props.operator.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            competitionForm.defaults('competition_ids', [...competitionForm.competition_ids]);
            toast.success('Penugasan lomba berhasil diperbarui.');
        },
    });
}

const formatLabel: Record<string, string> = {
    knockout: 'Knockout',
    final_four: 'Final Four',
    group_final_four: 'Group Final Four',
    group_four_final: 'Group Final Four',
    full_competition: 'Liga Penuh',
    half_competition: 'Setengah Liga',
};

const statusLabel: Record<string, string> = {
    drawn: 'Undian',
    locked: 'Terkunci',
    in_progress: 'Berlangsung',
    completed: 'Selesai',
};
</script>

<template>
    <Head title="Edit Operator" />

    <div class="flex flex-col gap-6 p-6">
        <div>
            <Link
                :href="operatorsIndex().url"
                class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:underline"
            >
                <ArrowLeft class="size-4" />
                <span>Kembali ke Daftar Operator</span>
            </Link>
            <h1 class="text-2xl font-bold mt-1">Edit Operator: {{ operator.name }}</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- KOLOM 1: PROFIL OPERATOR -->
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-lg font-semibold flex items-center gap-2">
                        <UserCog class="size-5 text-primary" />
                        Profil Akun Operator
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitProfile" class="space-y-5">
                        <div class="space-y-2">
                            <Label for="name">Nama</Label>
                            <Input id="name" v-model="profileForm.name" />
                            <InputError :message="profileForm.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input id="email" type="email" v-model="profileForm.email" />
                            <InputError :message="profileForm.errors.email" />
                        </div>

                        <div class="space-y-2">
                            <Label for="password">Password (biarkan kosong jika tidak diubah)</Label>
                            <Input id="password" type="password" v-model="profileForm.password" />
                            <InputError :message="profileForm.errors.password" />
                        </div>

                        <div class="flex gap-3 pt-2">
                            <Button type="submit" :disabled="profileForm.processing">
                                {{ profileForm.processing ? 'Menyimpan...' : 'Simpan Profil' }}
                            </Button>

                            <Button type="button" variant="outline" @click="toggleActiveFn">
                                {{ operator.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- KOLOM 2: PENUGASAN LOMBA -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-3">
                    <CardTitle class="text-lg font-semibold flex items-center gap-2">
                        <Trophy class="size-5 text-amber-500" />
                        Penugasan Lomba
                    </CardTitle>
                    <Badge variant="secondary" class="text-xs">
                        {{ competitionForm.competition_ids.length }} Lomba Ditugaskan
                    </Badge>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitCompetitions" class="space-y-4">
                        <p class="text-sm text-muted-foreground">
                            Pilih lomba yang ingin ditugaskan ke operator ini. Operator hanya bisa mengelola skor pada lomba yang ditugaskan.
                        </p>

                        <div v-if="competitions.length > 0" class="space-y-2 max-h-[400px] overflow-y-auto pr-1">
                            <div
                                v-for="comp in competitions"
                                :key="comp.id"
                                class="flex items-center gap-3 rounded-lg border p-3 hover:bg-muted/50 transition-colors"
                                :class="competitionForm.competition_ids.includes(comp.id) ? 'border-primary/40 bg-primary/5' : ''"
                            >
                                <Checkbox
                                    :id="`comp-${comp.id}`"
                                    :model-value="competitionForm.competition_ids.includes(comp.id)"
                                    @update:model-value="(checked: boolean | 'indeterminate') => toggleCompetition(comp.id, checked)"
                                />
                                <Label :for="`comp-${comp.id}`" class="flex-1 cursor-pointer space-y-0.5">
                                    <span class="font-medium text-sm block">{{ comp.name }}</span>
                                    <span class="text-xs text-muted-foreground flex items-center gap-2">
                                        <Badge variant="outline" class="text-[10px] px-1.5 py-0">{{ formatLabel[comp.format] || comp.format }}</Badge>
                                        <Badge
                                            :variant="comp.status === 'in_progress' ? 'default' : 'secondary'"
                                            class="text-[10px] px-1.5 py-0"
                                            :class="comp.status === 'in_progress' ? 'bg-emerald-600 text-white' : ''"
                                        >
                                            {{ statusLabel[comp.status] || comp.status }}
                                        </Badge>
                                    </span>
                                </Label>
                            </div>
                        </div>

                        <div
                            v-else
                            class="text-sm text-muted-foreground py-6 text-center border rounded-lg border-dashed"
                        >
                            Belum ada lomba yang bisa ditugaskan (belum ada lomba dengan status selain Draft).
                        </div>

                        <div v-if="competitionForm.errors.competition_ids" class="text-sm font-medium text-rose-600">
                            {{ competitionForm.errors.competition_ids }}
                        </div>

                        <div v-if="competitions.length > 0" class="pt-2">
                            <Button type="submit" :disabled="competitionForm.processing" class="w-full">
                                <Save class="mr-2 size-4" />
                                {{ competitionForm.processing ? 'Menyimpan...' : 'Simpan Penugasan Lomba' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
