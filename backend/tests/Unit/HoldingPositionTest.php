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

    public function test_current_invested_cost_is_calculated_from_current_position(): void
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
            2250.0,
            $holding->currentInvestedCost()
        );
    }

    public function test_current_invested_cost_is_zero_after_selling_entire_position(): void
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
                'price'            => 150,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
        ]);

        $this->assertSame(
            0.0,
            $holding->currentInvestedCost()
        );
    }

    public function test_soft_deleted_transactions_are_not_included_in_current_invested_cost(): void
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

        $activeBuy = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 100,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $deletedBuy = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 200,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ]);

        $deletedBuy->delete();

        $this->assertSame(
            1000.0,
            $holding->currentInvestedCost()
        );
    }

    public function test_current_invested_cost_is_zero_when_holding_has_no_transactions(): void
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

        $this->assertSame(
            0.0,
            $holding->currentInvestedCost()
        );
    }

    public function test_current_market_price_is_returned(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 1450,
            'currency'      => 'INR',
        ]);

        $holding->market_price = 1600;

        $this->assertSame(
            1600.0,
            $holding->currentMarketPrice()
        );
    }

    public function test_current_market_price_is_persisted(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 1450,
            'currency'      => 'INR',
            'market_price'  => 1600,
        ]);

        $holding->refresh();

        $this->assertSame(
            1600.0,
            $holding->currentMarketPrice()
        );
    }

    public function test_current_market_value_is_calculated_from_current_position(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 1450,
            'market_price'  => 1600,
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
        ]);

        $this->assertSame(
            16000.0,
            $holding->currentMarketValue()
        );
    }

    public function test_current_market_value_is_zero_after_selling_entire_position(): void
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
            'market_price'  => 1600,
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
                'price'            => 150,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
        ]);

        $this->assertSame(
            0.0,
            $holding->currentMarketValue()
        );
    }

    public function test_current_market_value_uses_remaining_quantity_after_partial_sell(): void
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
            'market_price'  => 160,
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
                'quantity'         => 4,
                'price'            => 150,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
        ]);

        $this->assertSame(
            960.0,
            $holding->currentMarketValue()
        );
    }

    public function test_current_market_value_is_zero_when_market_price_is_not_available(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 1450,
            'market_price'  => null,
            'currency'      => 'INR',
        ]);

        $this->assertSame(
            0.0,
            $holding->currentMarketValue()
        );
    }

    public function test_unrealized_profit_is_calculated_from_current_position(): void
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
            'market_price'  => 1600,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 1450,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $this->assertSame(
            1500.0,
            $holding->unrealizedProfitLoss()
        );
    }

    public function test_unrealized_loss_is_calculated_from_current_position(): void
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
            'market_price'  => 1300,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 1450,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $this->assertSame(
            -1500.0,
            $holding->unrealizedProfitLoss()
        );
    }

    public function test_unrealized_profit_loss_is_zero_after_selling_entire_position(): void
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
            'market_price'  => 1600,
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
                'price'            => 1500,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
        ]);

        $this->assertSame(
            0.0,
            $holding->unrealizedProfitLoss()
        );
    }

    public function test_unrealized_profit_loss_percentage_is_calculated(): void
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
            'market_price'  => 1600,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 1450,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $this->assertSame(
            10.344827586206897,
            $holding->unrealizedProfitLossPercentage()
        );
    }

    public function test_unrealized_profit_loss_percentage_is_negative_for_loss(): void
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
            'market_price'  => 1300,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 1450,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $this->assertSame(
            -10.344827586206897,
            $holding->unrealizedProfitLossPercentage()
        );
    }

    public function test_unrealized_profit_loss_percentage_is_zero_when_invested_cost_is_zero(): void
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
            'market_price'  => 1600,
            'currency'      => 'INR',
        ]);

        $this->assertSame(
            0.0,
            $holding->unrealizedProfitLossPercentage()
        );
    }

    public function test_realized_profit_is_calculated_from_sell_transaction(): void
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
                'quantity'         => 4,
                'price'            => 150,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
        ]);

        $this->assertSame(
            200.0,
            $holding->realizedProfitLoss()
        );
    }

    public function test_realized_loss_is_calculated_from_sell_transaction(): void
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
                'quantity'         => 4,
                'price'            => 80,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
        ]);

        $this->assertSame(
            -80.0,
            $holding->realizedProfitLoss()
        );
    }

    public function test_realized_profit_uses_average_buy_price_from_multiple_buy_transactions(): void
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
            500.0,
            $holding->realizedProfitLoss()
        );
    }

    public function test_realized_profit_is_accumulated_across_multiple_sell_transactions(): void
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
                'quantity'         => 2,
                'price'            => 150,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'SELL',
                'quantity'         => 3,
                'price'            => 120,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 12:00:00',
            ],
        ]);

        $this->assertSame(
            160.0,
            $holding->realizedProfitLoss()
        );
    }

    public function test_soft_deleted_sell_transactions_are_not_included_in_realized_profit_loss(): void
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
                'quantity'         => 2,
                'price'            => 150,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'SELL',
                'quantity'         => 3,
                'price'            => 200,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 12:00:00',
            ],
        ]);

        $holding->transactions()
            ->where('type', 'SELL')
            ->where('price', 200)
            ->first()
            ->delete();

        $this->assertSame(
            100.0,
            $holding->realizedProfitLoss()
        );
    }

    public function test_realized_profit_loss_is_zero_when_there_are_no_sell_transactions(): void
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

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 100,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $this->assertSame(
            0.0,
            $holding->realizedProfitLoss()
        );
    }

    public function test_realized_profit_loss_is_preserved_after_selling_entire_position(): void
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
                'price'            => 150,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
        ]);

        $this->assertSame(
            500.0,
            $holding->realizedProfitLoss()
        );

        $this->assertSame(
            0.0,
            $holding->currentQuantity()
        );
    }

    public function test_realized_profit_loss_is_calculated_chronologically_after_rebuy(): void
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
                'price'            => 150,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'BUY',
                'quantity'         => 10,
                'price'            => 200,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 12:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'SELL',
                'quantity'         => 10,
                'price'            => 250,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 13:00:00',
            ],
        ]);

        $this->assertSame(
            1000.0,
            $holding->realizedProfitLoss()
        );
    }

    public function test_realized_profit_loss_uses_remaining_position_cost_after_rebuy(): void
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
                'quantity'         => 5,
                'price'            => 150,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'BUY',
                'quantity'         => 10,
                'price'            => 200,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 12:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'SELL',
                'quantity'         => 10,
                'price'            => 250,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 13:00:00',
            ],
        ]);

        $this->assertEqualsWithDelta(
            1083.333333,
            $holding->realizedProfitLoss(),
            0.000001
        );
    }

    public function test_realized_profit_loss_uses_remaining_position_cost_after_rebuy_for_loss(): void
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
                'price'            => 200,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 10:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'SELL',
                'quantity'         => 5,
                'price'            => 150,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'BUY',
                'quantity'         => 10,
                'price'            => 100,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 12:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'SELL',
                'quantity'         => 10,
                'price'            => 80,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 13:00:00',
            ],
        ]);

        $this->assertEqualsWithDelta(
            -783.333333,
            $holding->realizedProfitLoss(),
            0.000001
        );
    }

    public function test_realized_profit_loss_percentage_is_calculated(): void
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
            'quantity'         => 5,
            'price'            => 150,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ],
    ]);

    $this->assertSame(
        50.0,
        $holding->realizedProfitLossPercentage()
    );
}

