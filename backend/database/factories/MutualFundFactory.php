<?php

namespace Database\Factories;

use App\Models\MutualFund;
use Illuminate\Database\Eloquent\Factories\Factory;

class MutualFundFactory extends Factory
{
    protected $model = MutualFund::class;

    public function definition(): array
    {
        return [
            'amfi_code' => $this->faker->unique()->numerify('######'),
            'isin' => $this->faker->unique()->lexify('INF??????????'),
            'amc_name' => $this->faker->company(),
            'scheme_name' => $this->faker->words(3, true),
            'plan_type' => $this->faker->randomElement(['DIRECT', 'REGULAR']),
            'option_type' => $this->faker->randomElement(['GROWTH', 'IDCW']),
            'category' => 'Equity',
        ];
    }
}
