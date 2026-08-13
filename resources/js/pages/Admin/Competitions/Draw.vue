<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Shuffle,
    Lock,
    Trophy,
    Users,
    AlertTriangle,
    CheckCircle2,
    ListOrdered,
    Save,
    Sparkles,
    Eye,
    Clock,
} from '@lucide/vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
    DialogClose,
} from '@/components/ui/dialog';
import {
    show as drawShow,
    shuffle,
    reorder,
    lock,
} from '@/routes/admin/competitions';
import ParticipantSortableList from '@/components/Admin/ParticipantSortableList.vue';
import { generateClientPreview, type ParticipantItem, type ClientMatchSlot } from '@/lib/drawPreviewGenerator';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    competition: {
        id: number;
        name: string;
        slug: string;
        format: string;
        status: string;
        draw_version: number;
    };
    participants: Array<{
        id: number;
        name: string;
        short_name: string | null;
        draw_position: number | null;
    }>;
    matches: Array<{
        id: number;
        round: number;
        leg: number;
        sequence: number;
        scheduled_time?: string | null;
        home: { id: number; name: string; short_name: string | null } | null;
        away: { id: number; name: string; short_name: string | null } | null;
        status: string;
        next_match_id: number | null;
        next_slot: number | null;
    }>;
}>();

const formatLabel: Record<string, string> = {
    knockout: 'Knockout (Sistem Gugur)',
    final_four: 'Final Four (Semifinal, Final & Perebutan Juara 3)',
    group_final_four: 'Group Final Four (2 Grup Penyisihan & Final 1-4)',
    full_competition: 'Kompetisi Penuh (Double Round-Robin)',
    half_competition: 'Setengah Kompetisi (Single Round-Robin)',
};

const statusLabel: Record<string, string> = {
    draft: 'Draft',
    drawn: 'Diundi',
    locked: 'Terkunci',
    in_progress: 'Berlangsung',
    completed: 'Selesai',
};

const statusVariant: Record<string, 'default' | 'outline' | 'destructive' | 'secondary'> = {
    draft: 'secondary',
    drawn: 'secondary',
    locked: 'default',
    in_progress: 'default',
    completed: 'outline',
};

const isKnockout = computed(() => props.competition.format === 'knockout' || props.competition.format === 'final_four');
const isGroupFinalFour = computed(() => props.competition.format === 'group_final_four');
const isValidGroupFinalFour = computed(() => !isGroupFinalFour.value || (orderedParticipants.value.length > 4 && orderedParticipants.value.length % 2 === 0));
const canEditDraw = computed(() => props.competition.status === 'draft' || props.competition.status === 'drawn');
const canLock = computed(() => props.competition.status === 'drawn' && !isDirty.value && isValidGroupFinalFour.value);

// Reactive participants state for manual sorting
const initialParticipants = computed<ParticipantItem[]>(() => [...props.participants]);
const orderedParticipants = ref<ParticipantItem[]>([...props.participants]);

watch(
    () => props.participants,
    (newVal) => {
        orderedParticipants.value = [...newVal];
    },
    { deep: true, immediate: true }
);

// Check if client order differs from server order
const isDirty = computed(() => {
    if (orderedParticipants.value.length !== props.participants.length) return true;
    for (let i = 0; i < orderedParticipants.value.length; i++) {
        if (orderedParticipants.value[i].id !== props.participants[i].id) {
            return true;
        }
    }
    return false;
});

// Client-side Instant Live Preview Generator
const activeMatches = computed<(ClientMatchSlot | typeof props.matches[0])[]>(() => {
    // If dirty or no server matches exist yet, compute live preview in real-time
    if (isDirty.value || props.matches.length === 0) {
        return generateClientPreview(props.competition.format, orderedParticipants.value);
    }
    return props.matches;
});

const groupedMatches = computed(() => {
    const groups: Record<string, typeof activeMatches.value> = {};
    for (const m of activeMatches.value) {
        const key = isKnockout.value
            ? `Ronde ${m.round}`
            : `Ronde ${m.round}${m.leg === 2 ? ' (Leg 2)' : ''}`;
        if (!groups[key]) groups[key] = [];
        groups[key].push(m);
    }
    return groups;
});

const scoredMatchCount = computed(
    () => activeMatches.value.filter((m) => m.status === 'ready' || m.status === 'pending').length,
);

