<?php

namespace Database\Factories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\TicketType;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph(3),
            'opened_at' => now(),
            'closed_at' => null,
            'type' => $this->faker->randomElement(TicketType::values()),
            'created_by' => User::factory(),
            'assigned_user_id' => null,
        ];
    }
}
