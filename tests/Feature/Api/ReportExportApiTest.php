<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_the_monthly_report_as_csv_with_correct_data(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'Mercado']);
        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id,
            'type' => 'saida', 'amount' => 123456, 'date' => '2026-05-10',
            'description' => 'Compras', 'classification' => 'necessidade',
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id,
            'type' => 'entrada', 'amount' => 500000, 'date' => '2026-05-01', 'classification' => null,
        ]);

        $response = $this->actingAs($user)->get('/api/export/monthly?month=2026-05&format=csv');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();

        $this->assertStringContainsString('R$ 1.234,56', $csv); // valor em formato brasileiro
        $this->assertStringContainsString('10/05/2026', $csv);   // data dd/mm/aaaa
        $this->assertStringContainsString('Mercado', $csv);      // categoria
        $this->assertStringContainsString('Sobrou', $csv);       // resumo
    }

    public function test_it_exports_the_monthly_report_as_pdf(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->saida()->create([
            'user_id' => $user->id, 'amount' => 10000, 'date' => '2026-05-15',
        ]);

        $response = $this->actingAs($user)->get('/api/export/monthly?month=2026-05&format=pdf');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_monthly_export_only_includes_the_authenticated_user_data(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Transaction::factory()->create([
            'user_id' => $other->id, 'type' => 'saida', 'amount' => 999999,
            'date' => '2026-05-10', 'description' => 'SEGREDO',
        ]);

        $csv = $this->actingAs($user)
            ->get('/api/export/monthly?month=2026-05&format=csv')
            ->streamedContent();

        $this->assertStringNotContainsString('SEGREDO', $csv);
        $this->assertStringNotContainsString('9.999,99', $csv);
    }

    public function test_full_export_contains_all_user_entities(): void
    {
        $user = User::factory()->create(['name' => 'Fulano']);
        $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'Lazer']);
        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'amount' => 4242,
        ]);

        $response = $this->actingAs($user)->get('/api/export/full');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/json');
        $payload = json_decode($response->streamedContent(), true);

        $this->assertSame('Fulano', $payload['perfil']['name']);
        $this->assertCount(1, $payload['categorias']);
        $this->assertCount(1, $payload['transacoes']);
        $this->assertSame(4242, $payload['transacoes'][0]['amount']); // centavos
        $this->assertArrayNotHasKey('password', $payload['perfil']);
    }

    public function test_full_export_is_scoped_to_the_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Transaction::factory()->create(['user_id' => $other->id, 'amount' => 777]);

        $payload = json_decode(
            $this->actingAs($user)->get('/api/export/full')->streamedContent(),
            true
        );

        $this->assertCount(0, $payload['transacoes']);
    }

    public function test_export_requires_authentication(): void
    {
        $this->getJson('/api/export/monthly')->assertUnauthorized();
        $this->getJson('/api/export/full')->assertUnauthorized();
    }
}
