---
title: FlowFin
---

# APM Tracker

## Task Tracking

**Stage 1:** Complete

**Stage 2:** Complete

| Task | Status | Agent | Branch |
|------|--------|-------|--------|
| 2.1 | Done | backend-agent | |
| 2.2 | Done | backend-agent | |
| 2.3 | Done | frontend-agent | |
| 2.4 | Done | frontend-agent | |
| 2.5 | Done | frontend-agent | |
| 2.6 | Done | backend-agent | |
| 2.7 | Done | frontend-agent | |

**Stage 3:**

| Task | Status | Agent | Branch |
|------|--------|-------|--------|
| 3.1 | Done | backend-agent | |
| 3.2 | Done | backend-agent | |
| 3.3 | Active | frontend-agent | feature/ui-consciencia-economia |
| 3.4 | Active | frontend-agent | feature/ui-consciencia-economia |

## Worker Tracking

| Agent | Instance | Notes |
|-------|----------|-------|
| devops-docs-agent | 1 | Initialized; completed Task 1.1 (next work in Stage 6) |
| backend-agent | 1 | Completed 1.3, 1.4, 2.1, 2.2, 2.6, 3.1, 3.2 |
| frontend-agent | 1 | Completed 1.2, 2.3, 2.4, 2.5, 2.7 |

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
- NOTA contexto Worker: chats dos Workers foram fechados; cada novo dispatch reinicia o Worker em chat novo (sem memória de trabalho das Tasks anteriores). Tratar dependências com contexto explícito por caminho de arquivo (não confiar em "seu trabalho anterior").
- 2.7 DONE/mesclada em develop (merge `--no-ff`). Refino visual mobile (glassmorphism/iOS 26 + tema claro/escuro/sistema), nav de categorias no mobile, histórico arejado, ícone da marca no topo. Donut do dashboard SUBSTITUÍDO por ranking de categorias com barras de proporção (Spec pedia rosca; usuário aprovou a troca de forma) e bloco "Necessidade vs. desejo" harmonizado no mesmo padrão. Chart.js removido do bundle (~90→21KB gzip; ainda em package.json — limpeza futura opcional). Verificação final: build ✓ + 69/69 testes ✓. Branch apagada.
- Findings 2.7 p/ depois: Perfil + dropdown desktop sem dark theme (Perfil cai na Task 5.4; dropdown = ajuste curto opcional). Componentes compartilhados (.card/toast/transaction-form) agora translúcidos/cientes de tema — UIs das Stages 3/4 herdam isso (reusar como estão).
- Stage 2 COMPLETO — MVP utilizável validado pelo usuário (registrar → dashboard/ranking → histórico/filtros). Stage summary no Index.
- 3.1 DONE/mesclada (commit 93f2b18). Serviços+endpoints: recorrentes/projeção, insights (top 3 transações de saída, linha do tempo diária, comparativo mês a mês), invisíveis, orçamentos (status ok/alerta/estourado 80/100), meta de economia. NOVA coluna `users.monthly_savings_goal` (centavos, nullable) — Score do Stage 4 (peso 30%) depende dela + `PUT /api/savings-goal`. Observers de Recurrence/Budget + TransactionObserver estendido. Contratos JSON documentados no task-03-01.log.md (todos centavos, month=aaaa-mm) p/ UIs 3.3/3.4. Verificação: migrate + 100/100 testes ✓.
- A confirmar na UI 3.3: "Top 3 maiores gastos" foi implementado como as 3 TRANSAÇÕES de saída de maior valor (não top 3 categorias) — ajuste pequeno se o produto quiser categorias.
- 3.2 DONE/mesclada (commit ccb9b10). `SavingsReportService` + `GET /api/savings-report?month=aaaa-mm` → `{month, total_potential_savings, count, suggestions[{type(categoria_desejo|recorrente), reference_id, label, current_amount, cut_pct, estimated_savings, category|null, message}]}` (centavos). Cortes determinísticos: desejo 30%, recorrente 20% (decisão do Worker; Spec só exemplifica 30% — ajuste de constante se quiser variar). Possível sobreposição categoria×recorrente mantida sem dedupe (transparente). Verificação: build + 107/107 testes ✓. Branch apagada.
- 3.3+3.4 despachadas em LOTE ao Frontend (uma branch `feature/ui-consciencia-economia`, sequencial no mesmo chat) — ambos os contratos backend prontos (3.1 + 3.2). 3.3 = UI Consciência (insights/recorrentes/invisíveis/orçamentos); 3.4 = UI Economia (relatório onde economizar + meta + orçamentos/invisíveis). Validação visual guiada ao final.
- 3.2 despachada (Backend, sequencial, feature/relatorio-onde-economizar): Relatório "Onde Economizar" (sugestões determinísticas sobre maiores Desejos + recorrentes). Depende de 3.1. Após 3.2: despachar UIs 3.3 (Consciência) + 3.4 (Economia) ao Frontend como batch (ambos contratos prontos).
- Utilitário: `php artisan db:seed --class=TestDataSeeder` recria usuário de teste (teste@flowfin.com.br/senha1234, e-mail verificado) + dados (idempotente). Commitado em develop.
- GIT LESSON (do not repeat): never delete/recreate a feature branch a Worker is using — a Worker commit (2.1, `5ddba2f`) was nearly lost by branch -D + recreate; recovered via the commit object. Manager edits planning docs on `develop` only; feature branches must not modify tracker/index (avoids merge conflicts via 3-way merge).

