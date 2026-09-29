<?php

namespace Database\Factories;

use App\Models\Portfolio;
use App\Models\SipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SipPlan>
 */
class SipPlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SipPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'portfolio_id' => Portfolio::factory(),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'strategy' => 'optimization',
            'frequency' => fake()->randomElement(['daily', 'weekly', 'monthly', 'quarterly']),
            'status' => 'active',
            'start_date' => fake()->date(),
            'next_scheduled_date' => fake()->date(),
            'end_date' => null,
            'timezone' => 'UTC',
        ];
    }
}
