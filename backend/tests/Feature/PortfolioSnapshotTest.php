<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\PortfolioSnapshot;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_can_be_created_for_a_portfolio()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        $snapshot = $portfolio->snapshots()->create([
            'valuation_date' => '2026-09-17',
            'invested_capital' => 1000.50,
            'market_value' => 1500.75,
            'cash_balance' => 500.00,
            'total_value' => 2000.75,
        ]);

        $this->assertDatabaseHas('portfolio_snapshots', [
            'id' => $snapshot->id,
            'portfolio_id' => $portfolio->id,
            'valuation_date' => '2026-09-17 00:00:00',
        ]);
    }

    public function test_snapshot_belongs_to_the_correct_portfolio()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        $snapshot = $portfolio->snapshots()->create([
            'valuation_date' => '2026-09-17',
            'invested_capital' => 0,
            'market_value' => 0,
            'cash_balance' => 0,
            'total_value' => 0,
        ]);

        $this->assertTrue($snapshot->portfolio->is($portfolio));
        $this->assertTrue($portfolio->snapshots->first()->is($snapshot));
    }

    public function test_financial_values_and_date_persist_and_cast_correctly()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        $snapshot = $portfolio->snapshots()->create([
            'valuation_date' => '2026-09-17',
            'invested_capital' => 12345.678912,
            'market_value' => 98765.432198,
            'cash_balance' => 1000.50,
            'total_value' => 99765.932198,
        ]);

        $snapshot->refresh();

        $this->assertSame('2026-09-17 00:00:00', $snapshot->valuation_date->format('Y-m-d H:i:s'));
        $this->assertSame(12345.678912, $snapshot->invested_capital);
        $this->assertSame(98765.432198, $snapshot->market_value);
        $this->assertSame(1000.5, $snapshot->cash_balance);
        $this->assertSame(99765.932198, $snapshot->total_value);
    }

    public function test_duplicate_portfolio_id_and_valuation_date_is_rejected_by_the_database()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        $portfolio->snapshots()->create([
            'valuation_date' => '2026-09-17',
            'invested_capital' => 0,
            'market_value' => 0,
            'cash_balance' => 0,
            'total_value' => 0,
        ]);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('UNIQUE constraint failed');

        $portfolio->snapshots()->create([
            'valuation_date' => '2026-09-17',
            'invested_capital' => 10,
            'market_value' => 10,
            'cash_balance' => 10,
            'total_value' => 10,
        ]);
    }

    public function test_same_valuation_date_is_allowed_for_different_portfolios()
    {
        $user = User::factory()->create();
        $portfolio1 = Portfolio::factory()->for($user)->create();
        $portfolio2 = Portfolio::factory()->for($user)->create();

        $portfolio1->snapshots()->create([
            'valuation_date' => '2026-09-17',
            'invested_capital' => 0,
            'market_value' => 0,
            'cash_balance' => 0,
            'total_value' => 0,
        ]);

        $snapshot2 = $portfolio2->snapshots()->create([
            'valuation_date' => '2026-09-17',
            'invested_capital' => 0,
            'market_value' => 0,
            'cash_balance' => 0,
            'total_value' => 0,
        ]);

        $this->assertDatabaseHas('portfolio_snapshots', [
            'id' => $snapshot2->id,
            'portfolio_id' => $portfolio2->id,
        ]);
    }

    public function test_deleting_a_portfolio_cascades_to_its_snapshots()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->for($user)->create();

        $snapshot = $portfolio->snapshots()->create([
            'valuation_date' => '2026-09-17',
            'invested_capital' => 0,
            'market_value' => 0,
            'cash_balance' => 0,
            'total_value' => 0,
        ]);

        $this->assertDatabaseHas('portfolio_snapshots', ['id' => $snapshot->id]);

        $portfolio->forceDelete(); // Portfolio uses SoftDeletes, so we force delete to test DB cascade

        $this->assertDatabaseMissing('portfolio_snapshots', ['id' => $snapshot->id]);
    }
}
