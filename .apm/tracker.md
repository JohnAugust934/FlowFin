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
| 2.3 | Active | frontend-agent | feature/ui-registro-historico |
| 2.4 | Ready | frontend-agent | |
| 2.5 | Active | frontend-agent | feature/ui-registro-historico |

## Worker Tracking

| Agent | Instance | Notes |
|-------|----------|-------|
| devops-docs-agent | 1 | Initialized; completed Task 1.1 (next work in Stage 6) |
| backend-agent | 1 | Completed 1.3, 1.4, 2.1, 2.2; idle (Stage 2 backend done) |
| frontend-agent | 1 | Completed 1.2; dispatched batch 2.3+2.5 |

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
- Wait state: Frontend busy with batch 2.3+2.5 (worktree ui-registro-historico). 2.4 Ready, dispatch to Frontend once the batch report returns. Backend idle. Next action: User returns Frontend batch report (`/apm-5-check-reports`).
- GIT LESSON (do not repeat): never delete/recreate a feature branch a Worker is using — a Worker commit (2.1, `5ddba2f`) was nearly lost by branch -D + recreate; recovered via the commit object. Manager edits planning docs on `develop` only; feature branches must not modify tracker/index (avoids merge conflicts via 3-way merge).

