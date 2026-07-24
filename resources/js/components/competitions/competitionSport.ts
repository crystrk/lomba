import type { Component } from 'vue';
import BadmintonIcon from '@/components/competitions/sport-icons/BadmintonIcon.vue';
import ChessIcon from '@/components/competitions/sport-icons/ChessIcon.vue';
import FootballIcon from '@/components/competitions/sport-icons/FootballIcon.vue';
import TableTennisIcon from '@/components/competitions/sport-icons/TableTennisIcon.vue';
import TennisIcon from '@/components/competitions/sport-icons/TennisIcon.vue';
import TrophyIcon from '@/components/competitions/sport-icons/TrophyIcon.vue';
import VolleyballIcon from '@/components/competitions/sport-icons/VolleyballIcon.vue';

export type CompetitionSport =
    | 'football'
    | 'badminton'
    | 'tennis'
    | 'table_tennis'
    | 'chess'
    | 'volleyball'
    | 'general';

interface SportDefinition {
    label: string;
    icon: Component;
}

export const trophyIcon = TrophyIcon;

export const competitionSports: Record<CompetitionSport, SportDefinition> = {
    football: { label: 'Sepak Bola', icon: FootballIcon },
    badminton: { label: 'Badminton', icon: BadmintonIcon },
    tennis: { label: 'Tenis Lapangan', icon: TennisIcon },
    table_tennis: { label: 'Tenis Meja', icon: TableTennisIcon },
    chess: { label: 'Catur', icon: ChessIcon },
    volleyball: { label: 'Bola Voli', icon: VolleyballIcon },
    general: { label: 'Lomba Umum', icon: TrophyIcon },
};
