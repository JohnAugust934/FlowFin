---
stage: 5
task: 2
title: "Follow-up 5.2 — Categorias disponíveis offline (registrar transação sem rede)"
agent: frontend-agent
status: Completed
important_findings: true
compatibility_issues: false
---

# Follow-up Task 5.2 - Categorias disponíveis offline

## Summary
Corrigido o defeito que violava o requisito de **zero perda de dados**: em modo avião o app mostrava "offline" mas não deixava registrar lançamento, porque a lista de categorias (carregada pela rede, cache só em memória) ficava vazia ao abrir o app offline → `canSave` exigia `category_id` → botão Salvar desabilitado. Agora as categorias são cacheadas de forma **persistente** e o seletor funciona offline. Build OK, **145** testes verdes. Mesma branch/worktree `feature/pwa-offline` (commit `eccf5a3`).

## Details
- **Cache em duas camadas** (`resources/js/flowfin/components.js`):
  - Camada em memória (variável de módulo `categoriesCache`) — inalterada, evita refetch na sessão.
  - **Nova camada persistente em `localStorage`** sob a chave `flowfin:categories`. Escolhi `localStorage` em vez do IndexedDB `flowfin` porque a lista é pequena, não paginada e síncrona de ler na inicialização do formulário; não exige abrir transação no DB nem código assíncrono extra. O DB IndexedDB segue dedicado à fila de escrita (`offline-queue.js`), sem acoplamento.
- **`loadCategories(force)` resiliente offline:** ao buscar online com sucesso, grava a lista no `localStorage` (mantém o cache fresco, refletindo criação/edição/remoção via os `invalidateCategories()` já existentes que forçam refetch). Se a rede falhar (`isOfflineError`: `!navigator.onLine` **ou** `ApiError status 0`), usa a última lista persistida em vez de lançar erro/lista vazia.
- **`ensureCategories()` (formulário de transação):** mantém o fluxo; no caso de borda em que o usuário **nunca** carregou categorias online (cache vazio) e abre offline pela 1ª vez, mostra mensagem clara "Conecte-se uma vez para carregar suas categorias." em vez do erro genérico de conexão.
- **Não mexi** na fila offline, no Service Worker nem no contrato da API (já aprovados). É só disponibilidade das categorias.

## Output
- Modificado: `resources/js/flowfin/components.js` (helper de cache + `ensureCategories`).
- Commit: `eccf5a3` (branch `feature/pwa-offline`).

## Validation
- `npm run build`: OK (app.js 87,17 KB / 27,78 KB gzip). `php artisan test`: **145 passed** (459 assertions).
- Reprodução do fluxo completo offline (DevTools → Network → Offline) descrita no Report para o usuário reconfirmar no celular: abrir online uma vez → modo avião → **+** → categorias aparecem (do cache) → preencher valor + categoria → Salvar habilitado → cai na fila com feedback "pendente" → reconectar → sincroniza sem duplicar.

## Important Findings
- O cache persistente vive em **`localStorage` (`flowfin:categories`)**, não no IndexedDB. Decisão registrada acima para revisão do Manager.
- A lista cacheada reflete alterações de categoria porque os pontos de criação/edição/remoção já chamam `invalidateCategories()` e recarregam com `force`, regravando o `localStorage` quando online. Offline, mudanças de categoria não são possíveis (telas de categoria exigem rede) — coerente.
- Caso de borda documentado: 1º uso já offline (sem cache) → mensagem orientando a conectar uma vez. Caso normal (já usou online antes) funciona.
