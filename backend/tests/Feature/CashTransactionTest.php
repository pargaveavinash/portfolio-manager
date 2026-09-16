<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_cash_deposit(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'base_currency' => 'INR',
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(
                "/api/v1/portfolios/{$portfolio->id}/cash-transactions",
                [
                    'type'             => 'DEPOSIT',
                    'amount'           => 100000,
                    'currency'         => 'INR',
                    'transaction_date' => '2026-09-08 10:00:00',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.cash_transaction.type',
                'DEPOSIT'
            )
            ->assertJsonPath(
                'data.cash_transaction.amount',
                100000
            )
            ->assertJsonPath(
                'data.cash_transaction.currency',
                'INR'
            );

        $this->assertDatabaseHas('cash_transactions', [
            'portfolio_id' => $portfolio->id,
            'type'         => 'DEPOSIT',
            'amount'       => 100000,
            'currency'     => 'INR',
        ]);
    }

    public function test_user_cannot_create_cash_transaction_with_wrong_currency(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'base_currency' => 'INR',
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(
                "/api/v1/portfolios/{$portfolio->id}/cash-transactions",
                [
                    'type'             => 'DEPOSIT',
                    'amount'           => 100000,
                    'currency'         => 'USD',
                    'transaction_date' => '2026-09-08 10:00:00',
                ]
            );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('currency');
    }

    public function test_user_cannot_withdraw_more_than_cash_balance(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'base_currency' => 'INR',
        ]);

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 500,
            'currency' => 'INR',
            'transaction_date' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(
                "/api/v1/portfolios/{$portfolio->id}/cash-transactions",
                [
                    'type'             => 'WITHDRAWAL',
                    'amount'           => 1000,
                    'currency'         => 'INR',
                    'transaction_date' => '2026-09-08 10:00:00',
                ]
            );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('amount');
    }
}