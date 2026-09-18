<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\PortfolioSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Portfolio $portfolio;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->portfolio = Portfolio::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    public function test_owner_can_retrieve_history()
    {
        PortfolioSnapshot::factory()->create([
            'portfolio_id' => $this->portfolio->id,
            'valuation_date' => '2026-01-01',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/portfolios/{$this->portfolio->id}/history");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'history' => [
                        '*' => [
                            'valuation_date',
                            'invested_capital',
                            'market_value',
                            'cash_balance',
                            'total_value',
                        ]
                    ]
                ]
            ]);
    }

    public function test_unauthenticated_user_is_rejected()
    {
        $response = $this->getJson("/api/v1/portfolios/{$this->portfolio->id}/history");
        
        $response->assertStatus(401);
    }

    public function test_user_cannot_retrieve_another_users_portfolio_history()
    {
        $otherUser = User::factory()->create();
        
        $response = $this->actingAs($otherUser)
            ->getJson("/api/v1/portfolios/{$this->portfolio->id}/history");

        $response->assertStatus(404);
    }

    public function test_correct_persisted_snapshot_values_are_returned()
    {
        PortfolioSnapshot::factory()->create([
            'portfolio_id' => $this->portfolio->id,
            'valuation_date' => '2026-01-01',
            'invested_capital' => '10000.000000',
            'market_value' => '10500.000000',
            'cash_balance' => '500.000000',
            'total_value' => '11000.000000',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/portfolios/{$this->portfolio->id}/history");

        $response->assertStatus(200)
            ->assertJsonPath('data.history.0.valuation_date', '2026-01-01')
            ->assertJsonPath('data.history.0.invested_capital', '10000.000000')
            ->assertJsonPath('data.history.0.market_value', '10500.000000')
            ->assertJsonPath('data.history.0.cash_balance', '500.000000')
            ->assertJsonPath('data.history.0.total_value', '11000.000000');
    }

    public function test_results_are_chronological()
    {
        PortfolioSnapshot::factory()->create([
            'portfolio_id' => $this->portfolio->id,
            'valuation_date' => '2026-01-03',
        ]);
        PortfolioSnapshot::factory()->create([
            'portfolio_id' => $this->portfolio->id,
            'valuation_date' => '2026-01-01',
        ]);
        PortfolioSnapshot::factory()->create([
            'portfolio_id' => $this->portfolio->id,
            'valuation_date' => '2026-01-02',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/portfolios/{$this->portfolio->id}/history");

        $response->assertStatus(200)
            ->assertJsonPath('data.history.0.valuation_date', '2026-01-01')
            ->assertJsonPath('data.history.1.valuation_date', '2026-01-02')
            ->assertJsonPath('data.history.2.valuation_date', '2026-01-03');
    }

    public function test_from_filter_works()
    {
        PortfolioSnapshot::factory()->create(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-01']);
        PortfolioSnapshot::factory()->create(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-02']);
        PortfolioSnapshot::factory()->create(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-03']);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/portfolios/{$this->portfolio->id}/history?from=2026-01-02");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.history')
            ->assertJsonPath('data.history.0.valuation_date', '2026-01-02')
            ->assertJsonPath('data.history.1.valuation_date', '2026-01-03');
    }

    public function test_to_filter_works()
    {
        PortfolioSnapshot::factory()->create(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-01']);
        PortfolioSnapshot::factory()->create(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-02']);
        PortfolioSnapshot::factory()->create(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-03']);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/portfolios/{$this->portfolio->id}/history?to=2026-01-02");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.history')
            ->assertJsonPath('data.history.0.valuation_date', '2026-01-01')
            ->assertJsonPath('data.history.1.valuation_date', '2026-01-02');
    }

    public function test_from_and_to_filter_works()
    {
        PortfolioSnapshot::factory()->create(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-01']);
        PortfolioSnapshot::factory()->create(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-02']);
        PortfolioSnapshot::factory()->create(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-03']);
        PortfolioSnapshot::factory()->create(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-04']);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/portfolios/{$this->portfolio->id}/history?from=2026-01-02&to=2026-01-03");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.history')
            ->assertJsonPath('data.history.0.valuation_date', '2026-01-02')
            ->assertJsonPath('data.history.1.valuation_date', '2026-01-03');
    }

    public function test_date_boundaries_are_inclusive()
    {
        PortfolioSnapshot::factory()->create(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-01']);
        PortfolioSnapshot::factory()->create(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-02']);
        PortfolioSnapshot::factory()->create(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-03']);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/portfolios/{$this->portfolio->id}/history?from=2026-01-02&to=2026-01-02");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.history')
            ->assertJsonPath('data.history.0.valuation_date', '2026-01-02');
    }

    public function test_invalid_from_returns_422()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/portfolios/{$this->portfolio->id}/history?from=invalid-date");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from']);
    }

    public function test_invalid_to_returns_422()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/portfolios/{$this->portfolio->id}/history?to=invalid-date");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    }

    public function test_from_greater_than_to_returns_422()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/portfolios/{$this->portfolio->id}/history?from=2026-01-02&to=2026-01-01");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    }

    public function test_empty_history_returns_empty_array()
    {
        // No snapshots created
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/portfolios/{$this->portfolio->id}/history");

        $response->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    'history' => []
                ]
            ]);
    }

    public function test_portfolio_with_no_snapshots_returns_empty_history()
    {
        // Same as above effectively, testing portfolio with no transactions/snapshots
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/portfolios/{$this->portfolio->id}/history");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.history');
    }

    public function test_historical_values_are_read_from_persisted_snapshots_rather_than_recalculated()
    {
        // We create a snapshot explicitly. We DO NOT create any holdings or mutual fund NAVs.
        // If the endpoint attempts to recalculate, it would fail or return zeroes, or throw MissingMarketDataException.
        PortfolioSnapshot::factory()->create([
            'portfolio_id' => $this->portfolio->id,
            'valuation_date' => '2026-05-05',
            'invested_capital' => '9999.123456',
            'market_value' => '8888.123456',
            'cash_balance' => '7777.123456',
            'total_value' => '6666.123456',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/portfolios/{$this->portfolio->id}/history");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.history')
            ->assertJsonPath('data.history.0.valuation_date', '2026-05-05')
            ->assertJsonPath('data.history.0.invested_capital', '9999.123456')
            ->assertJsonPath('data.history.0.market_value', '8888.123456')
            ->assertJsonPath('data.history.0.cash_balance', '7777.123456')
            ->assertJsonPath('data.history.0.total_value', '6666.123456');
    }
}
