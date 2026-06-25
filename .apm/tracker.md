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

**Stage 3:** Complete

| Task | Status | Agent | Branch |
|------|--------|-------|--------|
| 3.1 | Done | backend-agent | |
| 3.2 | Done | backend-agent | |
| 3.3 | Done | frontend-agent | |
| 3.4 | Done | frontend-agent | |

**Stage 4:** Complete

| Task | Status | Agent | Branch |
|------|--------|-------|--------|
| 4.1 | Done | backend-agent | |
| 4.2 | Done | backend-agent | |
| 4.3 | Done | frontend-agent | |
| 4.4 | Done | frontend-agent | |

**Stage 5:** In Progress

| Task | Status | Agent | Branch |
|------|--------|-------|--------|
| 5.1 | Dispatched | frontend-agent | feature/pwa-offline |
| 5.2 | Dispatched | frontend-agent | feature/pwa-offline |
| 5.3 | Dispatched | backend-agent | feature/export-lgpd-hardening |
| 5.4 | Blocked (dep 5.3) | frontend-agent | |
| 5.5 | Dispatched | backend-agent | feature/export-lgpd-hardening |

## Worker Tracking

| Agent | Instance | Notes |
|-------|----------|-------|
| devops-docs-agent | 1 | Initialized; completed Task 1.1 (next work in Stage 6) |
| backend-agent | 1 | Completed 1.3, 1.4, 2.1, 2.2, 2.6, 3.1, 3.2, 4.1, 4.2 |
| frontend-agent | 1 | Completed 1.2, 2.3, 2.4, 2.5, 2.7, 3.3, 3.4 |

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
- 3.3+3.4 DONE/mescladas (commit 9549c76, merge --no-ff). Telas `/consciencia` (top 3 transações, linha do tempo diária em barras CSS, comparativo mês a mês com setas semafóricas) e `/economia` (meta com barra+CRUD, orçamentos semafóricos 80/100 com CRUD, invisíveis, onde economizar). SEM reintroduzir Chart.js (barras CSS, consistente com dashboard). Usuário APROVOU as 3 decisões: Top 3 = transações; invisíveis na tela Economia; navegação reorganizada (bottom-nav mobile = Início·Transações·[+]·Consciência·Economia; Categorias virou ícone no cabeçalho; Perfil no avatar; desktop = …·Consciência·Economia·Categorias). Verificação: build + 107/107 ✓.
- Stage 3 COMPLETO. Lacuna conhecida p/ depois: NÃO há tela de cadastro de recorrências ligada na navegação (endpoints CRUD existem da 3.1) — painel de "invisíveis" depende de transações marcadas `is_recurring`. Avaliar uma UI de recorrências (candidata a Stage futuro/roadmap 6.3 se o tempo apertar).
- 4.1+4.2 DONE/mescladas (commit 002ed28). 4.1: `ScoreService` (40/30/30 com renormalização quando falta orçamento/meta; consistência=dias do mês), `StreakService`, job `RecalculateStreaks` no scheduler (`dailyAt 00:10`, fila no banco — confirmado em schedule:list), `TipsService`, seed de 5 conteúdos educativos. Endpoints `/api/score|streak|tips|educational-contents`. Migration aditiva `users.current_streak`. 4.2: CRUD metas (propósito mapeado em `description`; ordenação alta→media→baixa; sem migration nova), `SimulatorService` (`POST /api/goals/simulate`, ceil), CRUD investimentos com `total_invested`. Resources encapsulam em `data` (wrapping ATIVO — UIs devem ler `.data`). Decisões do Worker aceitas pelo Manager (alinhadas à Spec). Verificação: migrate + build + 145/145 testes ✓ (459 asserções) + scheduler ✓. PARA DEPLOY (6.1): cron único → `schedule:run` + `QUEUE_CONNECTION=database` (orientar usuário).
- 4.3+4.4 DONE/mescladas (commit de merge em develop). Telas `/mentalidade` (Score em anel SVG + 3 fatores, streak 🔥, dicas por nível, conteúdos paginados) e `/direcionamento` (resumo prioridades, metas com propósito+progresso+CRUD, simulador ao vivo, investimentos+total+CRUD). SEM Chart.js. Decisões do usuário aceitas: pilares 4/5 acessíveis (desktop = barra superior; mobile = menu ≡ no cabeçalho com Mentalidade/Direcionamento/Categorias+tema+perfil/sair); Categorias saiu da barra → menu avatar (desktop)/painel ≡ (mobile). Filtro de temas cresce ao navegar; resumo de prioridades conta a página atual (20). 
- Cabeçalho (app shell) REDESENHADO em 3 follow-ups guiados pelo usuário: (1) redesenho profissional desktop (3 zonas: marca/nav pills com ativo/ações) + mobile (topo enxuto + painel ≡); (2) superfícies mais opacas (vidro vazava); (3) FIX no menu ≡ mobile: scrim 40%→60% + blur, e overlay recortado `top-14` (antes `inset-0` escurecia a própria barra deixando ícones apagados — efeito amador). Aprovado. Beneficia TODAS as telas (global). Verificação: build + 145/145 ✓.
- Stage 4 COMPLETO. PARA DEPLOY (6.1): cron único → `php artisan schedule:run` + `QUEUE_CONNECTION=database` ativam o reset diário de streak (job RecalculateStreaks 00:10) — orientar usuário.
- GITHUB: usuário pediu (24/06, fim do dia) para PUBLICAR no remoto `https://github.com/JohnAugust934/FlowFin` AGORA (antes da Task 6.2). Autorização explícita e durável p/ push. Ver Working Notes de VC se push exigiu auth do usuário.
- Utilitário: `php artisan db:seed --class=TestDataSeeder` recria usuário de teste (teste@flowfin.com.br/senha1234, e-mail verificado) + dados (idempotente). Commitado em develop.
- GITHUB push CONFIRMADO concluído (Manager 3, 25/06): `origin/develop`=8318d05 e `main`=cb152b5 sincronizados em `https://github.com/JohnAugust934/FlowFin`. Pendência herdada do handoff 2→3 resolvida.
- Stage 5 DESPACHADO (Manager 3) em 2 frentes paralelas via worktrees: Frontend lote **5.1+5.2** (`feature/pwa-offline`, worktree `.apm/worktrees/pwa-offline`); Backend lote **5.3+5.5** (`feature/export-lgpd-hardening`, worktree `.apm/worktrees/export-lgpd-hardening`). 5.4 (UI Export/LGPD/Perfil) bloqueada até 5.3 fechar. 5.2 = requisito inegociável zero-perda-de-dados + validação guiada. Verificação holística de fim de Stage 5 prevista (offline→online real + performance).
- GIT LESSON (do not repeat): never delete/recreate a feature branch a Worker is using — a Worker commit (2.1, `5ddba2f`) was nearly lost by branch -D + recreate; recovered via the commit object. Manager edits planning docs on `develop` only; feature branches must not modify tracker/index (avoids merge conflicts via 3-way merge).

