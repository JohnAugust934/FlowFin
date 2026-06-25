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

**Stage 5:** Complete

| Task | Status | Agent | Branch |
|------|--------|-------|--------|
| 5.1 | Done (merged) | frontend-agent | feature/pwa-offline |
| 5.2 | Done (merged) | frontend-agent | feature/pwa-offline |
| 5.3 | Done (merged) | backend-agent | feature/export-lgpd-hardening |
| 5.4 | Done (merged) | frontend-agent | feature/ui-export-lgpd-perfil |
| 5.5 | Done (merged) | backend-agent | feature/export-lgpd-hardening |

**Stage 6:** Complete (docs/config entregues; deploy de produção real = ação do usuário pendente)

| Task | Status | Agent | Branch |
|------|--------|-------|--------|
| 6.1 | Done (merged) | devops-docs-agent | feature/deploy-docs |
| 6.2 | Done docs (merged); validação = usuário publicar em produção | devops-docs-agent | feature/deploy-docs |
| 6.3 | Done (merged) | devops-docs-agent | feature/deploy-docs |

**Stage 7:** In Progress (extensão pós-MVP solicitada pelo usuário 25/06)

| Task | Status | Agent | Branch |
|------|--------|-------|--------|
| 7.1 | Dispatched | frontend-agent | feature/landing-page |
| 7.2 | Dispatched | devops-docs-agent | feature/readme |
| (features futuras) | Aguardando priorização do usuário | — | — |

## Worker Tracking

