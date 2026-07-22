<?php

namespace Database\Seeders;

use App\Enums\CompetitionFormat;
use App\Enums\CompetitionMatchStatus;
use App\Enums\CompetitionStatus;
use App\Enums\UserRole;
use App\Generators\DrawGenerator;
use App\Models\Competition;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds for demo data.
     * Note: This seeder is intentioned for demo setup and should NOT be registered
     * in DatabaseSeeder so it is not run during `php artisan migrate:fresh --seed`.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            // 1. Ensure Admin User exists for assigner / locked_by references
            $admin = User::firstOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'name' => 'Admin',
                    'password' => Hash::make('password'),
                    'role' => UserRole::Admin,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // 2. Create requested Demo Wasit user
            $wasit = User::firstOrCreate(
                ['email' => 'demo.wasit@cryst.web.id'],
                [
                    'name' => 'Wasit Demo',
                    'password' => Hash::make('password'),
                    'role' => UserRole::Operator,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Clean up existing demo competitions if re-running seeder
            Competition::whereIn('slug', [
                'liga-mini-soccer-indonesia-2026',
                'kejuaraan-voli-putra-open-2026',
                'turnamen-tenis-lapangan-master-2026',
                'kejuaraan-tenis-meja-tunggal-2026',
                'turnamen-catur-cepat-rapid-chess-2026',
            ])->delete();

            // Seed 5 Competitions
            $this->seedMiniSoccer($admin, $wasit);
            $this->seedVolleyball($admin, $wasit);
            $this->seedTenisLapangan($admin, $wasit);
            $this->seedTenisMeja($admin, $wasit);
            $this->seedCatur($admin, $wasit);
        });
    }

    private function seedMiniSoccer(User $admin, User $wasit): void
    {
        $competition = Competition::create([
            'name' => 'Liga Mini Soccer Indonesia 2026',
            'slug' => 'liga-mini-soccer-indonesia-2026',
            'description' => 'Kompetisi Mini Soccer 7v7 format setengah kompetisi (Round-Robin).',
            'format' => CompetitionFormat::HalfCompetition,
            'status' => CompetitionStatus::Draft,
            'win_points' => 3,
            'draw_points' => 1,
            'loss_points' => 0,
            'draw_version' => 0,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addDays(14),
        ]);

        $teamNames = [
            ['name' => 'FC Garuda Jakarta', 'short_name' => 'GAR'],
            ['name' => 'Elang United Bandung', 'short_name' => 'ELG'],
            ['name' => 'Harimau FC Surabaya', 'short_name' => 'HRM'],
            ['name' => 'Badak Hitam FC Medan', 'short_name' => 'BDK'],
            ['name' => 'Singa Utama Bali', 'short_name' => 'SNG'],
            ['name' => 'Rajawali FC Semarang', 'short_name' => 'RJW'],
        ];

        $participants = [];
        foreach ($teamNames as $team) {
            $participants[] = Participant::create([
                'competition_id' => $competition->id,
                'name' => $team['name'],
                'short_name' => $team['short_name'],
            ]);
        }

        $matches = $this->generateDrawAndMatches($competition, $participants);

        $competition->update([
            'status' => CompetitionStatus::InProgress,
            'draw_version' => 1,
            'locked_by' => $admin->id,
            'locked_at' => now()->subDays(2),
        ]);

        $competition->operators()->attach($wasit->id, [
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        // Complete Round 1 matches with demo scores
        $round1Matches = collect($matches)->where('round', 1)->values();
        if ($round1Matches->count() >= 3) {
            $this->recordMatchScore($round1Matches[0], 2, 1, $wasit);
            $this->recordMatchScore($round1Matches[1], 0, 0, $wasit);
            $this->recordMatchScore($round1Matches[2], 3, 1, $wasit);
        }
    }

    private function seedVolleyball(User $admin, User $wasit): void
    {
        $competition = Competition::create([
            'name' => 'Kejuaraan Voli Putra Open 2026',
            'slug' => 'kejuaraan-voli-putra-open-2026',
            'description' => 'Kejuaraan Bola Voli Putra format sistem gugur (Knockout).',
            'format' => CompetitionFormat::Knockout,
            'status' => CompetitionStatus::Draft,
            'win_points' => null,
            'draw_points' => null,
            'loss_points' => null,
            'draw_version' => 0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(7),
        ]);

        $teamNames = [
            ['name' => 'VBC Petrokimia', 'short_name' => 'PET'],
            ['name' => 'Voli Smash Club', 'short_name' => 'SMS'],
            ['name' => 'Halilintar VBC', 'short_name' => 'HLN'],
            ['name' => 'Gelora Volley', 'short_name' => 'GLR'],
            ['name' => 'Samudra VBC', 'short_name' => 'SMD'],
            ['name' => 'Bhayangkara VC', 'short_name' => 'BHY'],
            ['name' => 'Perkasa VBC', 'short_name' => 'PKS'],
            ['name' => 'Eagle Volley Team', 'short_name' => 'EGL'],
        ];

        $participants = [];
        foreach ($teamNames as $team) {
            $participants[] = Participant::create([
                'competition_id' => $competition->id,
                'name' => $team['name'],
                'short_name' => $team['short_name'],
            ]);
        }

        $matches = $this->generateDrawAndMatches($competition, $participants);

        $competition->update([
            'status' => CompetitionStatus::InProgress,
            'draw_version' => 1,
            'locked_by' => $admin->id,
            'locked_at' => now()->subDay(),
        ]);

        $competition->operators()->attach($wasit->id, [
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        // Complete Round 1 (Quarter Finals) & advance winners to Round 2 (Semi Finals)
        $qfMatches = collect($matches)->where('round', 1)->values();
        $scores = [[3, 1], [3, 0], [3, 2], [3, 1]];
        foreach ($qfMatches as $idx => $match) {
            if (isset($scores[$idx])) {
                $this->recordKnockoutScore($match, $scores[$idx][0], $scores[$idx][1], $wasit);
            }
        }
    }

    private function seedTenisLapangan(User $admin, User $wasit): void
    {
        $competition = Competition::create([
            'name' => 'Turnamen Tenis Lapangan Master 2026',
            'slug' => 'turnamen-tenis-lapangan-master-2026',
            'description' => 'Turnamen Tenis Lapangan Tunggal Putra format kompetisi penuh (Double Round-Robin).',
            'format' => CompetitionFormat::FullCompetition,
            'status' => CompetitionStatus::Draft,
            'win_points' => 3,
            'draw_points' => 0,
            'loss_points' => 0,
            'draw_version' => 0,
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(20),
        ]);

        $playerNames = [
            ['name' => 'Riki Wijaya', 'short_name' => 'RWJ'],
            ['name' => 'Budi Santoso', 'short_name' => 'BST'],
            ['name' => 'Andi Pratama', 'short_name' => 'APR'],
            ['name' => 'Doni Setiawan', 'short_name' => 'DST'],
        ];

        $participants = [];
        foreach ($playerNames as $player) {
            $participants[] = Participant::create([
                'competition_id' => $competition->id,
                'name' => $player['name'],
                'short_name' => $player['short_name'],
            ]);
        }

        $this->generateDrawAndMatches($competition, $participants);

        $competition->update([
            'status' => CompetitionStatus::InProgress,
            'draw_version' => 1,
            'locked_by' => $admin->id,
            'locked_at' => now(),
        ]);

        $competition->operators()->attach($wasit->id, [
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);
    }

    private function seedTenisMeja(User $admin, User $wasit): void
    {
        $competition = Competition::create([
            'name' => 'Kejuaraan Tenis Meja Tunggal 2026',
            'slug' => 'kejuaraan-tenis-meja-tunggal-2026',
            'description' => 'Kejuaraan Tenis Meja Tunggal Putra sistem gugur.',
            'format' => CompetitionFormat::Knockout,
            'status' => CompetitionStatus::Draft,
            'win_points' => null,
            'draw_points' => null,
            'loss_points' => null,
            'draw_version' => 0,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDay(),
        ]);

        $playerNames = [
            ['name' => 'Agus Supriyadi', 'short_name' => 'AGS'],
            ['name' => 'Bambang Hidayat', 'short_name' => 'BBG'],
            ['name' => 'Candra Wijaya', 'short_name' => 'CND'],
            ['name' => 'Dedi Kurnia', 'short_name' => 'DED'],
        ];

        $participants = [];
        foreach ($playerNames as $player) {
            $participants[] = Participant::create([
                'competition_id' => $competition->id,
                'name' => $player['name'],
                'short_name' => $player['short_name'],
            ]);
        }

        $matches = $this->generateDrawAndMatches($competition, $participants);

        $competition->update([
            'status' => CompetitionStatus::Locked,
            'draw_version' => 1,
            'locked_by' => $admin->id,
            'locked_at' => now()->subDays(5),
        ]);

        $competition->operators()->attach($wasit->id, [
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        // Complete Semi Finals (Round 1)
        $sfMatches = collect($matches)->where('round', 1)->values();
        if ($sfMatches->count() >= 2) {
            $this->recordKnockoutScore($sfMatches[0], 3, 1, $wasit);
            $this->recordKnockoutScore($sfMatches[1], 3, 2, $wasit);
        }

        // Complete Final (Round 2)
        $finalMatch = $competition->matches()->where('round', 2)->first();
        if ($finalMatch && $finalMatch->hasBothParticipants()) {
            $this->recordKnockoutScore($finalMatch, 3, 0, $wasit);
        }

        $competition->update([
            'status' => CompetitionStatus::Completed,
        ]);
    }

    private function seedCatur(User $admin, User $wasit): void
    {
        $competition = Competition::create([
            'name' => 'Turnamen Catur Cepat (Rapid Chess) 2026',
            'slug' => 'turnamen-catur-cepat-rapid-chess-2026',
            'description' => 'Turnamen Catur Cepat 15 menit format setengah kompetisi (Draft).',
            'format' => CompetitionFormat::HalfCompetition,
            'status' => CompetitionStatus::Draft,
            'win_points' => 3,
            'draw_points' => 1,
            'loss_points' => 0,
            'draw_version' => 0,
            'starts_at' => now()->addDays(7),
            'ends_at' => now()->addDays(10),
        ]);

        $playerNames = [
            ['name' => 'GM Ahmad Fauzi', 'short_name' => 'AHM'],
            ['name' => 'MI Budi Hartono', 'short_name' => 'BDH'],
            ['name' => 'MF Cakra Buana', 'short_name' => 'CKR'],
            ['name' => 'Darmawan S.', 'short_name' => 'DRM'],
            ['name' => 'Eka Putra', 'short_name' => 'EKA'],
        ];

        foreach ($playerNames as $player) {
            Participant::create([
                'competition_id' => $competition->id,
                'name' => $player['name'],
                'short_name' => $player['short_name'],
            ]);
        }

        $competition->operators()->attach($wasit->id, [
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);
    }

    /**
     * @param  array<Participant>  $participants
     * @return array<int, mixed>
     */
    private function generateDrawAndMatches(Competition $competition, array $participants): array
    {
        $participantIds = collect($participants)->pluck('id')->toArray();

        foreach ($participantIds as $index => $id) {
            Participant::where('id', $id)->update(['draw_position' => $index + 1]);
        }

        $result = DrawGenerator::generate($competition->format, $participantIds);
        $createdMatches = collect();

        foreach ($result->slots as $slot) {
            $createdMatches->push($competition->matches()->create([
                'round' => $slot->round,
                'leg' => $slot->leg,
                'sequence' => $slot->sequence,
                'participant_id_home' => $slot->homeId,
                'participant_id_away' => $slot->awayId,
                'status' => $slot->status,
                'next_match_id' => null,
                'next_slot' => $slot->nextSlot,
            ]));
        }

        foreach ($result->slots as $i => $slot) {
            if ($slot->nextMatchId === null) {
                continue;
            }

            $target = $createdMatches->firstWhere('sequence', $slot->nextMatchId);

            if ($target !== null) {
                $createdMatches[$i]->update(['next_match_id' => $target->id]);
            }
        }

        return $createdMatches->all();
    }

    private function recordMatchScore($match, int $scoreHome, int $scoreAway, User $wasit): void
    {
        $winnerId = $scoreHome > $scoreAway
            ? $match->participant_id_home
            : ($scoreHome < $scoreAway ? $match->participant_id_away : null);

        $match->update([
            'score_home' => $scoreHome,
            'score_away' => $scoreAway,
            'winner_id' => $winnerId,
            'status' => CompetitionMatchStatus::Completed,
            'result_version' => $match->result_version + 1,
            'result_updated_by' => $wasit->id,
            'result_updated_at' => now(),
        ]);
    }

    private function recordKnockoutScore($match, int $scoreHome, int $scoreAway, User $wasit): void
    {
        // Refresh match to get updated participants if advanced from prior matches
        $match->refresh();

        if (! $match->hasBothParticipants()) {
            return;
        }

        $winnerId = $scoreHome > $scoreAway
            ? $match->participant_id_home
            : $match->participant_id_away;

        $match->update([
            'score_home' => $scoreHome,
            'score_away' => $scoreAway,
            'winner_id' => $winnerId,
            'status' => CompetitionMatchStatus::Completed,
            'result_version' => $match->result_version + 1,
            'result_updated_by' => $wasit->id,
            'result_updated_at' => now(),
        ]);

        // Advance winner to next match if applicable
        if ($match->next_match_id !== null && $match->next_slot !== null) {
            $nextMatch = $match->nextMatch;
            if ($nextMatch && ! $nextMatch->isBye()) {
                if ($match->next_slot === 1) {
                    $nextMatch->update(['participant_id_home' => $winnerId]);
                } else {
                    $nextMatch->update(['participant_id_away' => $winnerId]);
                }

                $nextMatch->refresh();
                if ($nextMatch->hasBothParticipants() && $nextMatch->isPending()) {
                    $nextMatch->update(['status' => CompetitionMatchStatus::Ready]);
                }
            }
        }
    }
}