const byeMatchCount = computed(
    () => activeMatches.value.filter((m) => m.status === 'bye').length,
);

// Active Tab / Mode Selection
const drawMode = ref<'manual' | 'random'>('manual');
const shuffleDialogOpen = ref(false);
const lockDialogOpen = ref(false);

const reorderForm = useForm({
    participant_ids: [] as number[],
});

function saveReorder() {
    reorderForm.participant_ids = orderedParticipants.value.map((p) => p.id);
    reorderForm.post(reorder(props.competition.id).url, {
        preserveScroll: true,
    });
}

function cancelReorder() {
    orderedParticipants.value = [...props.participants];
}

const shuffleForm = useForm({});

function executeShuffle() {
    shuffleForm.post(shuffle(props.competition.id).url, {
        onSuccess: () => {
            shuffleDialogOpen.value = false;
        },
    });
}

const lockForm = useForm({
    draw_version: props.competition.draw_version,
});

watch(
    () => props.competition.draw_version,
    (newVersion) => {
        lockForm.draw_version = newVersion;
    },
    { immediate: true },
);

function executeLock() {
    lockForm.draw_version = props.competition.draw_version;
    lockForm.post(lock(props.competition.id).url, {
        onSuccess: () => {
            lockDialogOpen.value = false;
        },
    });
}

function participantName(match: typeof activeMatches.value[0], side: 'home' | 'away'): string {
    const p = match[side];
    if (!p) return '—';
    return p.short_name || p.name;
}
</script>

