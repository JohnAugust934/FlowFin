---
stage: 5
task: 3
title: "Follow-up 5.3+ — Idempotência de transações por client_uuid (Backend Agent)"
agent: backend-agent
status: Success
important_findings: true
compatibility_issues: false
---

# Follow-up 5.3+ — Idempotência server-side por `client_uuid`

## Summary
A criação de transação passou a ser **idempotente** pela chave `client_uuid` enviada
pela camada offline (Task 5.2). O mesmo `client_uuid` (do mesmo usuário) nunca cria duas
transações: um reenvio devolve a transação já existente com **200**, fechando o caso de
borda de duplicação quando a resposta do POST se perde no meio. Suíte sobe para **160/160**.

## Details
- **Migration aditiva** `2026_06_25_000002_add_client_uuid_to_transactions_table.php`:
  coluna `client_uuid` (uuid, **nullable**) + **unique COMPOSTO** `(user_id, client_uuid)`
  (`transactions_user_client_uuid_unique`). Transações antigas ficam com `client_uuid` nulo
  (sem perda de dados); no MySQL múltiplos NULLs são aceitos no índice unique.
  - **Justificativa do escopo do unique:** a chave é gerada no dispositivo do usuário, então
    o escopo correto é **por usuário** — um UUID coincidente de outro usuário nunca deve
    bloquear a criação. Escopo global seria incorreto e abriria um vetor de colisão entre
    usuários.
- **`StoreTransactionRequest`:** aceita `client_uuid` → `['nullable', 'uuid']`.
- **`Transaction` model:** `client_uuid` adicionado ao `$fillable`.
- **`TransactionController@store`:** se vier `client_uuid` e já existir transação **do mesmo
  usuário** (`$request->user()->transactions()->where('client_uuid', ...)`) com aquela chave,
  retorna a existente (`TransactionResource` com `category` carregada) com **200**. Sem
  `client_uuid`, o fluxo atual (201, cria) fica **inalterado**. A verificação é escopada ao
  `user_id` autenticado.
- **`TransactionResource`:** passa a ecoar `client_uuid` na resposta, permitindo à fila offline
  reconciliar o registro local com o do servidor.

## Output
- Migration: `database/migrations/2026_06_25_000002_add_client_uuid_to_transactions_table.php`
- `app/Http/Controllers/Api/TransactionController.php` (lógica de idempotência + docblock do contrato)
- `app/Http/Requests/StoreTransactionRequest.php` (regra `client_uuid`)
- `app/Models/Transaction.php` (fillable)
- `app/Http/Resources/TransactionResource.php` (echo do `client_uuid`)
- Teste: `tests/Feature/Api/TransactionIdempotencyApiTest.php` (5 casos)
- Commit: `feea1fb` na branch `feature/export-lgpd-hardening`

## Validation
- `php artisan migrate` (worktree) → migration aplicada.
- `npm run build` + `php artisan test` → **160/160 passando, 518 asserções** (era 155/498).
- Casos cobertos: reenvio com mesmo `client_uuid` → **1 única** transação e 2ª resposta é a
  mesma (200, mesmo id); `client_uuid` distintos criam normalmente; sem `client_uuid` o
  comportamento atual é mantido (dois POSTs = duas transações); **isolamento por usuário**
  (mesmo UUID em usuários diferentes cria uma para cada); `client_uuid` malformado → 422.

## Issues
None.

## Important Findings
- **Contrato atualizado (relevante para Frontend / 5.2 / 5.4):**
  - `POST /api/transactions` aceita campo **opcional** `client_uuid` (formato uuid).
  - **Reenvio do mesmo `client_uuid` (mesmo usuário) devolve 200** com a transação já criada,
    em vez de 201/duplicar. Sem o campo, comportamento inalterado (201).
  - A resposta de transação agora inclui `client_uuid` (echo) para reconciliação offline.
- **Para deploy:** rodar `php artisan migrate` aplica a coluna+unique (aditivo, sem perda de
  dados). Nenhuma dependência nova (não exigiu composer).
