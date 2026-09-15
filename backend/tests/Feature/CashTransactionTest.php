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
}