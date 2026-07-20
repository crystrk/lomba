<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
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
    drawn: 'default',
    locked: 'default',
    in_progress: 'default',
    completed: 'default',
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

const shuffleForm = useForm({});

function handleShuffle() {
    if (!confirm('Mengacak ulang akan mengganti susunan pertandingan yang ada. Lanjutkan?')) return;
    shuffleForm.post(shuffle(props.competition.id).url);
}

const lockForm = useForm({
    draw_version: props.competition.draw_version,
});

function handleLock() {
    if (!confirm('Kunci undian? Setelah dikunci, susunan pertandingan tidak dapat diubah lagi.')) return;
    lockForm.post(lock(props.competition.id).url);
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
        <div class="flex items-center justify-between">
            <div>
                <Link
                    :href="`/admin/competitions/${competition.id}`"
                    class="text-sm text-muted-foreground hover:underline"
                    >{{ competition.name }}</Link
                >
                <h1 class="text-2xl font-bold">Undian</h1>
            </div>
            <div class="flex items-center gap-3">
                <Badge :variant="statusVariant[competition.status] || 'secondary'">
                    {{ statusLabel[competition.status] || competition.status }}
                </Badge>
                <span class="text-sm text-muted-foreground">
                    v{{ competition.draw_version }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-lg border p-4">
                <span class="text-sm text-muted-foreground">Format</span>
                <p class="text-lg font-semibold">
                    {{ formatLabel[competition.format] }}
                </p>
            </div>
            <div class="rounded-lg border p-4">
                <span class="text-sm text-muted-foreground">Peserta</span>
                <p class="text-lg font-semibold">{{ participants.length }}</p>
            </div>
            <div class="rounded-lg border p-4">
                <span class="text-sm text-muted-foreground"
                    >Pertandingan (Skor)</span
                >
                <p class="text-lg font-semibold">
                    {{ matches.length }}
                    <span class="text-sm font-normal text-muted-foreground"
                        >({{ scoredMatchCount }} diitung skor)</span
                    >
                </p>
            </div>
        </div>

        <div v-if="byeMatchCount > 0" class="rounded-lg border bg-muted/50 p-3 text-sm text-muted-foreground">
            {{ byeMatchCount }} pertandingan <em>bye</em> (peserta lolos otomatis).
        </div>

        <div class="flex gap-3">
            <Button v-if="canDraw" @click="handleShuffle" :disabled="shuffleForm.processing">
                {{ matches.length > 0 ? 'Acak Ulang' : 'Acak & Buat Undian' }}
            </Button>
            <Button v-if="canLock" variant="default" @click="handleLock" :disabled="lockForm.processing">
                Kunci Undian
            </Button>
        </div>

        <div v-if="matches.length === 0 && participants.length < 2" class="rounded-lg border bg-muted/50 p-6 text-center text-muted-foreground">
            Minimal 2 peserta diperlukan untuk membuat undian.
        </div>

        <div v-if="participants.length > 0" class="rounded-lg border">
            <div class="border-b bg-muted/50 px-4 py-2 font-medium">
                Urutan Peserta
            </div>
            <div class="divide-y">
                <div
                    v-for="(p, i) in participants"
                    :key="p.id"
                    class="flex items-center gap-3 px-4 py-2 text-sm"
                >
                    <span class="w-6 text-muted-foreground">{{ i + 1 }}</span>
                    <span>{{ p.short_name || p.name }}</span>
                </div>
            </div>
        </div>

        <div v-if="Object.keys(groupedMatches).length > 0">
            <template v-for="(roundMatches, roundKey) in groupedMatches" :key="roundKey">
                <div class="mb-4 rounded-lg border">
                    <div class="border-b bg-muted/50 px-4 py-2 font-medium">
                        {{ roundKey }}
                    </div>
                    <div class="divide-y">
                        <div
                            v-for="m in roundMatches"
                            :key="m.id"
                            class="flex items-center gap-4 px-4 py-3 text-sm"
                        >
                            <span class="w-8 text-muted-foreground">#{{ m.sequence }}</span>
                            <div v-if="m.status === 'bye'" class="flex-1 italic text-muted-foreground">
                                Bye
                            </div>
                            <div v-else class="flex flex-1 items-center justify-between">
                                <span class="font-medium">{{ participantName(m, 'home') }}</span>
                                <span class="mx-2 text-muted-foreground">vs</span>
                                <span class="font-medium">{{ participantName(m, 'away') }}</span>
                            </div>
                            <Badge v-if="m.status === 'bye'" variant="outline">Bye</Badge>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
