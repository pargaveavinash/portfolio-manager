<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Portfolio;
use App\Models\PortfolioSnapshot;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PortfolioSnapshot>
 */
class PortfolioSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'portfolio_id' => Portfolio::factory(),
            'valuation_date' => $this->faker->date(),
            'invested_capital' => $this->faker->randomFloat(6, 0, 100000),
            'market_value' => $this->faker->randomFloat(6, 0, 100000),
            'cash_balance' => $this->faker->randomFloat(6, 0, 10000),
            'total_value' => $this->faker->randomFloat(6, 0, 110000),
        ];
    }
}
