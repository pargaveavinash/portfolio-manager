<?php

namespace Tests\Unit;

use App\Models\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioCashTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_balance_is_calculated_from_deposits_and_withdrawals(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->cashTransactions()->create([
            'type'             => 'DEPOSIT',
            'amount'           => 100000,
            'currency'         => 'INR',
            'transaction_date' => '2026-09-08 10:00:00',
        ]);

        $portfolio->cashTransactions()->create([
            'type'             => 'WITHDRAWAL',
            'amount'           => 20000,
            'currency'         => 'INR',
            'transaction_date' => '2026-09-08 11:00:00',
        ]);

        $this->assertSame(
            80000.0,
            $portfolio->cashBalance()
        );
    }

    public function test_soft_deleted_cash_transactions_are_not_included_in_cash_balance(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->cashTransactions()->create([
            'type'             => 'DEPOSIT',
            'amount'           => 100000,
            'currency'         => 'INR',
            'transaction_date' => '2026-09-08 10:00:00',
        ]);

        $withdrawal = $portfolio->cashTransactions()->create([
            'type'             => 'WITHDRAWAL',
            'amount'           => 20000,
            'currency'         => 'INR',
            'transaction_date' => '2026-09-08 11:00:00',
        ]);

        $withdrawal->delete();

        $this->assertSame(
            100000.0,
            $portfolio->cashBalance()
        );
    }

    public function test_buy_transaction_reduces_cash_balance(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->cashTransactions()->create([
            'type'             => 'DEPOSIT',
            'amount'           => 100000,
            'currency'         => 'INR',
            'transaction_date' => '2026-09-08 10:00:00',
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'market_price'  => 2000,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 2000,
            'currency'         => 'INR',
            'transaction_date' => '2026-09-08 11:00:00',
        ]);

        $this->assertSame(
            80000.0,
            $portfolio->cashBalance()
        );
    }

    public function test_sell_transaction_increases_cash_balance(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->cashTransactions()->create([
            'type'             => 'DEPOSIT',
            'amount'           => 100000,
            'currency'         => 'INR',
            'transaction_date' => '2026-09-08 10:00:00',
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'market_price'  => 2000,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'SELL',
            'quantity'         => 10,
            'price'            => 2000,
            'currency'         => 'INR',
            'transaction_date' => '2026-09-08 11:00:00',
        ]);

        $this->assertSame(
            120000.0,
            $portfolio->cashBalance()
        );
    }

    public function test_soft_deleted_investment_transactions_are_not_included_in_cash_balance(): void
    {
        $portfolio = Portfolio::factory()->create();

        $portfolio->cashTransactions()->create([
            'type'             => 'DEPOSIT',
            'amount'           => 100000,
            'currency'         => 'INR',
            'transaction_date' => '2026-09-08 10:00:00',
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'market_price'  => 2000,
            'currency'      => 'INR',
        ]);

        $buy = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 2000,
            'currency'         => 'INR',
            'transaction_date' => '2026-09-08 11:00:00',
        ]);

        $buy->delete();

        $this->assertSame(
            100000.0,
            $portfolio->cashBalance()
        );
    }

}