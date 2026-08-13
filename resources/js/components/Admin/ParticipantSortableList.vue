<script setup lang="ts">
import {
    GripVertical,
    ArrowUp,
    ArrowDown,
    ChevronsUp,
    ChevronsDown,
    ArrowUpDown,
    RotateCcw,
    Sparkles,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { ParticipantItem } from '@/lib/drawPreviewGenerator';

const props = defineProps<{
    modelValue: ParticipantItem[];
    initialParticipants: ParticipantItem[];
    disabled?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', val: ParticipantItem[]): void;
}>();

const items = ref<ParticipantItem[]>([...props.modelValue]);
const draggedIndex = ref<number | null>(null);
const dragOverIndex = ref<number | null>(null);

watch(
    () => props.modelValue,
    (newVal) => {
        items.value = [...newVal];
    },
    { deep: true }
);

function updateList(newList: ParticipantItem[]) {
    items.value = newList;
    emit('update:modelValue', [...newList]);
}

function moveUp(index: number) {
    if (index <= 0 || props.disabled) {
return;
}

    const copy = [...items.value];
    const [moved] = copy.splice(index, 1);
    copy.splice(index - 1, 0, moved);
    updateList(copy);
}

function moveDown(index: number) {
    if (index >= items.value.length - 1 || props.disabled) {
return;
}

    const copy = [...items.value];
    const [moved] = copy.splice(index, 1);
    copy.splice(index + 1, 0, moved);
    updateList(copy);
}

function moveTop(index: number) {
    if (index <= 0 || props.disabled) {
return;
}

    const copy = [...items.value];
    const [moved] = copy.splice(index, 1);
    copy.unshift(moved);
    updateList(copy);
}

function moveBottom(index: number) {
    if (index >= items.value.length - 1 || props.disabled) {
return;
}

    const copy = [...items.value];
    const [moved] = copy.splice(index, 1);
    copy.push(moved);
    updateList(copy);
}

function sortAlphabetically() {
    if (props.disabled) {
return;
}

    const copy = [...items.value].sort((a, b) => a.name.localeCompare(b.name, 'id'));
    updateList(copy);
}

function resetOrder() {
    if (props.disabled) {
return;
}

    updateList([...props.initialParticipants]);
}

// Drag & Drop handlers
function onDragStart(index: number, e: DragEvent) {
    if (props.disabled) {
return;
}

    draggedIndex.value = index;

    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', index.toString());
    }
}

function onDragOver(index: number, e: DragEvent) {
    if (props.disabled || draggedIndex.value === null) {
return;
}

    e.preventDefault();
    dragOverIndex.value = index;
}

function onDrop(index: number, e: DragEvent) {
    if (props.disabled || draggedIndex.value === null) {
return;
}

    e.preventDefault();

    const fromIndex = draggedIndex.value;
    const toIndex = index;

    if (fromIndex !== toIndex) {
        const copy = [...items.value];
        const [moved] = copy.splice(fromIndex, 1);
        copy.splice(toIndex, 0, moved);
        updateList(copy);
    }

    draggedIndex.value = null;
    dragOverIndex.value = null;
}

function onDragEnd() {
    draggedIndex.value = null;
    dragOverIndex.value = null;
}
</script>

<template>
    <Card class="border shadow-sm">
        <CardHeader class="flex flex-row items-center justify-between border-b py-3 bg-muted/30">
            <div class="flex items-center gap-2">
                <CardTitle class="text-sm font-semibold flex items-center gap-1.5">
                    <Sparkles class="size-4 text-amber-500" />
                    <span>Urutan Peserta Undian (Draw Position)</span>
                </CardTitle>
                <Badge variant="outline" class="text-xs">
                    {{ items.length }} Peserta
                </Badge>
            </div>
            <div v-if="!disabled" class="flex items-center gap-1.5">
                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button variant="outline" size="sm" class="h-8 text-xs" @click="sortAlphabetically">
                                <ArrowUpDown class="mr-1 size-3.5" />
                                Urutkan A-Z
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Urutkan secara alfabetis</TooltipContent>
                    </Tooltip>
                </TooltipProvider>

                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button variant="ghost" size="sm" class="h-8 text-xs" @click="resetOrder">
                                <RotateCcw class="mr-1 size-3.5" />
                                Reset
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Kembalikan ke urutan awal</TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>
        </CardHeader>

        <CardContent class="p-2">
            <div class="space-y-1.5">
                <div
                    v-for="(p, index) in items"
                    :key="p.id"
                    :draggable="!disabled"
                    @dragstart="onDragStart(index, $event)"
                    @dragover="onDragOver(index, $event)"
                    @drop="onDrop(index, $event)"
                    @dragend="onDragEnd"
                    class="group relative flex items-center justify-between rounded-md border bg-card p-2.5 transition-all"
                    :class="[
                        disabled ? 'opacity-80' : 'cursor-grab active:cursor-grabbing hover:border-primary/50 hover:bg-accent/40',
                        draggedIndex === index ? 'opacity-40 border-dashed border-primary' : '',
                        dragOverIndex === index && draggedIndex !== index ? 'border-primary bg-primary/5 ring-1 ring-primary' : '',
                    ]"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <div
                            v-if="!disabled"
                            class="flex items-center justify-center text-muted-foreground/60 group-hover:text-foreground shrink-0"
                        >
                            <GripVertical class="size-4" />
                        </div>
                        <Badge
                            variant="secondary"
                            class="w-7 h-7 flex items-center justify-center p-0 font-mono text-xs shrink-0"
                            :class="{
                                'bg-amber-100 text-amber-900 border-amber-300 font-bold dark:bg-amber-950 dark:text-amber-200': index === 0,
                                'bg-slate-100 text-slate-900 border-slate-300 font-bold dark:bg-slate-800 dark:text-slate-200': index === 1,
                                'bg-amber-800/10 text-amber-800 border-amber-600/30 dark:bg-amber-900/30 dark:text-amber-400': index === 2,
                            }"
                        >
                            {{ index + 1 }}
                        </Badge>
                        <div class="truncate">
                            <span class="font-medium text-sm text-foreground">{{ p.name }}</span>
                            <span v-if="p.short_name" class="ml-1.5 text-xs text-muted-foreground">({{ p.short_name }})</span>
                        </div>
                    </div>

                    <div v-if="!disabled" class="flex items-center gap-1 shrink-0 opacity-80 group-hover:opacity-100 transition-opacity">
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-7"
                                        :disabled="index === 0"
                                        @click="moveTop(index)"
                                    >
                                        <ChevronsUp class="size-3.5" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>Ke Paling Atas</TooltipContent>
                            </Tooltip>
                        </TooltipProvider>

                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-7"
                                        :disabled="index === 0"
                                        @click="moveUp(index)"
                                    >
                                        <ArrowUp class="size-3.5" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>Pindah Up</TooltipContent>
                            </Tooltip>
                        </TooltipProvider>

                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-7"
                                        :disabled="index === items.length - 1"
                                        @click="moveDown(index)"
                                    >
                                        <ArrowDown class="size-3.5" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>Pindah Down</TooltipContent>
                            </Tooltip>
                        </TooltipProvider>

                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-7"
                                        :disabled="index === items.length - 1"
                                        @click="moveBottom(index)"
                                    >
                                        <ChevronsDown class="size-3.5" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>Ke Paling Bawah</TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