<template>
    <Head :title="`Undian - ${competition.name}`" />

    <div class="flex flex-col gap-6 p-6">
        <div>
            <Link
                :href="drawShow(competition.id)"
                class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:underline"
            >
                <ArrowLeft class="size-4" />
                <span>{{ competition.name }}</span>
            </Link>
            <div class="flex items-center justify-between mt-1">
                <h1 class="text-2xl font-bold tracking-tight">Undian & Jadwal Pertandingan</h1>
                <div class="flex items-center gap-3">
                    <Badge :variant="statusVariant[competition.status] || 'secondary'">
                        {{ statusLabel[competition.status] || competition.status }}
                    </Badge>
                    <span class="text-sm font-medium text-muted-foreground">
                        v{{ competition.draw_version }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                    <CardTitle class="text-sm font-medium">Format Lomba</CardTitle>
                    <Trophy class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold">{{ formatLabel[competition.format] || competition.format }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                    <CardTitle class="text-sm font-medium">Jumlah Peserta</CardTitle>
                    <Users class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold">{{ participants.length }} Peserta</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                    <CardTitle class="text-sm font-medium">Total Pertandingan</CardTitle>
                    <CheckCircle2 class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold">
                        {{ activeMatches.length }} Match
                        <span class="text-xs font-normal text-muted-foreground"
                            >({{ scoredMatchCount }} dihitung skor)</span
                        >
                    </div>
                </CardContent>
            </Card>
        </div>

        <div v-if="byeMatchCount > 0" class="flex items-center gap-2 rounded-lg border bg-amber-500/10 border-amber-500/30 p-3 text-sm text-amber-900 dark:text-amber-200">
            <AlertTriangle class="size-4 text-amber-500 shrink-0" />
            <span>Terdapat <strong>{{ byeMatchCount }}</strong> pertandingan <em>bye</em> (peserta otomatis lolos ke babak berikutnya).</span>
        </div>

        <!-- Banner Warning Format Group Final Four Tidak Memenuhi Syarat -->
        <div
            v-if="isGroupFinalFour && !isValidGroupFinalFour"
            class="flex flex-col gap-2 rounded-lg border border-destructive/50 bg-destructive/10 p-4 text-sm text-destructive"
        >
            <div class="flex items-center gap-2 font-bold text-base">
                <AlertTriangle class="size-5 shrink-0" />
                <span>Format Group Final Four Tidak Memenuhi Syarat</span>
            </div>
            <p>
                Format <strong>Group Final Four</strong> membutuhkan MINIMAL <strong>6 TIM</strong> dan jumlah tim HARUS <strong>GENAP</strong> (6, 8, 10, dst.).
                Saat ini terdaftar <strong>{{ participants.length }} Peserta</strong>. Silakan tambahkan atau sesuaikan jumlah tim terlebih dahulu sebelum membuat undian dan jadwal.
            </p>
        </div>

        <!-- Banner Peringatan Perubahan Belum Disimpan -->
        <div
            v-if="isDirty"
            class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 rounded-lg border border-amber-500/40 bg-amber-500/15 p-4 text-amber-950 dark:text-amber-100"
        >
            <div class="flex items-center gap-2 text-sm font-medium">
                <Sparkles class="size-5 text-amber-600 shrink-0" />
                <div>
                    <span class="font-bold">Live Preview Aktif:</span>
                    Urutan peserta telah diubah. Jadwal di bawah menampilkan pratinjau instan. Klik tombol simpan untuk memperbarui secara permanen.
                </div>
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto shrink-0">
                <Button variant="outline" size="sm" class="w-full sm:w-auto" :disabled="reorderForm.processing" @click="cancelReorder">
                    Batal
                </Button>
                <Button size="sm" class="w-full sm:w-auto" :disabled="reorderForm.processing" @click="saveReorder">
                    <Save class="mr-1.5 size-4" />
                    {{ reorderForm.processing ? 'Menyimpan...' : 'Simpan & Regenerasi Undian' }}
                </Button>
            </div>
        </div>

        <!-- Mode Pengundian Navigation & Action Header -->
        <div v-if="canEditDraw" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b pb-4">
            <div class="flex items-center rounded-lg bg-muted p-1 gap-1">
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-8 text-xs font-medium"
                    :class="{ 'bg-background shadow-xs text-foreground': drawMode === 'manual' }"
                    @click="drawMode = 'manual'"
                >
                    <ListOrdered class="mr-1.5 size-3.5" />
                    Urutkan Manual (Drag & Drop)
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-8 text-xs font-medium"
                    :class="{ 'bg-background shadow-xs text-foreground': drawMode === 'random' }"
                    @click="drawMode = 'random'"
                >
                    <Shuffle class="mr-1.5 size-3.5" />
                    Acak Otomatis (Shuffle)
                </Button>
            </div>

            <div class="flex items-center gap-2">
                <Button
                    v-if="isDirty"
                    variant="default"
                    size="sm"
                    :disabled="reorderForm.processing || !isValidGroupFinalFour"
                    @click="saveReorder"
                >
                    <Save class="mr-1.5 size-4" />
                    {{ reorderForm.processing ? 'Menyimpan...' : 'Simpan Urutan Manual' }}
                </Button>
                <Button
                    v-if="drawMode === 'random' && canEditDraw"
                    variant="outline"
                    size="sm"
                    @click="shuffleDialogOpen = true"
                    :disabled="shuffleForm.processing || !isValidGroupFinalFour"
                >
                    <Shuffle class="mr-1.5 size-4" />
                    {{ matches.length > 0 ? 'Acak Ulang Undian' : 'Acak & Buat Undian' }}
                </Button>
                <Button
                    v-if="canLock"
                    variant="default"
                    size="sm"
                    @click="lockDialogOpen = true"
                    :disabled="lockForm.processing || isDirty || !isValidGroupFinalFour"
                >
                    <Lock class="mr-1.5 size-4" />
                    Kunci Undian
                </Button>
            </div>
        </div>

        <!-- Alert bila peserta kurang dari 2 -->
        <Card v-if="participants.length < 2" class="p-8 text-center text-muted-foreground">
            Minimal 2 peserta diperlukan untuk membuat undian pertandingan.
        </Card>

        <!-- Visual Editor Urutan Peserta (Manual Sortable List) -->
        <div v-if="participants.length >= 2" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-5 space-y-4">
                <ParticipantSortableList
                    v-model="orderedParticipants"
                    :initial-participants="initialParticipants"
                    :disabled="!canEditDraw"
                />

                <div v-if="canEditDraw && drawMode === 'random'" class="rounded-lg border bg-muted/40 p-4 space-y-2 text-sm text-muted-foreground">
                    <div class="flex items-center gap-2 font-medium text-foreground">
                        <Shuffle class="size-4 text-primary" />
                        <span>Pengacak Acak Otomatis (Shuffle)</span>
                    </div>
                    <p class="text-xs">
                        Klik tombol di bawah ini untuk mengacak urutan posisi undian peserta secara acak dan otomatis.
                    </p>
                    <Button
                        variant="secondary"
                        size="sm"
                        class="w-full mt-2"
                        :disabled="shuffleForm.processing"
                        @click="shuffleDialogOpen = true"
                    >
                        <Shuffle class="mr-1.5 size-4" />
                        Acak Urutan Sekarang
                    </Button>
                </div>
            </div>

            <!-- Preview Jadwal Pertandingan (Live Preview) -->
            <div class="lg:col-span-7 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold flex items-center gap-2">
                        <Eye class="size-5 text-primary" />
                        <span>Daftar Pertandingan (Preview)</span>
                    </h2>
                    <Badge v-if="isDirty" variant="outline" class="bg-amber-500/10 text-amber-800 border-amber-400 dark:text-amber-300">
                        Live Preview (Belum Disimpan)
                    </Badge>
                </div>

                <template v-for="(roundMatches, roundKey) in groupedMatches" :key="roundKey">
                    <Card class="border">
                        <CardHeader class="border-b py-2.5 bg-muted/30">
                            <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                {{ roundKey }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div class="divide-y">
                                <div
                                    v-for="m in roundMatches"
                                    :key="m.id"
                                    class="flex items-center gap-4 px-4 py-3 text-sm"
                                >
                                    <span class="w-8 font-mono text-xs text-muted-foreground">#{{ m.sequence }}</span>
                                    <div v-if="m.status === 'bye'" class="flex-1 italic text-muted-foreground">
                                        Bye (Otomatis Lolos)
                                    </div>
                                    <div v-else class="flex flex-1 items-center justify-between gap-2">
                                        <span class="font-medium text-foreground">{{ participantName(m, 'home') }}</span>
                                        <div class="flex items-center gap-2">
                                            <Badge v-if="m.scheduled_time" variant="outline" class="text-xs font-normal text-muted-foreground flex items-center gap-1">
                                                <Clock class="size-3" />
                                                {{ m.scheduled_time }}
                                            </Badge>
                                            <span class="text-xs font-bold uppercase text-muted-foreground">VS</span>
                                        </div>
                                        <span class="font-medium text-foreground">{{ participantName(m, 'away') }}</span>
                                    </div>
                                    <Badge v-if="m.status === 'bye'" variant="outline" class="text-xs">Bye</Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </template>
            </div>
        </div>

        <!-- Modal Konfirmasi Shuffle -->
        <Dialog :open="shuffleDialogOpen" @update:open="shuffleDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Konfirmasi Acak Ulang Undian</DialogTitle>
                    <DialogDescription>
                        Mengacak ulang akan membatalkan dan mengganti seluruh susunan pertandingan yang sudah dibuat. Apakah Anda yakin ingin melanjutkan?
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline" :disabled="shuffleForm.processing">Batal</Button>
                    </DialogClose>
                    <Button :disabled="shuffleForm.processing" @click="executeShuffle">
                        <Shuffle class="mr-2 size-4" />
                        {{ shuffleForm.processing ? 'Mengacak...' : 'Ya, Acak Ulang' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal Konfirmasi Lock -->
        <Dialog :open="lockDialogOpen" @update:open="lockDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Konfirmasi Kunci Undian</DialogTitle>
                    <DialogDescription class="space-y-2 pt-2">
                        <p>Setelah undian dikunci:</p>
                        <ul class="list-disc pl-5 text-sm space-y-1 text-muted-foreground">
                            <li>Susunan pertandingan dan urutan peserta tidak dapat diubah lagi.</li>
                            <li>Format lomba dan aturan poin tidak dapat diedit.</li>
                            <li>Peserta baru tidak dapat ditambahkan atau dihapus.</li>
                        </ul>
                        <div class="rounded-md border bg-muted/40 p-3 mt-3 text-xs">
                            <p><strong>Format:</strong> {{ formatLabel[competition.format] || competition.format }}</p>
                            <p><strong>Peserta:</strong> {{ participants.length }} Peserta</p>
                            <p><strong>Pertandingan Bernilai Skor:</strong> {{ scoredMatchCount }} Match</p>
                        </div>
                        <div v-if="lockForm.errors.draw_version" class="rounded-md bg-destructive/15 p-3 text-xs font-medium text-destructive">
                            {{ lockForm.errors.draw_version }}
                        </div>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline" :disabled="lockForm.processing">Batal</Button>
                    </DialogClose>
                    <Button variant="default" :disabled="lockForm.processing || isDirty" @click="executeLock">
                        <Lock class="mr-2 size-4" />
                        {{ lockForm.processing ? 'Mengunci...' : 'Kunci Undian Sekarang' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
