<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private const MONTH = '2026-06';

    private User $user;

    private Category $catA;

    private Category $catB;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DashboardService::class);
        $this->user = User::factory()->create();
        $this->catA = Category::factory()->predefined()->create(['name' => 'Alimentação']);
        $this->catB = Category::factory()->create(['user_id' => $this->user->id, 'name' => 'Pets']);
    }

    private function tx(array $attributes): Transaction
    {
        return Transaction::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'date' => self::MONTH.'-15',
        ], $attributes));
    }

    private function seedKnownScenario(): void
    {
        $this->tx(['type' => 'entrada', 'amount' => 100000, 'classification' => null, 'category_id' => $this->catA->id]);
        $this->tx(['type' => 'entrada', 'amount' => 50000, 'classification' => null, 'category_id' => $this->catA->id]);
        $this->tx(['type' => 'saida', 'amount' => 30000, 'classification' => 'necessidade', 'category_id' => $this->catA->id]);
        $this->tx(['type' => 'saida', 'amount' => 20000, 'classification' => 'desejo', 'category_id' => $this->catA->id]);
        $this->tx(['type' => 'saida', 'amount' => 10000, 'classification' => 'necessidade', 'category_id' => $this->catB->id]);
        $this->tx(['type' => 'saida', 'amount' => 5000, 'classification' => null, 'category_id' => $this->catB->id]);

        // Ruído: outro mês e outro usuário — não devem entrar no cálculo.
        $this->tx(['type' => 'saida', 'amount' => 99999, 'classification' => 'desejo', 'category_id' => $this->catA->id, 'date' => '2026-05-15']);
        Transaction::factory()->create([
            'user_id' => User::factory()->create()->id,
            'type' => 'saida', 'amount' => 77777, 'date' => self::MONTH.'-10',
            'category_id' => Category::factory()->predefined()->create()->id,
        ]);
    }

    public function test_it_computes_entrou_saiu_sobrou_in_cents(): void
    {
        $this->seedKnownScenario();

        $totals = $this->service->forUser($this->user, self::MONTH)['totals'];

        $this->assertSame(150000, $totals['entrou']);
        $this->assertSame(65000, $totals['saiu']);
        $this->assertSame(85000, $totals['sobrou']);
    }

    public function test_it_summarizes_expenses_by_category(): void
    {
        $this->seedKnownScenario();

        $byCategory = collect($this->service->forUser($this->user, self::MONTH)['by_category']);

        // Ordenado por total desc: Alimentação (50000) antes de Pets (15000).
        $this->assertSame($this->catA->id, $byCategory->first()['category_id']);
        $this->assertSame(50000, $byCategory->firstWhere('category_id', $this->catA->id)['total']);
        $this->assertSame(15000, $byCategory->firstWhere('category_id', $this->catB->id)['total']);
        $this->assertSame('Alimentação', $byCategory->first()['name']);
    }

    public function test_it_computes_needs_vs_wants_excluding_unclassified(): void
    {
        $this->seedKnownScenario();

        $nvw = $this->service->forUser($this->user, self::MONTH)['needs_vs_wants'];

        $this->assertSame(40000, $nvw['necessidade']);
        $this->assertSame(20000, $nvw['desejo']);
        $this->assertSame(5000, $nvw['sem_classificacao']);
        $this->assertSame(66.7, $nvw['necessidade_pct']);
        $this->assertSame(33.3, $nvw['desejo_pct']);
    }

    public function test_aggregates_are_cached_and_invalidated_on_create(): void
    {
        $this->seedKnownScenario();
        $key = $this->service->cacheKey($this->user->id, self::MONTH);

        $first = $this->service->forUser($this->user, self::MONTH);
        $this->assertTrue(Cache::has($key));
        $this->assertSame(65000, $first['totals']['saiu']);

        // Criar transação invalida o cache via observer.
        $this->tx(['type' => 'saida', 'amount' => 1000, 'classification' => 'desejo', 'category_id' => $this->catA->id]);
        $this->assertFalse(Cache::has($key));

        // Recalcula com o novo valor.
        $this->assertSame(66000, $this->service->forUser($this->user, self::MONTH)['totals']['saiu']);
    }

    public function test_cache_is_invalidated_on_update_and_delete(): void
    {
        $tx = $this->tx(['type' => 'saida', 'amount' => 10000, 'classification' => 'necessidade', 'category_id' => $this->catA->id]);
        $key = $this->service->cacheKey($this->user->id, self::MONTH);

        $this->service->forUser($this->user, self::MONTH);
        $this->assertTrue(Cache::has($key));

        $tx->update(['amount' => 25000]);
        $this->assertFalse(Cache::has($key));
        $this->assertSame(25000, $this->service->forUser($this->user, self::MONTH)['totals']['saiu']);

        $tx->delete();
        $this->assertFalse(Cache::has($key));
        $this->assertSame(0, $this->service->forUser($this->user, self::MONTH)['totals']['saiu']);
    }

    public function test_moving_a_transaction_between_months_invalidates_both(): void
    {
        $tx = $this->tx(['type' => 'saida', 'amount' => 10000, 'classification' => 'necessidade', 'category_id' => $this->catA->id]);
        $juneKey = $this->service->cacheKey($this->user->id, '2026-06');
        $julyKey = $this->service->cacheKey($this->user->id, '2026-07');

        $this->service->forUser($this->user, '2026-06');
        $this->service->forUser($this->user, '2026-07');
        $this->assertTrue(Cache::has($juneKey));
        $this->assertTrue(Cache::has($julyKey));

        // Move a transação de junho para julho.
        $tx->update(['date' => '2026-07-10']);

        $this->assertFalse(Cache::has($juneKey));
        $this->assertFalse(Cache::has($julyKey));
        $this->assertSame(0, $this->service->forUser($this->user, '2026-06')['totals']['saiu']);
        $this->assertSame(10000, $this->service->forUser($this->user, '2026-07')['totals']['saiu']);
    }
}
