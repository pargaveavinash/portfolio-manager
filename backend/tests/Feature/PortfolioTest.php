<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PortfolioTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_portfolio(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/portfolios', [
                'name'          => 'My Long Term Portfolio',
                'description'   => 'Long term wealth creation',
                'base_currency' => 'INR',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.portfolio.name',
                'My Long Term Portfolio'
            )
            ->assertJsonPath(
                'data.portfolio.base_currency',
                'INR'
            )
            ->assertJsonMissingPath(
                'data.portfolio.user_id'
            )
            ->assertJsonMissingPath(
                'data.portfolio.deleted_at'
            );

        $this->assertDatabaseHas('portfolios', [
            'user_id'       => $user->id,
            'name'          => 'My Long Term Portfolio',
            'base_currency' => 'INR',
            'deleted_at'    => null,
        ]);
    }

    public function test_portfolio_creation_requires_valid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/portfolios', [
                'name'          => '',
                'base_currency' => 'IN',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'base_currency',
            ]);
    }

    public function test_authenticated_user_can_list_only_their_portfolios(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->portfolios()->create([
            'name'          => 'My Portfolio',
            'description'   => 'My investments',
            'base_currency' => 'INR',
        ]);

        $otherUser->portfolios()->create([
            'name'          => 'Other User Portfolio',
            'description'   => 'Should not be visible',
            'base_currency' => 'INR',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/portfolios');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.portfolios')
            ->assertJsonPath(
                'data.portfolios.0.name',
                'My Portfolio'
            );
    }

    public function test_soft_deleted_portfolios_are_not_returned(): void
    {
        $user = User::factory()->create();

        $activePortfolio = $user->portfolios()->create([
            'name'          => 'Active Portfolio',
            'base_currency' => 'INR',
        ]);

        $deletedPortfolio = $user->portfolios()->create([
            'name'          => 'Deleted Portfolio',
            'base_currency' => 'INR',
        ]);

        $deletedPortfolio->delete();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/portfolios');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.portfolios')
            ->assertJsonPath(
                'data.portfolios.0.id',
                $activePortfolio->id
            );
    }

    public function test_authenticated_user_can_view_their_portfolio(): void
    {
        $user = User::factory()->create();

        $portfolio = $user->portfolios()->create([
            'name'          => 'My Portfolio',
            'description'   => 'My investments',
            'base_currency' => 'INR',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}");

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.portfolio.id',
                $portfolio->id
            )
            ->assertJsonPath(
                'data.portfolio.name',
                'My Portfolio'
            );
    }

    public function test_user_cannot_view_another_users_portfolio(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();

        $portfolio = $otherUser->portfolios()->create([
            'name'          => 'Private Portfolio',
            'description'   => 'Should not be visible',
            'base_currency' => 'INR',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}");

        $response->assertNotFound();
    }

    public function test_soft_deleted_portfolio_cannot_be_viewed(): void
    {
        $user = User::factory()->create();

        $portfolio = $user->portfolios()->create([
            'name'          => 'Deleted Portfolio',
            'base_currency' => 'INR',
        ]);

        $portfolio->delete();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/portfolios/{$portfolio->id}");

        $response->assertNotFound();
    }

    public function test_authenticated_user_can_update_their_portfolio(): void
    {
        $user = User::factory()->create();

        $portfolio = $user->portfolios()->create([
            'name'          => 'Old Name',
            'description'   => 'Old description',
            'base_currency' => 'INR',
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/v1/portfolios/{$portfolio->id}", [
                'name' => 'Updated Portfolio',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.portfolio.name',
                'Updated Portfolio'
            )
            ->assertJsonPath(
                'data.portfolio.description',
                'Old description'
            );

        $this->assertDatabaseHas('portfolios', [
            'id'          => $portfolio->id,
            'name'        => 'Updated Portfolio',
            'description' => 'Old description',
        ]);
    }

    public function test_user_cannot_update_another_users_portfolio(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();

        $portfolio = $otherUser->portfolios()->create([
            'name'          => 'Private Portfolio',
            'base_currency' => 'INR',
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/v1/portfolios/{$portfolio->id}", [
                'name' => 'Hacked Portfolio',
            ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('portfolios', [
            'id'   => $portfolio->id,
            'name' => 'Private Portfolio',
        ]);
    }

    public function test_portfolio_update_validates_input(): void
    {
        $user = User::factory()->create();

        $portfolio = $user->portfolios()->create([
            'name'          => 'My Portfolio',
            'base_currency' => 'INR',
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/v1/portfolios/{$portfolio->id}", [
                'name'          => str_repeat('A', 256),
                'base_currency' => 'INDIA',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'base_currency',
            ]);
    }

    public function test_authenticated_user_can_delete_their_portfolio(): void
    {
        $user = User::factory()->create();

        $portfolio = $user->portfolios()->create([
            'name'          => 'My Portfolio',
            'base_currency' => 'INR',
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/portfolios/{$portfolio->id}");

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.message',
                'Portfolio deleted successfully.'
            );

        $this->assertSoftDeleted('portfolios', [
            'id' => $portfolio->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_portfolio(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();

        $portfolio = $otherUser->portfolios()->create([
            'name'          => 'Private Portfolio',
            'base_currency' => 'INR',
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/portfolios/{$portfolio->id}");

        $response->assertNotFound();

        $this->assertDatabaseHas('portfolios', [
            'id'         => $portfolio->id,
            'name'       => 'Private Portfolio',
            'deleted_at' => null,
        ]);
    }
}
