<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Hardening de performance (Task 5.5): garante que os agregados do dashboard não
 * sofrem N+1 — o número de consultas permanece constante e pequeno mesmo crescendo
 * o volume de transações e de categorias.
 */
class DashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_query_count_is_constant_regardless_of_volume(): void
    {
        $user = User::factory()->create();
        $categories = Category::factory()->count(8)->create(['user_id' => $user->id]);
        foreach (range(1, 120) as $i) {
            Transaction::factory()->saida()->create([
                'user_id' => $user->id,
                'category_id' => $categories->random()->id,
                'date' => '2026-05-'.str_pad((string) (($i % 28) + 1), 2, '0', STR_PAD_LEFT),
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($user)->getJson('/api/dashboard?month=2026-05')->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // by_category usa 1 consulta para os totais + 1 (whereIn) para as categorias;
        // entrou/saiu/needs-vs-wants são somas agregadas. Sem N+1, fica numa dezena.
        $this->assertLessThanOrEqual(10, $queries, "Dashboard executou {$queries} queries — possível N+1.");
    }
}
