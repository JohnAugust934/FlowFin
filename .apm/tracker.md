---
title: FlowFin
---

# APM Tracker

## Task Tracking

**Stage 1:** Complete

**Stage 2:**

| Task | Status | Agent | Branch |
|------|--------|-------|--------|
| 2.1 | Done | backend-agent | |
| 2.2 | Done | backend-agent | |
| 2.3 | Done | frontend-agent | |
| 2.4 | Done | frontend-agent | |
| 2.5 | Done | frontend-agent | |
| 2.6 | Done | backend-agent | |
| 2.7 | Active | frontend-agent | feature/refino-visual-mobile |

## Worker Tracking

| Agent | Instance | Notes |
|-------|----------|-------|
| devops-docs-agent | 1 | Initialized; completed Task 1.1 (next work in Stage 6) |
| backend-agent | 1 | Completed 1.3, 1.4, 2.1, 2.2, 2.6 |
| frontend-agent | 1 | Completed 1.2, 2.3, 2.4, 2.5 |

## Version Control

| Repository | Base Branch | Branch Convention | Commit Convention |
|-----------|-------------|-------------------|-------------------|
| FlowFin (`C:\_PROJETOS\FlowFin`) | develop | `feature/<descrição>` off develop; `main` reserved for production releases (GitFlow) | `tipo: descrição` (feat, fix, refactor, docs, test, chore) |

## Working Notes

- (Durable engineering/env notes distilled to Memory Notes in the Index after Stage 1.)
- Deadline 02/07/2026 (~9 days). Contingency: if time runs short, declare Stage 2 (MVP) the main deliverable and document the rest in the roadmap (Task 6.3). User priority: finish quality over quantity.
- Holistic end-to-end verification planned at end of Stage 2 (MVP usable flow: registrar → dashboard/gráfico/histórico) and Stage 5 (offline sync).
- GitHub remote (`https://github.com/JohnAugust934/FlowFin`) not connected; deferred to Task 6.2, guided User action. No pushes by default. `.apm/` planning docs tracked; `.apm/worktrees/` and `.apm/bus/` gitignored.
- Cleanup pending: `.apm/worktrees/auth-seguranca` dir is OS-locked ("Device or resource busy") — git worktree already pruned; delete the leftover folder once the locking process exits.
- 2.1 done/merged. API contract (consumed by 2.3/2.5 and offline 5.2): `amount` in integer centavos in/out; writes need `Accept: application/json` + CSRF; categories list NOT paginated (justified — small set). Factories for Transaction/Category created.
- 2.2 done/merged. Dashboard endpoint for 2.4: `GET /api/dashboard?month=aaaa-mm` → `{month, totals{entrou,saiu,sobrou}, by_category[], needs_vs_wants{necessidade,desejo,sem_classificacao,*_pct}}`, all centavos. Cache invalidation via `TransactionObserver` (`#[ObservedBy]` on Transaction model — preserve it).
- 2.3+2.5 done/merged. Frontend established: central write point `api.persistTransaction` (offline interception target for 5.2); JS utils in `resources/js/flowfin/` (api, format R$↔centavos, icons Heroicons, components Alpine); global transaction form (bottom-sheet/modal) opened via `open-quick-add`; events `transaction-saved`/`edit-transaction`; histórico at `/transacoes`, categorias at `/categorias`. App shell layouts modified to wire screens.
- Task 2.6 added to Plan (Manager authority) from 2.5 finding: API `index` ignored filter params. 2.6 adds server-side `date_from/date_to/category_id/type` to `GET /api/transactions` (UI already sends them).
- 2.4 e 2.6 mescladas em develop (Manager 2). Verificação integrada na pasta principal: npm install + npm run build ✓; suíte completa 69/69 ✓ (222 asserções) — as 7 falhas de Vite manifest eram só do worktree sem build. Commits de merge: 2.6 e 2.4 (--no-ff). Worktrees removidos, branches apagadas.
- Validação visual guiada do MVP (usuário, com seeder TestDataSeeder + usuário teste@flowfin.com.br/senha1234, e-mail verificado): fluxo aprovado funcionalmente (dados, gráfico, filtros, registro OK). Surgiram 2 ajustes de Frontend → Task 2.7.
- Task 2.7 adicionada (Manager authority) da verificação holística: (a) bottom-nav mobile sem link p/ categories.manage (slot "Metas" é placeholder morto href="#"); (b) refino visual mobile moderno/distinto com estética glassmorphism/iOS 26, foco na exibição de dados; (c) tema claro/escuro/sistema (Tailwind darkMode:class + persistência localStorage, sem flash). Usar skill frontend-design. Despachada sequencialmente em feature/refino-visual-mobile na pasta principal. Prompt enriquecido antes da inicialização do Worker.
- NOTA contexto Worker: chats dos Workers foram fechados; cada novo dispatch reinicia o Worker em chat novo (sem memória de trabalho das Tasks anteriores). Tratar dependências com contexto explícito por caminho de arquivo (não confiar em "seu trabalho anterior").
- 2.7 base (1e928cd) + follow-up 1 (fff6eb8) aprovados pelo usuário, EXCETO o gráfico de rosca (ainda não agradou após 2 tentativas). Itens já OK: histórico arejado, ícone no topo, vidro/temas, nav categorias mobile.
- 2.7 follow-up 2 despachado (mesma branch/log): reformular SÓ a visualização de gastos por categoria no dashboard. Usuário autorizou buscar outra solução/referências da web ou alternativa ao donut (ranking/barras horizontais) — clareza+beleza no mobile mandam; Spec pedia rosca mas usuário abriu mão da forma. Manter contrato by_category, temas, reatividade, R$, estado vazio. Não-mesclar antes da validação visual.
- Findings 2.7 p/ depois: Perfil + dropdown desktop sem dark theme (Perfil cai na Task 5.4; dropdown = ajuste curto opcional). Componentes compartilhados (.card/toast/transaction-form) agora translúcidos/cientes de tema — UIs das Stages 3/4 herdam isso.
- 2.7 follow-up 2 (ranking de categorias substituindo o donut, commit 3c82436) APROVADO visualmente pelo usuário. Chart.js removido do bundle (~90→21KB gzip).
- 2.7 follow-up 3 despachado (mesma branch/log): harmonizar bloco "Necessidade vs. desejo" do dashboard com a linguagem visual do ranking aprovado (itens com tile/ícone, valor R$, % e barra de proporção). Contrato needs_vs_wants inalterado, temas, estado vazio e nota de não classificadas preservados. Não mesclar antes da validação visual.
- Após 2.7 aprovada (merge + validação visual): Stage 2 completo → Stage summary + despacho 3.1.
- Utilitário: `php artisan db:seed --class=TestDataSeeder` recria usuário de teste + dados (idempotente). Commitado em develop.
- GIT LESSON (do not repeat): never delete/recreate a feature branch a Worker is using — a Worker commit (2.1, `5ddba2f`) was nearly lost by branch -D + recreate; recovered via the commit object. Manager edits planning docs on `develop` only; feature branches must not modify tracker/index (avoids merge conflicts via 3-way merge).

