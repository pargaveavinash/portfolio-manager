<?php

namespace Tests\Unit;

use App\Models\Portfolio;
use App\Models\PortfolioAllocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_portfolio_has_many_allocation_targets(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->allocationTargets()->create([
            'symbol'            => 'NIFTYBEES',
            'target_percentage' => 40,
        ]);

        $this->assertCount(
            1,
            $portfolio->allocationTargets
        );

        $this->assertSame(
            'NIFTYBEES',
            $portfolio->allocationTargets->first()->symbol
        );
    }

    public function test_allocation_target_belongs_to_portfolio(): void
    {
        $portfolio = Portfolio::factory()->create();

        $allocation = PortfolioAllocation::create([
            'portfolio_id'      => $portfolio->id,
            'symbol'            => 'NIFTYBEES',
            'target_percentage' => 40,
        ]);

        $this->assertTrue(
            $allocation->portfolio->is($portfolio)
        );
    }

    public function test_portfolio_allocation_percentage_is_calculated(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->allocationTargets()->createMany([
            [
                'symbol'            => 'NIFTYBEES',
                'target_percentage' => 40,
            ],
            [
                'symbol'            => 'GOLDBEES',
                'target_percentage' => 20,
            ],
        ]);

        $this->assertSame(
            60.0,
            $portfolio->allocationPercentage()
        );
    }

    public function test_allocation_target_percentage_cannot_exceed_hundred(): void
    {
        $portfolio = Portfolio::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        PortfolioAllocation::create([
            'portfolio_id'      => $portfolio->id,
            'symbol'            => 'NIFTYBEES',
            'target_percentage' => 110,
        ]);
    }

    public function test_allocation_target_percentage_cannot_be_negative(): void
    {
        $portfolio = Portfolio::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        PortfolioAllocation::create([
            'portfolio_id'      => $portfolio->id,
            'symbol'            => 'NIFTYBEES',
            'target_percentage' => -10,
        ]);
    }

    public function test_portfolio_allocation_percentage_total_is_calculated(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->allocationTargets()->create([
            'symbol'            => 'NIFTYBEES',
            'target_percentage' => 40,
        ]);

        $portfolio->allocationTargets()->create([
            'symbol'            => 'GOLDBEES',
            'target_percentage' => 20,
        ]);

        $portfolio->allocationTargets()->create([
            'symbol'            => 'LIQUIDBEES',
            'target_percentage' => 40,
        ]);

        $this->assertSame(
            100.0,
            $portfolio->allocationPercentageTotal()
        );
    }

    public function test_portfolio_has_valid_allocation_when_total_is_hundred(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->allocationTargets()->create([
            'symbol'            => 'NIFTYBEES',
            'target_percentage' => 60,
        ]);

        $portfolio->allocationTargets()->create([
            'symbol'            => 'GOLDBEES',
            'target_percentage' => 40,
        ]);

        $this->assertTrue(
            $portfolio->hasValidAllocation()
        );
    }

    public function test_portfolio_has_invalid_allocation_when_total_is_less_than_hundred(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->allocationTargets()->create([
            'symbol'            => 'NIFTYBEES',
            'target_percentage' => 40,
        ]);

        $portfolio->allocationTargets()->create([
            'symbol'            => 'GOLDBEES',
            'target_percentage' => 30,
        ]);

        $this->assertFalse(
            $portfolio->hasValidAllocation()
        );
    }

    public function test_portfolio_has_invalid_allocation_when_total_is_greater_than_hundred(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->allocationTargets()->create([
            'symbol'            => 'NIFTYBEES',
            'target_percentage' => 60,
        ]);

        $portfolio->allocationTargets()->create([
            'symbol'            => 'GOLDBEES',
            'target_percentage' => 50,
        ]);

        $this->assertFalse(
            $portfolio->hasValidAllocation()
        );
    }

    public function test_portfolio_current_allocation_percentage_is_calculated(): void
    {
        $portfolio = Portfolio::factory()->create();

        $niftyBees = $portfolio->holdings()->create([
            'symbol'        => 'NIFTYBEES',
            'name'          => 'Nippon India ETF Nifty BeES',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 6000,
            'currency'      => 'INR',
            'market_price'  => 6000,
        ]);

        $goldBees = $portfolio->holdings()->create([
            'symbol'        => 'GOLDBEES',
            'name'          => 'Gold ETF',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 4000,
            'currency'      => 'INR',
            'market_price'  => 4000,
        ]);

        $niftyBees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 6000,
            'currency'         => 'INR',
            'transaction_date' => '2026-09-01 10:00:00',
        ]);

        $goldBees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 4000,
            'currency'         => 'INR',
            'transaction_date' => '2026-09-01 10:00:00',
        ]);

        $this->assertSame(
            60.0,
            $portfolio->currentAllocationPercentage('NIFTYBEES')
        );
    }

    public function test_portfolio_allocation_deviation_is_calculated(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->allocationTargets()->create([
            'symbol'            => 'NIFTYBEES',
            'target_percentage' => 50,
        ]);

        $niftybees = $portfolio->holdings()->create([
            'symbol'        => 'NIFTYBEES',
            'name'          => 'Nippon India ETF Nifty BeES',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 6000,
            'currency'      => 'INR',
            'market_price'  => 6000,
        ]);

        $goldbees = $portfolio->holdings()->create([
            'symbol'        => 'GOLDBEES',
            'name'          => 'Gold ETF',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 4000,
            'currency'      => 'INR',
            'market_price'  => 4000,
        ]);

        $niftybees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 6000,
            'currency'         => 'INR',
            'transaction_date' => now(),
        ]);

        $goldbees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 4000,
            'currency'         => 'INR',
            'transaction_date' => now(),
        ]);

        $this->assertSame(
            10.0,
            $portfolio->allocationDeviation('NIFTYBEES')
        );
    }

    public function test_portfolio_rebalancing_action_is_buy_when_allocation_is_below_target(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->allocationTargets()->create([
            'symbol'            => 'NIFTYBEES',
            'target_percentage' => 50,
        ]);

        $niftybees = $portfolio->holdings()->create([
            'symbol'        => 'NIFTYBEES',
            'name'          => 'Nippon India ETF Nifty BeES',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 4000,
            'currency'      => 'INR',
            'market_price'  => 4000,
        ]);

        $goldbees = $portfolio->holdings()->create([
            'symbol'        => 'GOLDBEES',
            'name'          => 'Gold ETF',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 6000,
            'currency'      => 'INR',
            'market_price'  => 6000,
        ]);

        $niftybees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 4000,
            'currency'         => 'INR',
            'transaction_date' => now(),
        ]);

        $goldbees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 6000,
            'currency'         => 'INR',
            'transaction_date' => now(),
        ]);

        $this->assertSame(
            'BUY',
            $portfolio->rebalancingAction('NIFTYBEES')
        );
    }

    public function test_portfolio_rebalancing_action_is_sell_when_allocation_is_above_target(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->allocationTargets()->create([
            'symbol'            => 'NIFTYBEES',
            'target_percentage' => 50,
        ]);

        $niftybees = $portfolio->holdings()->create([
            'symbol'        => 'NIFTYBEES',
            'name'          => 'Nippon India ETF Nifty BeES',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 6000,
            'currency'      => 'INR',
            'market_price'  => 6000,
        ]);

        $goldbees = $portfolio->holdings()->create([
            'symbol'        => 'GOLDBEES',
            'name'          => 'Gold ETF',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 4000,
            'currency'      => 'INR',
            'market_price'  => 4000,
        ]);

        $niftybees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 6000,
            'currency'         => 'INR',
            'transaction_date' => now(),
        ]);

        $goldbees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 4000,
            'currency'         => 'INR',
            'transaction_date' => now(),
        ]);

        $this->assertSame(
            'SELL',
            $portfolio->rebalancingAction('NIFTYBEES')
        );
    }

    public function test_portfolio_rebalancing_action_is_hold_when_deviation_is_within_five_percent(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->allocationTargets()->create([
            'symbol'            => 'NIFTYBEES',
            'target_percentage' => 50,
        ]);

        $niftybees = $portfolio->holdings()->create([
            'symbol'        => 'NIFTYBEES',
            'name'          => 'Nippon India ETF Nifty BeES',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 4800,
            'currency'      => 'INR',
            'market_price'  => 4800,
        ]);

        $goldbees = $portfolio->holdings()->create([
            'symbol'        => 'GOLDBEES',
            'name'          => 'Gold ETF',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 5200,
            'currency'      => 'INR',
            'market_price'  => 5200,
        ]);

        $niftybees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 4800,
            'currency'         => 'INR',
            'transaction_date' => now(),
        ]);

        $goldbees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 5200,
            'currency'         => 'INR',
            'transaction_date' => now(),
        ]);

        $this->assertSame(
            'HOLD',
            $portfolio->rebalancingAction('NIFTYBEES')
        );
    }

    public function test_portfolio_rebalancing_action_is_hold_at_exact_five_percent_deviation(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->allocationTargets()->create([
            'symbol'            => 'NIFTYBEES',
            'target_percentage' => 50,
        ]);

        $niftybees = $portfolio->holdings()->create([
            'symbol'        => 'NIFTYBEES',
            'name'          => 'Nippon India ETF Nifty BeES',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 5500,
            'currency'      => 'INR',
            'market_price'  => 5500,
        ]);

        $goldbees = $portfolio->holdings()->create([
            'symbol'        => 'GOLDBEES',
            'name'          => 'Gold ETF',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 4500,
            'currency'      => 'INR',
            'market_price'  => 4500,
        ]);

        $niftybees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 5500,
            'currency'         => 'INR',
            'transaction_date' => now(),
        ]);

        $goldbees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 4500,
            'currency'         => 'INR',
            'transaction_date' => now(),
        ]);

        $this->assertSame(
            'HOLD',
            $portfolio->rebalancingAction('NIFTYBEES')
        );
    }

    public function test_portfolio_rebalancing_action_is_hold_when_allocation_target_does_not_exist(): void
    {
        $portfolio = Portfolio::factory()->create();

        $this->assertSame(
            'HOLD',
            $portfolio->rebalancingAction('NIFTYBEES')
        );
    }

    public function test_portfolio_current_allocation_percentage_is_zero_when_no_holdings(): void
    {
        $portfolio = Portfolio::factory()->create();

        $this->assertSame(
            0.0,
            $portfolio->currentAllocationPercentage('NIFTYBEES')
        );
    }

    public function test_portfolio_current_allocation_percentage_is_zero_when_symbol_does_not_exist(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->holdings()->create([
            'symbol'        => 'GOLDBEES',
            'name'          => 'Gold ETF',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 4000,
            'currency'      => 'INR',
            'market_price'  => 4000,
        ]);

        $this->assertSame(
            0.0,
            $portfolio->currentAllocationPercentage('NIFTYBEES')
        );
    }

    public function test_portfolio_allocation_deviation_is_zero_when_target_does_not_exist(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->holdings()->create([
            'symbol'        => 'NIFTYBEES',
            'name'          => 'Nippon India ETF Nifty BeES',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 6000,
            'currency'      => 'INR',
            'market_price'  => 6000,
        ]);

        $this->assertSame(
            0.0,
            $portfolio->allocationDeviation('NIFTYBEES')
        );
    }

    public function test_portfolio_rebalancing_action_is_hold_when_target_does_not_exist(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->holdings()->create([
            'symbol'        => 'NIFTYBEES',
            'name'          => 'Nippon India ETF Nifty BeES',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 6000,
            'currency'      => 'INR',
            'market_price'  => 6000,
        ]);

        $this->assertSame(
            'HOLD',
            $portfolio->rebalancingAction('NIFTYBEES')
        );
    }

    public function test_portfolio_rebalancing_amount_is_calculated(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->allocationTargets()->create([
            'symbol'            => 'NIFTYBEES',
            'target_percentage' => 60,
        ]);

        $niftyBees = $portfolio->holdings()->create([
            'symbol'        => 'NIFTYBEES',
            'name'          => 'Nippon India ETF Nifty BeES',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 5000,
            'currency'      => 'INR',
            'market_price'  => 5000,
        ]);

        $goldBees = $portfolio->holdings()->create([
            'symbol'        => 'GOLDBEES',
            'name'          => 'Gold ETF',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 5000,
            'currency'      => 'INR',
            'market_price'  => 5000,
        ]);

        $niftyBees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'holding_id'       => $niftyBees->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 5000,
            'currency'         => 'INR',
            'transaction_date' => now(),
        ]);

        $goldBees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'holding_id'       => $goldBees->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 5000,
            'currency'         => 'INR',
            'transaction_date' => now(),
        ]);

        $this->assertSame(
            10000.0,
            $portfolio->rebalancingAmount('NIFTYBEES')
        );
    }

    public function test_portfolio_rebalancing_plan_is_calculated(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->allocationTargets()->create([
            'symbol'            => 'NIFTYBEES',
            'target_percentage' => 60,
        ]);

        $portfolio->allocationTargets()->create([
            'symbol'            => 'GOLDBEES',
            'target_percentage' => 40,
        ]);

        $niftyBees = $portfolio->holdings()->create([
            'symbol'        => 'NIFTYBEES',
            'name'          => 'Nippon India ETF Nifty BeES',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 5000,
            'currency'      => 'INR',
            'market_price'  => 5000,
        ]);

        $goldBees = $portfolio->holdings()->create([
            'symbol'        => 'GOLDBEES',
            'name'          => 'Gold ETF',
            'asset_type'    => 'ETF',
            'quantity'      => 10,
            'average_price' => 5000,
            'currency'      => 'INR',
            'market_price'  => 5000,
        ]);

        $niftyBees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'holding_id'       => $niftyBees->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 5000,
            'currency'         => 'INR',
            'transaction_date' => now(),
        ]);

        $goldBees->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'holding_id'       => $goldBees->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 5000,
            'currency'         => 'INR',
            'transaction_date' => now(),
        ]);

        $plan = $portfolio->rebalancingPlan();

        $this->assertSame([
            [
                'symbol'    => 'NIFTYBEES',
                'target'    => 60.0,
                'current'   => 50.0,
                'deviation' => -10.0,
                'action'    => 'BUY',
                'amount'    => 10000.0,
            ],
            [
                'symbol'    => 'GOLDBEES',
                'target'    => 40.0,
                'current'   => 50.0,
                'deviation' => 10.0,
                'action'    => 'SELL',
                'amount'    => 10000.0,
            ],
        ], $plan);
    }
}