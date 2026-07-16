<?php

namespace Database\Factories;

use App\Models\Status;
use App\Models\StatusAssignmentRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatusAssignmentRule>
 */
class StatusAssignmentRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status_id' => Status::factory(),
            'strategy' => StatusAssignmentRule::STRATEGY_CREATOR,
        ];
    }

    public function role(string $role): static
    {
        return $this->state([
            'strategy' => StatusAssignmentRule::STRATEGY_ROLE,
            'role' => $role,
        ]);
    }
}
