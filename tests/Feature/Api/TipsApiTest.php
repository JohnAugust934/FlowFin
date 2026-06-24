<?php

namespace Tests\Feature\Api;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TipsApiTest extends TestCase
{
    use RefreshDatabase;

    private function category(): Category
    {
        return Category::factory()->create(['user_id' => null, 'is_predefined' => true]);
    }

    private function tipCodes(array $tips): array
    {
        return array_column($tips, 'code');
    }

    public function test_tips_always_include_educational_baseline(): void
    {
        $user = User::factory()->create();

        $tips = $this->actingAs($user)->getJson('/api/tips')->assertOk()->json();
        $codes = $this->tipCodes($tips);

        $this->assertContains('reserva_emergencia', $codes);
        $this->assertContains('regra_50_30_20', $codes);
    }

    public function test_alert_when_a_budget_is_exceeded(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-06-15 10:00:00'));
        $user = User::factory()->create();
        $cat = $this->category();

        Budget::factory()->create(['user_id' => $user->id, 'category_id' => $cat->id, 'monthly_limit' => 1000]);
        Transaction::factory()->saida()->create([
            'user_id' => $user->id, 'category_id' => $cat->id, 'amount' => 5000, 'date' => '2026-06-10',
        ]);

        $tips = $this->actingAs($user)->getJson('/api/tips?month=2026-06')->assertOk()->json();

        $this->assertContains('orcamento_estourado', $this->tipCodes($tips));
    }

    public function test_positive_tip_when_streak_is_active(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-06-15 10:00:00'));
        $user = User::factory()->create();
        $cat = $this->category();

        foreach (['2026-06-13', '2026-06-14', '2026-06-15'] as $date) {
            Transaction::factory()->saida()->create([
                'user_id' => $user->id, 'category_id' => $cat->id, 'amount' => 1000, 'date' => $date,
            ]);
        }

        $tips = $this->actingAs($user)->getJson('/api/tips?month=2026-06')->assertOk()->json();

        $this->assertContains('streak_ativo', $this->tipCodes($tips));
    }

    public function test_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/tips')->assertUnauthorized();
    }
}
