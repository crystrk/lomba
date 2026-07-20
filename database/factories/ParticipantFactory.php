<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    public function definition(): array
    {
        $name = fake()->unique()->city().' FC';

        return [
            'competition_id' => Competition::factory(),
            'name' => $name,
            'normalized_name' => Str::lower($name),
            'short_name' => fake()->optional()->lexify('???'),
            'logo_path' => null,
            'draw_position' => null,
        ];
    }

    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'draw_position' => $position,
        ]);
    }
}
