export interface ParticipantItem {
    id: number;
    name: string;
    short_name: string | null;
    draw_position?: number | null;
}

export interface ClientMatchSlot {
    id: number;
    round: number;
    leg: number;
    sequence: number;
    home: ParticipantItem | null;
    away: ParticipantItem | null;
    status: 'ready' | 'pending' | 'bye';
    next_match_id: number | null;
    next_slot: number | null;
}

function nextPowerOfTwo(n: number): number {
    if (n < 2) return 2;
    let power = 1;
    while (power < n) {
        power <<= 1;
    }
    return power;
}

export function generateClientPreview(
    format: string,
    participants: ParticipantItem[]
): ClientMatchSlot[] {
    const n = participants.length;
    if (n < 2) return [];

    if (format === 'half_competition') {
        return generateHalfCompetition(participants);
    } else if (format === 'full_competition') {
        return generateFullCompetition(participants);
    } else if (format === 'knockout') {
        return generateKnockout(participants);
    }

    return [];
}

function generateHalfCompetition(participants: ParticipantItem[]): ClientMatchSlot[] {
    const wheel: (ParticipantItem | null)[] = [...participants];
    const isOdd = wheel.length % 2 !== 0;

    if (isOdd) {
        wheel.push(null);
    }

    const rounds = wheel.length - 1;
    const slots: ClientMatchSlot[] = [];
    let sequence = 0;

    for (let round = 1; round <= rounds; round++) {
        const pairsInRound = Math.floor(wheel.length / 2);

        for (let p = 0; p < pairsInRound; p++) {
            const home = wheel[p];
            const away = wheel[wheel.length - 1 - p];

            if (home === null || away === null) {
                continue;
            }

            sequence++;
            slots.push({
                id: -sequence,
                round,
                leg: 1,
                sequence,
                home,
                away,
                status: 'ready',
                next_match_id: null,
                next_slot: null,
            });
        }

        const last = wheel.pop()!;
        wheel.splice(1, 0, last);
    }

    return slots;
}

function generateFullCompetition(participants: ParticipantItem[]): ClientMatchSlot[] {
    const leg1 = generateHalfCompetition(participants);
    let maxRound = 0;
    for (const slot of leg1) {
        maxRound = Math.max(maxRound, slot.round);
    }

    const leg2: ClientMatchSlot[] = leg1.map((slot) => ({
        id: -(slot.sequence + leg1.length),
        round: slot.round + maxRound,
        leg: 2,
        sequence: slot.sequence + leg1.length,
        home: slot.away,
        away: slot.home,
        status: slot.status,
        next_match_id: null,
        next_slot: null,
    }));

    return [...leg1, ...leg2];
}

function generateKnockout(participants: ParticipantItem[]): ClientMatchSlot[] {
    const n = participants.length;
    const bracketSize = nextPowerOfTwo(n);
    const totalRounds = Math.round(Math.log2(bracketSize));

    const m1 = 2 * n - bracketSize;
    const readyMatchesR1 = Math.floor(m1 / 2);

    interface MatchNode {
        home: ParticipantItem | null;
        away: ParticipantItem | null;
        status: 'ready' | 'pending' | 'bye';
    }

    const rounds: MatchNode[][] = [];
    const prevAdvValues: (ParticipantItem | null)[] = [];

    const r1Matches: MatchNode[] = [];
    for (let k = 0; k < bracketSize / 2; k++) {
        if (k < readyMatchesR1) {
            const home = participants[k * 2];
            const away = participants[k * 2 + 1];
            r1Matches.push({ home, away, status: 'ready' });
            prevAdvValues.push(null);
        } else {
            const pIndex = m1 + (k - readyMatchesR1);
            const home = pIndex < n ? participants[pIndex] : null;
            r1Matches.push({ home, away: null, status: 'bye' });
            prevAdvValues.push(home);
        }
    }
    rounds.push(r1Matches);

    let currentAdvValues = prevAdvValues;

    for (let round = 1; round < totalRounds; round++) {
        const prevMatches = rounds[round - 1];
        const nextAdvValues: (ParticipantItem | null)[] = [];
        const currentRoundMatches: MatchNode[] = [];

        for (let k = 0; k < prevMatches.length / 2; k++) {
            const parentA = prevMatches[k * 2];
            const parentB = prevMatches[k * 2 + 1];

            const parentAAdv = currentAdvValues[k * 2];
            const parentBAdv = currentAdvValues[k * 2 + 1];

            const home = parentA.status === 'bye' ? parentAAdv : null;
            const away = parentB.status === 'bye' ? parentBAdv : null;

            const bothParentsBye = parentA.status === 'bye' && parentB.status === 'bye';

            if (bothParentsBye) {
                if (home !== null && away !== null) {
                    currentRoundMatches.push({ home, away, status: 'ready' });
                    nextAdvValues.push(null);
                } else if (home !== null) {
                    currentRoundMatches.push({ home, away: null, status: 'bye' });
                    nextAdvValues.push(home);
                } else if (away !== null) {
                    currentRoundMatches.push({ home: away, away: null, status: 'bye' });
                    nextAdvValues.push(away);
                } else {
                    currentRoundMatches.push({ home: null, away: null, status: 'bye' });
                    nextAdvValues.push(null);
                }
            } else {
                const status = home !== null && away !== null ? 'ready' : 'pending';
                currentRoundMatches.push({ home, away, status });
                nextAdvValues.push(null);
            }
        }

        rounds.push(currentRoundMatches);
        currentAdvValues = nextAdvValues;
    }

    const idMap: number[][] = [];
    let seq = 0;

    for (let r = 0; r < rounds.length; r++) {
        idMap[r] = [];
        for (let pos = 0; pos < rounds[r].length; pos++) {
            seq++;
            idMap[r][pos] = seq;
        }
    }

    const slots: ClientMatchSlot[] = [];

    for (let r = 0; r < rounds.length; r++) {
        const roundNumber = r + 1;
        for (let pos = 0; pos < rounds[r].length; pos++) {
            const match = rounds[r][pos];
            const sequence = idMap[r][pos];

            let nextMatchId: number | null = null;
            let nextSlot: number | null = null;

            if (roundNumber < totalRounds) {
                const nextPos = Math.floor(pos / 2);
                nextMatchId = idMap[r + 1]?.[nextPos] ?? null;
                nextSlot = pos % 2 === 0 ? 1 : 2;
            }

            slots.push({
                id: -sequence,
                round: roundNumber,
                leg: 1,
                sequence,
                home: match.home,
                away: match.away,
                status: match.status,
                next_match_id: nextMatchId,
                next_slot: nextSlot,
            });
        }
    }

    return slots;
}
