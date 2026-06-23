<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_requests_are_rate_limited(): void
    {
        // O limite é 5 requisições por minuto por IP; a 6ª deve ser bloqueada (429).
        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', ['email' => 'quem@flowfin.test'])
                ->assertStatus(302);
        }

        $this->post('/forgot-password', ['email' => 'quem@flowfin.test'])
            ->assertStatus(429);
    }

    public function test_registration_requests_are_rate_limited(): void
    {
        // Payload inválido (confirmação não confere) para que o cadastro nunca
        // autentique a sessão — assim o throttle permanece chaveado pelo IP.
        $payload = [
            'name' => 'Repetido',
            'email' => 'repetido@flowfin.test',
            'password' => 'password',
            'password_confirmation' => 'diferente',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', $payload)->assertStatus(302);
        }

        $this->post('/register', $payload)->assertStatus(429);
    }
}