| Agent | Instance | Notes |
|-------|----------|-------|
| devops-docs-agent | 1 | Completed 1.1, 6.1, 6.2, 6.3 |
| backend-agent | 1 | Completed 1.3, 1.4, 2.1, 2.2, 2.6, 3.1, 3.2, 4.1, 4.2, 5.3, 5.5 (+idempotência) |
| frontend-agent | 1 | Completed 1.2, 2.3, 2.4, 2.5, 2.7, 3.3, 3.4, 5.1, 5.2, 5.4 (+ícones PNG, fix offline) |

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
- **EXTENSÃO PÓS-MVP (usuário, 25/06):** Stages 1–6 entregues, mas o usuário decidiu NÃO encerrar — quer adicionar: (a) tela de apresentação/landing [7.1 despachada], (b) README [7.2 despachada], (c) `main` como branch default no GitHub, (d) entregar as features do roadmap diferido (login Google, passkeys, import CSV, multi-moeda, Open Finance) + lacuna recorrências. Priorização das features pedida ao usuário (Stage 8+ a planejar). Prazo 02/07.
- **MAIN/release PENDENTE (ação do usuário):** merge `develop`→`main` + push bloqueado pelo classificador (release de produção). Delegado ao usuário rodar `! git checkout main && git merge --no-ff develop && git push origin main && git checkout develop`; depois definir `main` como default no GitHub (UI). main local ainda em cb152b5 (não mesclada).
- GITHUB push CONFIRMADO concluído (Manager 3, 25/06): `origin/develop`=8318d05 e `main`=cb152b5 sincronizados em `https://github.com/JohnAugust934/FlowFin`. Pendência herdada do handoff 2→3 resolvida.
- Stage 5 DESPACHADO (Manager 3) em 2 frentes paralelas via worktrees: Frontend lote **5.1+5.2** (`feature/pwa-offline`); Backend lote **5.3+5.5** (`feature/export-lgpd-hardening`). 5.2 = requisito inegociável zero-perda-de-dados + validação guiada. Verificação holística de fim de Stage 5 prevista (offline→online real + performance).
- **5.3+5.5+idempotência MESCLADOS em develop** (commit merge 3f84a85; branch/worktree removidos). Verificado: composer install (DomPDF), migrate, build, **160/160 testes (518 asserções)**. Endpoints novos (UI 5.4 consome): `GET /api/export/monthly?month=&format=csv|pdf`, `GET /api/export/full` (JSON centavos), `DELETE /api/account` (body `{password}`, reautenticação, **purge físico** LGPD). `POST /api/transactions` aceita `client_uuid` opcional → reenvio do mesmo (mesmo user) devolve a existente (200), unique composto `(user_id, client_uuid)`. Logs do Stage 5 ficaram no repo principal (Workers gravaram via caminho absoluto) — commitados em develop pelo Manager.
- **Frontend 5.1+5.2 + follow-up ícones PNG**: código pronto/verde (145/145) na branch `feature/pwa-offline` (worktree ativo), **Partial** aguardando validação guiada do usuário no celular (instalar/offline/registrar offline→sincronizar sem duplicar; e ícone correto no iPhone/Android). NÃO mesclar até OK do usuário.
- **5.2+ fix offline ACEITO** (commit `eccf5a3` na branch `feature/pwa-offline`): cache persistente de categorias em `localStorage` (`flowfin:categories`) + `loadCategories` resiliente offline; worker reproduziu o fluxo no DevTools offline; 145/145. Branch `pwa-offline` NÃO mesclada em develop ainda — aguardando re-validação do usuário em dispositivo real (zero-perda-de-dados exige).
- **OFFLINE APROVADO pelo usuário** (testou no celular, funcionou). Resta só validar 2 itens visuais antes de fechar o Stage 5: a barra inferior ajustada e as telas da 5.4.
- **5.2++ barra inferior (Manager fez direto na branch `pwa-offline`):** no PWA iOS a bottom-nav ficava colada na borda → conflito com o gesto de voltar à tela inicial. Fix: `pb-[calc(env(safe-area-inset-bottom)+0.75rem)]` na nav + `pb-28` no `<main>`. Build ✓. Aguarda confirmação visual do usuário no iPhone.
- **5.4 REVIEWED-SUCCESS** (commit `7e4ada8`, branch `feature/ui-export-lgpd-perfil`): Perfil retematizado dark/light (fecha finding 2.7), seção de relatórios (CSV/PDF por mês + export completo LGPD via navegação direta), exclusão de conta LGPD (modal+senha, `DELETE /api/account`, 422 claro). 160/160. Decisões aceitas: acessos dentro do Perfil (sem novo item de nav); exclusão usa endpoint LGPD (não a rota Breeze). NÃO mesclada — aguarda validação guiada do usuário (baixar CSV/PDF/JSON; excluir conta de teste; temas claro/escuro; salvar perfil).
- **Branch de teste `teste/estado-atual` RE-PUBLICADA** (commit `33fce1a`) = develop + pwa-offline (offline+PNG+barra) + 5.4. Conflito de merge em `components.js` resolvido pelo Manager (mantidos os 4 componentes Alpine: offlineSync, exportData, pwaInstall, accountDeletion); api.js auto-merge ok; build ✓. **MERGE EM DEVELOP PENDENTE** — após OK do usuário, mesclar `feature/pwa-offline` e depois `feature/ui-export-lgpd-perfil` em develop (o 2º merge terá o MESMO conflito em components.js — resolver mantendo os 4 componentes), fechar 5.1/5.2/5.4, remover worktrees/branches. NÃO mesclar a branch de teste em develop (ela tem `public/build` forçado).
- **5.4 DESPACHADA** (Frontend, branch `feature/ui-export-lgpd-perfil`, worktree próprio): UI export (CSV/PDF + dados completos), exclusão de conta (senha/confirmação), Perfil dark (renda; fecha finding 2.7). Consome endpoints 5.3. Roda em paralelo à re-validação do offline.
- **DEPLOY DE TESTE (Hostinger, subdomínio + SSH + SSL):** usuário fez o 1º deploy com sucesso a partir da branch **`teste/estado-atual`** (= develop + pwa-offline, com `public/build` commitado à força só nessa branch p/ dispensar Node no servidor). Validação visual OK. `php artisan storage:link` falha na Hostinger (`exec()` desabilitada) — IRRELEVANTE p/ FlowFin; anotar no guia 6.1 p/ criar o symlink de outro jeito. Login de teste: seeder TestDataSeeder (teste@flowfin.com.br/senha1234).
- **BUG OFFLINE encontrado no teste real (5.2):** offline, o registro de transação fica bloqueado porque o **seletor de categorias carrega só pela rede** (cache só em memória) → lista vazia offline → `canSave` falso (exige category_id). A escrita/fila offline em si está correta. Follow-up 5.2+ despachado: cache persistente de categorias (localStorage/IndexedDB) + loadCategories resiliente offline. Arquivos: `resources/js/flowfin/components.js` (form quick-add, `ensureCategories`/`canSave`) e `api.js`.
- GIT LESSON (do not repeat): never delete/recreate a feature branch a Worker is using — a Worker commit (2.1, `5ddba2f`) was nearly lost by branch -D + recreate; recovered via the commit object. Manager edits planning docs on `develop` only; feature branches must not modify tracker/index (avoids merge conflicts via 3-way merge).

