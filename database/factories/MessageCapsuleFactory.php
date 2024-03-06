<?php

namespace Database\Factories;

use App\Models\MessageCapsule;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageCapsule>
 */
class MessageCapsuleFactory extends Factory
{
    protected $model = MessageCapsule::class;

    /**
     * A sealed capsule whose opening time is still in the future.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'note' => $this->faker->sentence(),
            'scheduled_opening_time' => now()->addDays($this->faker->numberBetween(1, 10)),
            'is_opened' => false,
        ];
    }

    /** Opening time has passed and the capsule has been read. */
    public function opened(): self
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_opening_time' => now()->subDays(5),
            'is_opened' => true,
        ]);
    }

    /** Opening time has passed but the capsule is still sealed. */
    public function unlocked(): self
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_opening_time' => now()->subDays(5),
            'is_opened' => false,
        ]);
    }

    /** Explicit opening time, for boundary tests. */
    public function openingAt(DateTimeInterface|string $when): self
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_opening_time' => $when,
        ]);
    }
}
