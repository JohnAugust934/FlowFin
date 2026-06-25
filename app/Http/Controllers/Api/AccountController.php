<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Exclusão definitiva da conta e dos dados pessoais (LGPD).
 *
 * Política adotada (decisão do Worker — exceção explícita ao soft delete prevista
 * no CLAUDE.md): PURGE FÍSICO. A exclusão LGPD remove de fato os dados pessoais do
 * banco — não basta marcar `deleted_at`. A linha do usuário é apagada e as chaves
 * estrangeiras `cascadeOnDelete` removem fisicamente categorias, transações,
 * orçamentos, metas, investimentos e recorrências. Conteúdos educativos são globais
 * (sem `user_id`) e não contêm dado pessoal, portanto não são afetados.
 *
 * Exige REAUTENTICAÇÃO por senha (`current_password`) e é irreversível.
 *
 * Contrato (UI 5.4):
 *   DELETE /api/account  body: { "password": "<senha atual>" }
 *     → 200 { "message": "Sua conta e seus dados foram excluídos definitivamente." }
 *     → 422 se a senha não confere.
 */
class AccountController extends Controller
{
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ], [
            'password.current_password' => 'A senha informada está incorreta.',
        ]);

        $user = $request->user();

        // Encerra a sessão ANTES de excluir: o logout do guard regrava o
        // remember_token do usuário (save()), o que reinseriria a linha se fosse
        // chamado após o delete. Mantemos a referência ao model para excluí-lo.
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Purge físico (LGPD): o model User não usa soft delete, então delete() já
        // é definitivo; o cascadeOnDelete das FKs remove fisicamente todas as
        // entidades pessoais (categorias, transações, orçamentos, metas,
        // investimentos, recorrências) numa única operação atômica.
        $user->delete();

        return response()->json([
            'message' => 'Sua conta e seus dados foram excluídos definitivamente.',
        ]);
    }
}
