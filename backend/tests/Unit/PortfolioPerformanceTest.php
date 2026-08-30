<?php
namespace Tests\Unit;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class PortfolioPerformanceTest extends TestCase
{
    use RefreshDatabase;
    public function test_current_invested_cost_is_calculated_from_holdings(): void
    {
        $user      = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();
        $reliance  = $portfolio->holdings()->create(['symbol' => 'RELIANCE', 'name' => 'Reliance Industries', 'asset_type' => 'stock', 'quantity' => 8, 'average_price' => 1450, 'market_price' => 1600, 'currency' => 'INR',]);
        $niftyBees = $portfolio->holdings()->create(['symbol' => 'NIFTYBEES', 'name' => 'Nippon India ETF Nifty BeES', 'asset_type' => 'etf', 'quantity' => 20, 'average_price' => 250, 'market_price' => 275, 'currency' => 'INR',]);
        $reliance->transactions()->create(['portfolio_id' => $portfolio->id, 'type' => 'BUY', 'quantity' => 8, 'price' => 1450, 'currency' => 'INR', 'transaction_date' => '2026-08-27 10:00:00',]);
        $niftyBees->transactions()->create(['portfolio_id' => $portfolio->id, 'type' => 'BUY', 'quantity' => 20, 'price' => 250, 'currency' => 'INR', 'transaction_date' => '2026-08-27 10:00:00',]);
        $this->assertSame(16600.0, $portfolio->currentInvestedCost());
    }

    public function test_current_market_value_is_calculated_from_holdings(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()
            ->for($user)
            ->create();

        $reliance = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 8,
            'average_price' => 1450,
            'market_price'  => 1600,
            'currency'      => 'INR',
        ]);

        $niftyBees = $portfolio->holdings()->create([
            'symbol'        => 'NIFTYBEES',
            'name'          => 'Nippon India ETF Nifty BeES',
            'asset_type'    => 'etf',
            'quantity'      => 20,
            'average_price' => 250,
            'market_price'  => 275,
            'currency'      => 'INR',
        ]);

        $reliance->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 8,
            'price'            => 1450,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $niftyBees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 20,
            'price'            => 250,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $this->assertSame(
            18300.0,
            $portfolio->currentMarketValue()
        );
    }

    public function test_unrealized_profit_loss_is_calculated_from_portfolio_values(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()
            ->for($user)
            ->create();

        $reliance = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 8,
            'average_price' => 1450,
            'market_price'  => 1600,
            'currency'      => 'INR',
        ]);

        $niftyBees = $portfolio->holdings()->create([
            'symbol'        => 'NIFTYBEES',
            'name'          => 'Nippon India ETF Nifty BeES',
            'asset_type'    => 'etf',
            'quantity'      => 20,
            'average_price' => 250,
            'market_price'  => 275,
            'currency'      => 'INR',
        ]);

        $reliance->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 8,
            'price'            => 1450,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $niftyBees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 20,
            'price'            => 250,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $this->assertSame(
            1700.0,
            $portfolio->unrealizedProfitLoss()
        );
    }

    public function test_unrealized_profit_loss_percentage_is_calculated(): void
    {
        $portfolio = Portfolio::factory()->create();

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
            $portfolio->unrealizedProfitLossPercentage()
        );
    }

    public function test_unrealized_profit_loss_percentage_is_zero_when_invested_cost_is_zero(): void
    {
        $portfolio = Portfolio::factory()->create();

        $this->assertSame(
            0.0,
            $portfolio->unrealizedProfitLossPercentage()
        );
    }

    public function test_realized_profit_loss_is_calculated_from_holdings(): void
    {
        $portfolio = Portfolio::factory()->create();

        $holding1 = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'market_price'  => 1600,
            'currency'      => 'INR',
        ]);

        $holding1->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 1450,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $holding1->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'SELL',
            'quantity'         => 2,
            'price'            => 1550,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ]);

        $holding2 = $portfolio->holdings()->create([
            'symbol'        => 'NIFTYBEES',
            'name'          => 'Nippon India ETF Nifty BeES',
            'asset_type'    => 'etf',
            'quantity'      => 0,
            'average_price' => 0,
            'market_price'  => 300,
            'currency'      => 'INR',
        ]);

        $holding2->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 20,
            'price'            => 250,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $holding2->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'SELL',
            'quantity'         => 5,
            'price'            => 280,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ]);

        $this->assertSame(
            350.0,
            $portfolio->realizedProfitLoss()
        );
    }

    public function test_realized_profit_loss_percentage_is_calculated_from_holdings(): void
    {
        $portfolio = Portfolio::factory()->create();

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

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'SELL',
            'quantity'         => 2,
            'price'            => 1550,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ]);

        $this->assertSame(
            1.7241379310344827,
            $portfolio->realizedProfitLossPercentage()
        );
    }

    public function test_realized_profit_loss_percentage_is_zero_when_realized_cost_is_zero(): void
    {
        $portfolio = Portfolio::factory()->create();

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
            0.0,
            $portfolio->realizedProfitLossPercentage()
        );
    }

    public function test_total_profit_loss_is_calculated_from_realized_and_unrealized_profit_loss(): void
    {
        $portfolio = Portfolio::factory()->create();

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

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'SELL',
            'quantity'         => 2,
            'price'            => 1550,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ]);

        $this->assertSame(
            1400.0,
            $portfolio->totalProfitLoss()
        );
    }

    public function test_total_profit_loss_can_be_negative(): void
    {
        $portfolio = Portfolio::factory()->create();

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 1500,
            'market_price'  => 1300,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 1500,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'SELL',
            'quantity'         => 2,
            'price'            => 1400,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ]);

        $this->assertSame(
            -1800.0,
            $portfolio->totalProfitLoss()
        );
    }

    public function test_total_profit_loss_percentage_is_calculated(): void
    {
        $portfolio = Portfolio::factory()->create();

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

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'SELL',
            'quantity'         => 2,
            'price'            => 1550,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ]);

        $this->assertEqualsWithDelta(
            12.06896551724138,
            $portfolio->totalProfitLossPercentage(),
            0.00000000000001
        );
    }

    public function test_total_profit_loss_percentage_is_zero_when_invested_cost_is_zero(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->holdings()->create([
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
            $portfolio->totalProfitLossPercentage()
        );
    }
}