<?php

namespace Tests\Unit;

use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HoldingPositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_quantity_is_calculated_from_transactions(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->createMany([
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'BUY',
                'quantity'         => 10,
                'price'            => 1450,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 10:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'BUY',
                'quantity'         => 5,
                'price'            => 1500,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'SELL',
                'quantity'         => 3,
                'price'            => 1600,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 12:00:00',
            ],
        ]);

        $this->assertSame(
            12.0,
            $holding->currentQuantity()
        );
    }

    public function test_soft_deleted_transactions_are_not_included_in_current_quantity(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->createMany([
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'BUY',
                'quantity'         => 10,
                'price'            => 1450,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 10:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'SELL',
                'quantity'         => 3,
                'price'            => 1600,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
        ]);

        $holding->transactions()
            ->where('type', 'SELL')
            ->first()
            ->delete();

        $this->assertSame(
            10.0,
            $holding->currentQuantity()
        );
    }

    public function test_current_quantity_can_be_zero_after_selling_entire_position(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->createMany([
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'BUY',
                'quantity'         => 10,
                'price'            => 1450,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 10:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'SELL',
                'quantity'         => 10,
                'price'            => 1600,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
        ]);

        $this->assertSame(
            0.0,
            $holding->currentQuantity()
        );
    }

    public function test_current_quantity_can_be_negative_when_transactions_are_invalid(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->createMany([
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'BUY',
                'quantity'         => 10,
                'price'            => 1450,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 10:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'SELL',
                'quantity'         => 15,
                'price'            => 1600,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
        ]);

        $this->assertSame(
            -5.0,
            $holding->currentQuantity()
        );
    }

    public function test_current_quantity_is_reduced_after_partial_sell(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->createMany([
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'BUY',
                'quantity'         => 10,
                'price'            => 1450,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 10:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'SELL',
                'quantity'         => 4,
                'price'            => 1600,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
        ]);

        $this->assertSame(
            6.0,
            $holding->currentQuantity()
        );
    }

    public function test_current_average_price_is_calculated_from_buy_transactions(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->createMany([
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'BUY',
                'quantity'         => 10,
                'price'            => 100,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 10:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'BUY',
                'quantity'         => 10,
                'price'            => 200,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
        ]);

        $this->assertSame(
            150.0,
            $holding->currentAveragePrice()
        );
    }

    public function test_current_average_price_remains_same_after_partial_sell(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->createMany([
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'BUY',
                'quantity'         => 10,
                'price'            => 100,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 10:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'BUY',
                'quantity'         => 10,
                'price'            => 200,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'SELL',
                'quantity'         => 5,
                'price'            => 250,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 12:00:00',
            ],
        ]);

        $this->assertSame(
            150.0,
            $holding->currentAveragePrice()
        );
    }

    public function test_current_average_price_is_zero_after_selling_entire_position(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->createMany([
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'BUY',
                'quantity'         => 10,
                'price'            => 100,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 10:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'SELL',
                'quantity'         => 10,
                'price'            => 200,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
        ]);

        $this->assertSame(
            0.0,
            $holding->currentAveragePrice()
        );
    }

    public function test_soft_deleted_transactions_are_not_included_in_current_average_price(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        $validTransaction = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 100,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $deletedTransaction = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 200,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ]);

        $deletedTransaction->delete();

        $this->assertSame(
            100.0,
            $holding->currentAveragePrice()
        );
    }
}