<?php

namespace Tests\Feature;

use App\Models\MutualFund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MutualFundSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_search_mutual_funds()
    {
        $response = $this->getJson('/api/v1/mutual-funds/search?q=SBI');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_search_mutual_funds_by_scheme_name()
    {
        MutualFund::create([
            'amfi_code'   => '120503',
            'amc_name'    => 'SBI',
            'scheme_name' => 'SBI Flexicap',
            'plan_type'   => 'DIRECT',
            'option_type' => 'GROWTH',
        ]);

        MutualFund::create([
            'amfi_code'   => '120505',
            'amc_name'    => 'HDFC',
            'scheme_name' => 'HDFC Flexicap',
            'plan_type'   => 'DIRECT',
            'option_type' => 'GROWTH',
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/mutual-funds/search?q=SBI');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.amfi_code', '120503');
    }
}
