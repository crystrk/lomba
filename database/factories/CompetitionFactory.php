<?php

namespace Database\Factories;

use App\Enums\CompetitionFormat;
use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Competition>
 */
class CompetitionFactory extends Factory
{
    protected $model = Competition::class;

    public function definition(): array
    {
        $name = 'Lomba '.fake()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name.'-'.Str::random(4)),
            'description' => fake()->optional()->sentence(),
            'format' => CompetitionFormat::HalfCompetition,
            'status' => CompetitionStatus::Draft,
            'win_points' => 3,
            'draw_points' => 1,
            'loss_points' => 0,
            'draw_version' => 0,
            'starts_at' => fake()->optional()->dateTimeBetween('+1 day', '+1 month'),
            'ends_at' => fake()->optional()->dateTimeBetween('+1 month', '+2 months'),
            'is_results_locked' => false,
            'results_locked_by' => null,
            'results_locked_at' => null,
        ];
    }

    public function knockout(): static
    {
        return $this->state(fn (array $attributes) => [
            'format' => CompetitionFormat::Knockout,
            'win_points' => null,
            'draw_points' => null,
            'loss_points' => null,
        ]);
    }

    public function fullCompetition(): static
    {
        return $this->state(fn (array $attributes) => [
            'format' => CompetitionFormat::FullCompetition,
        ]);
    }

    public function halfCompetition(): static
    {
        return $this->state(fn (array $attributes) => [
            'format' => CompetitionFormat::HalfCompetition,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CompetitionStatus::Draft,
        ]);
    }

    public function drawn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CompetitionStatus::Drawn,
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CompetitionStatus::Locked,
            'locked_at' => now(),
            'locked_by' => User::factory()->admin(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->locked()->state(fn (array $attributes) => [
            'status' => CompetitionStatus::InProgress,
        ]);
    }

    public function completed(): static
    {
        return $this->locked()->state(fn (array $attributes) => [
            'status' => CompetitionStatus::Completed,
        ]);
    }

    public function resultsLocked(): static
    {
        return $this->completed()->state(fn (array $attributes) => [
            'is_results_locked' => true,
            'results_locked_at' => now(),
            'results_locked_by' => User::factory()->admin(),
        ]);
    }
}
