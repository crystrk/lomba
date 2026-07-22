<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Users, Calendar, Shield, Layers } from '@lucide/vue';

defineProps<{
    competition: {
        id: number;
        name: string;
        slug: string;
        format: string;
        status: string;
        win_points: number | null;
        draw_points: number | null;
        loss_points: number | null;
    };
    participantsCount: number;
    completedMatchesCount: number;
    totalScorableMatchesCount: number;
    matchProgressPercentage: number;
    formatLabel: Record<string, string>;
    statusLabel: Record<string, string>;
}>();
</script>

<template>
    <div class="relative overflow-hidden rounded-2xl border border-border/80 bg-card p-5 sm:p-8 shadow-xs">
        <!-- Top Glow Accent -->
        <div class="absolute -top-24 -right-24 size-64 rounded-full bg-amber-500/10 blur-3xl pointer-events-none" />

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-3 max-w-2xl">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge 
                        :variant="competition.status === 'in_progress' ? 'default' : 'secondary'"
                        class="text-xs font-semibold px-2.5 py-0.5"
                        :class="competition.status === 'in_progress' ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : ''"
                    >
                        <span v-if="competition.status === 'in_progress'" class="size-1.5 rounded-full bg-white animate-pulse mr-1.5 inline-block" />
                        {{ statusLabel[competition.status] || competition.status }}
                    </Badge>

                    <Badge variant="outline" class="text-xs font-medium border-amber-500/40 text-amber-600 dark:text-amber-400 bg-amber-500/10">
                        <Layers class="size-3 mr-1" />
                        {{ formatLabel[competition.format] || competition.format }}
                    </Badge>
                </div>

                <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-foreground">
                    {{ competition.name }}
                </h1>

                <div class="flex flex-wrap items-center gap-4 text-xs sm:text-sm text-muted-foreground pt-1">
                    <span class="flex items-center gap-1.5 font-medium text-foreground">
                        <Users class="size-4 text-indigo-500" />
                        {{ participantsCount }} Peserta Terdaftar
                    </span>

                    <span class="flex items-center gap-1.5 font-medium text-foreground">
                        <Calendar class="size-4 text-emerald-500" />
                        {{ completedMatchesCount }} / {{ totalScorableMatchesCount }} Match Selesai
                    </span>
                </div>

                <!-- Point Rules if League -->
                <div v-if="competition.win_points !== null" class="text-xs text-muted-foreground bg-muted/60 px-3 py-1.5 rounded-lg border border-border/50 inline-flex items-center gap-2">
                    <Shield class="size-3.5 text-amber-500 shrink-0" />
                    <span>Sistem Poin: Menang <strong>{{ competition.win_points }}</strong> &bull; Seri <strong>{{ competition.draw_points }}</strong> &bull; Kalah <strong>{{ competition.loss_points }}</strong></span>
                </div>
            </div>

            <!-- Progress Ring / Bar Summary -->
            <div v-if="totalScorableMatchesCount > 0" class="w-full md:w-64 p-4 rounded-xl bg-muted/40 border border-border/60 space-y-2">
                <div class="flex items-center justify-between text-xs font-semibold">
                    <span class="text-muted-foreground">Progress Lomba</span>
                    <span class="text-foreground font-bold">{{ matchProgressPercentage }}%</span>
                </div>
                <div class="h-2.5 w-full rounded-full bg-muted overflow-hidden">
                    <div 
                        class="h-full rounded-full transition-all duration-500"
                        :class="completedMatchesCount === totalScorableMatchesCount ? 'bg-emerald-500' : 'bg-amber-500'"
                        :style="{ width: `${matchProgressPercentage}%` }"
                    />
                </div>
                <p class="text-[11px] text-muted-foreground text-right italic">
                    {{ completedMatchesCount === totalScorableMatchesCount ? 'Seluruh pertandingan telah usai 🎉' : 'Pertandingan berlangsung secara berkala' }}
                </p>
            </div>
        </div>
    </div>
</template>