public function test_realized_profit_loss_percentage_is_negative_for_loss(): void
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
            'quantity'         => 4,
            'price'            => 50,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ],
    ]);

    $this->assertSame(
        -50.0,
        $holding->realizedProfitLossPercentage()
    );
}

public function test_realized_profit_loss_percentage_is_zero_when_there_are_no_sell_transactions(): void
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

    $holding->transactions()->create([
        'portfolio_id'     => $portfolio->id,
        'type'             => 'BUY',
        'quantity'         => 10,
        'price'            => 100,
        'currency'         => 'INR',
        'transaction_date' => '2026-08-27 10:00:00',
    ]);

    $this->assertSame(
        0.0,
        $holding->realizedProfitLossPercentage()
    );
}

public function test_realized_profit_loss_percentage_is_calculated_across_multiple_sell_transactions(): void
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
            'quantity'         => 2,
            'price'            => 150,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ],
        [
            'portfolio_id'     => $portfolio->id,
            'type'             => 'SELL',
            'quantity'         => 3,
            'price'            => 120,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 12:00:00',
        ],
    ]);

    $this->assertSame(
    32.0,
    $holding->realizedProfitLossPercentage()
);
}

public function test_realized_profit_loss_percentage_is_calculated_chronologically_after_rebuy(): void
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
            'quantity'         => 5,
            'price'            => 150,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ],
        [
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 5,
            'price'            => 200,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 12:00:00',
        ],
        [
            'portfolio_id'     => $portfolio->id,
            'type'             => 'SELL',
            'quantity'         => 5,
            'price'            => 250,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 13:00:00',
        ],
    ]);

    $this->assertSame(
    60.0,
    $holding->realizedProfitLossPercentage()
);
}

public function test_soft_deleted_sell_transactions_are_not_included_in_realized_profit_loss_percentage(): void
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

    $holding->transactions()->create([
        'portfolio_id'     => $portfolio->id,
        'type'             => 'BUY',
        'quantity'         => 10,
        'price'            => 100,
        'currency'         => 'INR',
        'transaction_date' => '2026-08-27 10:00:00',
    ]);

    $validSell = $holding->transactions()->create([
        'portfolio_id'     => $portfolio->id,
        'type'             => 'SELL',
        'quantity'         => 2,
        'price'            => 150,
        'currency'         => 'INR',
        'transaction_date' => '2026-08-27 11:00:00',
    ]);

    $deletedSell = $holding->transactions()->create([
        'portfolio_id'     => $portfolio->id,
        'type'             => 'SELL',
        'quantity'         => 2,
        'price'            => 200,
        'currency'         => 'INR',
        'transaction_date' => '2026-08-27 12:00:00',
    ]);

    $deletedSell->delete();

    $this->assertSame(
        50.0,
        $holding->realizedProfitLossPercentage()
    );
}
}