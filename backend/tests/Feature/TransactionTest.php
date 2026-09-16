<?php

namespace Tests\Feature;

use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_transaction(): void
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

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 100000,
            'currency' => 'INR',
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                "/api/v1/portfolios/{$portfolio->id}/holdings/{$holding->id}/transactions",
                [
                    'type'             => 'BUY',
                    'quantity'         => 5,
                    'price'            => 1500,
                    'currency'         => 'INR',
                    'transaction_date' => '2026-08-27 10:00:00',
                ]
            );

        $response->assertCreated();

        $response->assertJsonPath(
            'data.transaction.type',
            'BUY'
        );

        $response->assertJsonPath(
            'data.transaction.quantity',
            5
        );

        $response->assertJsonPath(
            'data.transaction.price',
            1500
        );

        $this->assertDatabaseHas('transactions', [
            'portfolio_id' => $portfolio->id,
            'holding_id'   => $holding->id,
            'type'         => 'BUY',
            'quantity'     => 5,
            'price'        => 1500,
        ]);
    }

    public function test_user_cannot_create_transaction_for_another_users_portfolio(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 1450,
            'currency'      => 'INR',
        ]);

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 100000,
            'currency' => 'INR',
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                "/api/v1/portfolios/{$portfolio->id}/holdings/{$holding->id}/transactions",
                [
                    'type'             => 'BUY',
                    'quantity'         => 5,
                    'price'            => 1500,
                    'currency'         => 'INR',
                    'transaction_date' => '2026-08-27 10:00:00',
                ]
            );

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_list_portfolio_transactions(): void
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

        $holding->transactions()->createMany([
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'BUY',
                'quantity'         => 5,
                'price'            => 1500,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 10:00:00',
            ],
            [
                'portfolio_id'     => $portfolio->id,
                'type'             => 'SELL',
                'quantity'         => 2,
                'price'            => 1550,
                'currency'         => 'INR',
                'transaction_date' => '2026-08-27 11:00:00',
            ],
        ]);

        $response = $this->actingAs($user)
            ->getJson(
                "/api/v1/portfolios/{$portfolio->id}/transactions"
            );

        $response->assertOk();

        $response->assertJsonCount(
            2,
            'data.transactions'
        );
    }

    public function test_user_cannot_list_another_users_portfolio_transactions(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 1450,
            'currency'      => 'INR',
        ]);

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 5,
            'price'            => 1500,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $response = $this->actingAs($user)
            ->getJson(
                "/api/v1/portfolios/{$portfolio->id}/transactions"
            );

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_view_transaction(): void
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

        $transaction = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 5,
            'price'            => 1500,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $response = $this->actingAs($user)
            ->getJson(
                "/api/v1/portfolios/{$portfolio->id}/transactions/{$transaction->id}"
            );

        $response->assertOk();

        $response->assertJsonPath(
            'data.transaction.type',
            'BUY'
        );

        $response->assertJsonPath(
            'data.transaction.quantity',
            5
        );

        $response->assertJsonPath(
            'data.transaction.price',
            1500
        );
    }

    public function test_user_cannot_view_another_users_transaction(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 1450,
            'currency'      => 'INR',
        ]);

        $transaction = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 5,
            'price'            => 1500,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $response = $this->actingAs($user)
            ->getJson(
                "/api/v1/portfolios/{$portfolio->id}/transactions/{$transaction->id}"
            );

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_update_transaction(): void
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

        $transaction = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 5,
            'price'            => 1500,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 100000,
            'currency' => 'INR',
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)
            ->putJson(
                "/api/v1/portfolios/{$portfolio->id}/transactions/{$transaction->id}",
                [
                    'type'             => 'BUY',
                    'quantity'         => 20,
                    'price'            => 1600,
                    'currency'         => 'INR',
                    'transaction_date' => '2026-08-27 12:00:00',
                ]
            );

        $response->assertOk();

        $response->assertJsonPath(
            'data.transaction.quantity',
            20
        );

        $response->assertJsonPath(
            'data.transaction.price',
            1600
        );
    }

    public function test_user_cannot_update_another_users_transaction(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 1450,
            'currency'      => 'INR',
        ]);

        $transaction = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 5,
            'price'            => 1500,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 100000,
            'currency' => 'INR',
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)
            ->putJson(
                "/api/v1/portfolios/{$portfolio->id}/transactions/{$transaction->id}",
                [
                    'type'             => 'BUY',
                    'quantity'         => 20,
                    'price'            => 1600,
                    'currency'         => 'INR',
                    'transaction_date' => '2026-08-27 12:00:00',
                ]
            );

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_delete_transaction(): void
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

        $transaction = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 5,
            'price'            => 1500,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $response = $this->actingAs($user)
            ->deleteJson(
                "/api/v1/portfolios/{$portfolio->id}/transactions/{$transaction->id}"
            );

        $response->assertNoContent();

        $this->assertSoftDeleted('transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_transaction(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 1450,
            'currency'      => 'INR',
        ]);

        $transaction = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 5,
            'price'            => 1500,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $response = $this->actingAs($user)
            ->deleteJson(
                "/api/v1/portfolios/{$portfolio->id}/transactions/{$transaction->id}"
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('transactions', [
            'id'         => $transaction->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_cannot_sell_more_than_current_holding_quantity(): void
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

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 1450,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                "/api/v1/portfolios/{$portfolio->id}/holdings/{$holding->id}/transactions",
                [
                    'type'             => 'SELL',
                    'quantity'         => 11,
                    'price'            => 1600,
                    'currency'         => 'INR',
                    'transaction_date' => '2026-08-27 11:00:00',
                ]
            );

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors('quantity');
    }

    public function test_user_can_sell_up_to_current_holding_quantity(): void
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

        $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 1450,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                "/api/v1/portfolios/{$portfolio->id}/holdings/{$holding->id}/transactions",
                [
                    'type'             => 'SELL',
                    'quantity'         => 10,
                    'price'            => 1600,
                    'currency'         => 'INR',
                    'transaction_date' => '2026-08-27 11:00:00',
                ]
            );

        $response->assertCreated();

        $response->assertJsonPath(
            'data.transaction.type',
            'SELL'
        );

        $response->assertJsonPath(
            'data.transaction.quantity',
            10
        );
    }

    public function test_user_can_increase_existing_sell_transaction_within_available_quantity(): void
    {
        $user = User::factory()->create();

        $portfolio = $user->portfolios()->create([
            'name' => 'My Portfolio',
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

        $sell = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'SELL',
            'quantity'         => 4,
            'price'            => 150,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ]);

        $response = $this->actingAs($user)
            ->putJson(
                "/api/v1/portfolios/{$portfolio->id}/transactions/{$sell->id}",
                [
                    'type'             => 'SELL',
                    'quantity'         => 8,
                    'price'            => 150,
                    'currency'         => 'INR',
                    'transaction_date' => '2026-08-27 11:00:00',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath('data.transaction.quantity', 8);

        $this->assertSame(
            2.0,
            $holding->fresh()->currentQuantity()
        );
    }

    public function test_user_cannot_increase_existing_sell_transaction_beyond_available_quantity(): void
    {
        $user = User::factory()->create();

        $portfolio = $user->portfolios()->create([
            'name' => 'My Portfolio',
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

        $sell = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'SELL',
            'quantity'         => 4,
            'price'            => 150,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ]);

        $response = $this->actingAs($user)
            ->putJson(
                "/api/v1/portfolios/{$portfolio->id}/transactions/{$sell->id}",
                [
                    'type'             => 'SELL',
                    'quantity'         => 11,
                    'price'            => 150,
                    'currency'         => 'INR',
                    'transaction_date' => '2026-08-27 11:00:00',
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_user_cannot_change_buy_transaction_to_sell_beyond_available_quantity(): void
    {
        $user = User::factory()->create();

        $portfolio = $user->portfolios()->create([
            'name' => 'My Portfolio',
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        $buy = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 100,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $response = $this->actingAs($user)
            ->putJson(
                "/api/v1/portfolios/{$portfolio->id}/transactions/{$buy->id}",
                [
                    'type'             => 'SELL',
                    'quantity'         => 11,
                    'price'            => 150,
                    'currency'         => 'INR',
                    'transaction_date' => '2026-08-27 10:00:00',
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_user_can_change_sell_transaction_to_buy(): void
    {
        $user = User::factory()->create();

        $portfolio = $user->portfolios()->create([
            'name' => 'My Portfolio',
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

        $sell = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'SELL',
            'quantity'         => 4,
            'price'            => 150,
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ]);

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 100000,
            'currency' => 'INR',
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)
            ->putJson(
                "/api/v1/portfolios/{$portfolio->id}/transactions/{$sell->id}",
                [
                    'type'             => 'BUY',
                    'quantity'         => 4,
                    'price'            => 150,
                    'currency'         => 'INR',
                    'transaction_date' => '2026-08-27 11:00:00',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath('data.transaction.type', 'BUY')
            ->assertJsonPath('data.transaction.quantity', 4);

        $this->assertSame(
            14.0,
            $holding->fresh()->currentQuantity()
        );
    }

    public function test_user_cannot_buy_with_insufficient_cash(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'base_currency' => 'INR',
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 1000,
            'currency' => 'INR',
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)
            ->postJson(
                "/api/v1/portfolios/{$portfolio->id}/holdings/{$holding->id}/transactions",
                [
                    'type'             => 'BUY',
                    'quantity'         => 10,
                    'price'            => 150, // Total 1500 > 1000
                    'currency'         => 'INR',
                    'transaction_date' => '2026-08-27 10:00:00',
                ]
            );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('quantity');
    }

    public function test_user_cannot_update_buy_to_exceed_available_cash(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'base_currency' => 'INR',
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 1000,
            'currency' => 'INR',
            'transaction_date' => now(),
        ]);

        $transaction = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 5,
            'price'            => 100, // Cost 500. Remaining cash: 500
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $response = $this->actingAs($user)
            ->putJson(
                "/api/v1/portfolios/{$portfolio->id}/transactions/{$transaction->id}",
                [
                    'type'             => 'BUY',
                    'quantity'         => 10,
                    'price'            => 150, // New cost 1500. Old cost 500. Required: 1500. Available: 500 + 500 = 1000. Fails.
                    'currency'         => 'INR',
                    'transaction_date' => '2026-08-27 12:00:00',
                ]
            );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('quantity');
    }

    public function test_user_cannot_decrease_sell_to_cause_negative_cash(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'base_currency' => 'INR',
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 100,
            'currency'      => 'INR',
        ]);

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 1000,
            'currency' => 'INR',
            'transaction_date' => now(),
        ]);

        $buy = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 100, // Cost 1000. Cash is now 0.
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $sell = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'SELL',
            'quantity'         => 5,
            'price'            => 200, // Proceeds 1000. Cash is now 1000.
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ]);

        // Withdraw 1000. Cash is now 0.
        $portfolio->cashTransactions()->create([
            'type' => 'WITHDRAWAL',
            'amount' => 1000,
            'currency' => 'INR',
            'transaction_date' => '2026-08-27 12:00:00',
        ]);

        // Attempt to decrease SELL proceeds to 500. Cash would become -500.
        $response = $this->actingAs($user)
            ->putJson(
                "/api/v1/portfolios/{$portfolio->id}/transactions/{$sell->id}",
                [
                    'type'             => 'SELL',
                    'quantity'         => 5,
                    'price'            => 100, // New proceeds 500.
                    'currency'         => 'INR',
                    'transaction_date' => '2026-08-27 11:00:00',
                ]
            );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('quantity'); // Or a general cash error, but quantity is used.
    }

    public function test_user_cannot_change_sell_to_buy_to_cause_negative_cash(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'base_currency' => 'INR',
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 10,
            'average_price' => 100,
            'currency'      => 'INR',
        ]);

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 1000,
            'currency' => 'INR',
            'transaction_date' => now(),
        ]);

        $buy = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 10,
            'price'            => 100, // Cost 1000. Cash is now 0.
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        $sell = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'SELL',
            'quantity'         => 5,
            'price'            => 200, // Proceeds 1000. Cash is now 1000.
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 11:00:00',
        ]);

        // Attempt to change SELL to BUY for 1000.
        // Reversing SELL removes 1000 cash. New BUY costs 1000. Total needed: 2000.
        // But current cash is only 1000. So cash would become -1000.
        $response = $this->actingAs($user)
            ->putJson(
                "/api/v1/portfolios/{$portfolio->id}/transactions/{$sell->id}",
                [
                    'type'             => 'BUY',
                    'quantity'         => 5,
                    'price'            => 200,
                    'currency'         => 'INR',
                    'transaction_date' => '2026-08-27 11:00:00',
                ]
            );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('quantity');
    }

    public function test_user_can_update_transaction_to_exactly_zero_cash(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'base_currency' => 'INR',
        ]);

        $holding = $portfolio->holdings()->create([
            'symbol'        => 'RELIANCE',
            'name'          => 'Reliance Industries',
            'asset_type'    => 'stock',
            'quantity'      => 0,
            'average_price' => 0,
            'currency'      => 'INR',
        ]);

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT',
            'amount' => 1000,
            'currency' => 'INR',
            'transaction_date' => now(),
        ]);

        $transaction = $holding->transactions()->create([
            'portfolio_id'     => $portfolio->id,
            'type'             => 'BUY',
            'quantity'         => 5,
            'price'            => 100, // Cost 500. Cash is now 500.
            'currency'         => 'INR',
            'transaction_date' => '2026-08-27 10:00:00',
        ]);

        // Update BUY to cost exactly 1000, leaving exactly 0 cash.
        $response = $this->actingAs($user)
            ->putJson(
                "/api/v1/portfolios/{$portfolio->id}/transactions/{$transaction->id}",
                [
                    'type'             => 'BUY',
                    'quantity'         => 10,
                    'price'            => 100, // New cost 1000.
                    'currency'         => 'INR',
                    'transaction_date' => '2026-08-27 12:00:00',
                ]
            );

        $response->assertOk();
    }
}
