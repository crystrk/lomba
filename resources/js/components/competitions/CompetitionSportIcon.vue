<script setup lang="ts">
import { computed } from 'vue';
import {
    competitionSports,
    trophyIcon,
    type CompetitionSport,
} from '@/components/competitions/competitionSport';

const props = withDefaults(
    defineProps<{
        sport: string | null;
    }>(),
    {
        sport: null,
    },
);

const sportDefinition = computed(
    () => competitionSports[props.sport as CompetitionSport],
);
</script>

<template>
    <component :is="sportDefinition?.icon ?? trophyIcon" aria-hidden="true" />
</template>
