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
    lock,
} from '@/routes/admin/competitions';

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
        home: { id: number; name: string; short_name: string | null } | null;
        away: { id: number; name: string; short_name: string | null } | null;
        status: string;
        next_match_id: number | null;
        next_slot: number | null;
    }>;
}>();

const formatLabel: Record<string, string> = {
    knockout: 'Knockout',
    full_competition: 'Kompetisi Penuh',
    half_competition: 'Setengah Kompetisi',
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

const isKnockout = computed(() => props.competition.format === 'knockout');

const groupedMatches = computed(() => {
    const groups: Record<string, typeof props.matches> = {};
    for (const m of props.matches) {
        const key = isKnockout.value
            ? `Ronde ${m.round}`
            : `Ronde ${m.round}${m.leg === 2 ? ' (Leg 2)' : ''}`;
        if (!groups[key]) groups[key] = [];
        groups[key].push(m);
    }
    return groups;
});

const scoredMatchCount = computed(
    () => props.matches.filter((m) => m.status === 'ready' || m.status === 'pending').length,
);

const byeMatchCount = computed(
    () => props.matches.filter((m) => m.status === 'bye').length,
);

const canDraw = computed(
    () => props.competition.status === 'draft' || props.competition.status === 'drawn',
);

const canLock = computed(() => props.competition.status === 'drawn');

const shuffleDialogOpen = ref(false);
const lockDialogOpen = ref(false);

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

function participantName(match: typeof props.matches[0], side: 'home' | 'away'): string {
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
                <h1 class="text-2xl font-bold">Undian & Jadwal Pertandingan</h1>
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
                    <div class="text-xl font-bold">{{ formatLabel[competition.format] }}</div>
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
                        {{ matches.length }} Match
                        <span class="text-xs font-normal text-muted-foreground"
                            >({{ scoredMatchCount }} dihitung skor)</span
                        >
                    </div>
                </CardContent>
            </Card>
        </div>

        <div v-if="byeMatchCount > 0" class="flex items-center gap-2 rounded-lg border bg-muted/40 p-3 text-sm text-muted-foreground">
            <AlertTriangle class="size-4 text-amber-500 shrink-0" />
            <span>Terdapat <strong>{{ byeMatchCount }}</strong> pertandingan <em>bye</em> (peserta otomatis lolos ke babak berikutnya).</span>
        </div>

        <div class="flex gap-3">
            <Button
                v-if="canDraw"
                @click="shuffleDialogOpen = true"
                :disabled="shuffleForm.processing"
            >
                <Shuffle class="mr-2 size-4" />
                {{ matches.length > 0 ? 'Acak Ulang Undian' : 'Acak & Buat Undian' }}
            </Button>
            <Button
                v-if="canLock"
                variant="default"
                @click="lockDialogOpen = true"
                :disabled="lockForm.processing"
            >
                <Lock class="mr-2 size-4" />
                Kunci Undian
            </Button>
        </div>

        <Card v-if="matches.length === 0 && participants.length < 2" class="p-8 text-center text-muted-foreground">
            Minimal 2 peserta diperlukan untuk membuat undian pertandingan.
        </Card>

        <Card v-if="participants.length > 0">
            <CardHeader class="border-b py-3 bg-muted/30">
                <CardTitle class="text-sm font-semibold">Hasil Pengacakan Urutan Peserta</CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <div class="divide-y">
                    <div
                        v-for="(p, i) in participants"
                        :key="p.id"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm"
                    >
                        <span class="w-6 font-mono text-muted-foreground">{{ i + 1 }}</span>
                        <span class="font-medium text-foreground">{{ p.name }}</span>
                        <span v-if="p.short_name" class="text-xs text-muted-foreground">({{ p.short_name }})</span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div v-if="Object.keys(groupedMatches).length > 0" class="space-y-4">
            <h2 class="text-lg font-bold">Daftar Pertandingan (Preview)</h2>
            <template v-for="(roundMatches, roundKey) in groupedMatches" :key="roundKey">
                <Card>
                    <CardHeader class="border-b py-3 bg-muted/30">
                        <CardTitle class="text-sm font-semibold">{{ roundKey }}</CardTitle>
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
                                    Bye
                                </div>
                                <div v-else class="flex flex-1 items-center justify-between">
                                    <span class="font-medium text-foreground">{{ participantName(m, 'home') }}</span>
                                    <span class="mx-3 text-xs font-bold uppercase text-muted-foreground">VS</span>
                                    <span class="font-medium text-foreground">{{ participantName(m, 'away') }}</span>
                                </div>
                                <Badge v-if="m.status === 'bye'" variant="outline">Bye</Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </template>
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
                            <p><strong>Format:</strong> {{ formatLabel[competition.format] }}</p>
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
                    <Button variant="default" :disabled="lockForm.processing" @click="executeLock">
                        <Lock class="mr-2 size-4" />
                        {{ lockForm.processing ? 'Mengunci...' : 'Kunci Undian Sekarang' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

