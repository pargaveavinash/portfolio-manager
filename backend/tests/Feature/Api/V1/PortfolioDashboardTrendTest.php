<?php

namespace Tests\Feature\Api\V1;

use App\Models\Portfolio;
use App\Models\PortfolioSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioDashboardTrendTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Portfolio $portfolio;
    private string $endpoint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->portfolio = Portfolio::factory()->create([
            'user_id' => $this->user->id,
            'base_currency' => 'INR',
        ]);
        $this->endpoint = "/api/v1/portfolios/{$this->portfolio->id}/dashboard/trends";
    }

    public function test_unauthenticated_user_is_rejected()
    {
        $response = $this->getJson($this->endpoint);
        $response->assertStatus(401);
    }

    public function test_user_cannot_access_another_users_portfolio_trends()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->getJson($this->endpoint);

        $response->assertStatus(404);
    }

    public function test_successful_trend_response_has_exact_fields_and_6_decimal_formatting()
    {
        PortfolioSnapshot::forceCreate([
            'portfolio_id' => $this->portfolio->id,
            'valuation_date' => '2026-01-01',
            'invested_capital' => 10000,
            'market_value' => 12000,
            'cash_balance' => 500,
            'total_value' => 12500,
        ]);

        $response = $this->actingAs($this->user)->getJson($this->endpoint);

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'trends' => [
                    [
                        'date' => '2026-01-01',
                        'invested_capital' => '10000.000000',
                        'market_value' => '12000.000000',
                        'cash_balance' => '500.000000',
                        'total_value' => '12500.000000',
                    ]
                ]
            ]
        ]);

        $trend = $response->json('data.trends.0');

        // Assert exact fields only
        $expectedKeys = ['date', 'invested_capital', 'market_value', 'cash_balance', 'total_value'];
        $actualKeys = array_keys($trend);
        sort($expectedKeys);
        sort($actualKeys);

        $this->assertEquals($expectedKeys, $actualKeys);
    }

    public function test_chronological_ordering()
    {
        PortfolioSnapshot::forceCreate([
            'portfolio_id' => $this->portfolio->id,
            'valuation_date' => '2026-01-02',
            'invested_capital' => 10000,
            'market_value' => 12000,
            'cash_balance' => 500,
            'total_value' => 12500,
        ]);

        PortfolioSnapshot::forceCreate([
            'portfolio_id' => $this->portfolio->id,
            'valuation_date' => '2026-01-01',
            'invested_capital' => 10000,
            'market_value' => 12000,
            'cash_balance' => 500,
            'total_value' => 12500,
        ]);

        $response = $this->actingAs($this->user)->getJson($this->endpoint);

        $response->assertStatus(200);
        $this->assertEquals('2026-01-01', $response->json('data.trends.0.date'));
        $this->assertEquals('2026-01-02', $response->json('data.trends.1.date'));
    }

    public function test_from_and_to_filters_work_inclusively()
    {
        PortfolioSnapshot::forceCreate(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-01', 'invested_capital' => 0, 'market_value' => 0, 'cash_balance' => 0, 'total_value' => 0]);
        PortfolioSnapshot::forceCreate(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-02', 'invested_capital' => 0, 'market_value' => 0, 'cash_balance' => 0, 'total_value' => 0]);
        PortfolioSnapshot::forceCreate(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-03', 'invested_capital' => 0, 'market_value' => 0, 'cash_balance' => 0, 'total_value' => 0]);

        $response = $this->actingAs($this->user)->getJson($this->endpoint . '?from=2026-01-01&to=2026-01-02');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.trends'));
        $this->assertEquals('2026-01-01', $response->json('data.trends.0.date'));
        $this->assertEquals('2026-01-02', $response->json('data.trends.1.date'));
    }

    public function test_omitted_dates_returns_all()
    {
        PortfolioSnapshot::forceCreate(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-01', 'invested_capital' => 0, 'market_value' => 0, 'cash_balance' => 0, 'total_value' => 0]);
        PortfolioSnapshot::forceCreate(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-02', 'invested_capital' => 0, 'market_value' => 0, 'cash_balance' => 0, 'total_value' => 0]);

        $response = $this->actingAs($this->user)->getJson($this->endpoint);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.trends'));
    }

    public function test_invalid_date_formats_return_422()
    {
        $response = $this->actingAs($this->user)->getJson($this->endpoint . '?from=01-01-2026');
        $response->assertStatus(422);

        $response = $this->actingAs($this->user)->getJson($this->endpoint . '?to=01-01-2026');
        $response->assertStatus(422);
    }

    public function test_from_greater_than_to_returns_422()
    {
        $response = $this->actingAs($this->user)->getJson($this->endpoint . '?from=2026-01-02&to=2026-01-01');
        $response->assertStatus(422);
    }

    public function test_empty_result_returns_200_and_empty_trends()
    {
        $response = $this->actingAs($this->user)->getJson($this->endpoint);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'trends' => []
            ]
        ]);
    }

    public function test_query_count_does_not_grow_linearly_with_snapshot_count()
    {
        // Add 2 snapshots
        PortfolioSnapshot::forceCreate(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-01', 'invested_capital' => 0, 'market_value' => 0, 'cash_balance' => 0, 'total_value' => 0]);
        PortfolioSnapshot::forceCreate(['portfolio_id' => $this->portfolio->id, 'valuation_date' => '2026-01-02', 'invested_capital' => 0, 'market_value' => 0, 'cash_balance' => 0, 'total_value' => 0]);

        DB::enableQueryLog();
        $this->actingAs($this->user)->getJson($this->endpoint)->assertStatus(200);
        $queriesWith2Snapshots = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        // Add 20 more snapshots
        for ($i = 3; $i <= 22; $i++) {
            $day = str_pad($i, 2, '0', STR_PAD_LEFT);
            PortfolioSnapshot::forceCreate(['portfolio_id' => $this->portfolio->id, 'valuation_date' => "2026-01-{$day}", 'invested_capital' => 0, 'market_value' => 0, 'cash_balance' => 0, 'total_value' => 0]);
        }

        DB::enableQueryLog();
        $this->actingAs($this->user)->getJson($this->endpoint)->assertStatus(200);
        $queriesWith22Snapshots = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            $queriesWith2Snapshots + 2,
            $queriesWith22Snapshots,
            "N+1 query pattern detected for snapshots."
        );
    }
}
