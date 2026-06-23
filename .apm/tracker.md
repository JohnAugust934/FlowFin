---
title: FlowFin
---

# APM Tracker

## Task Tracking

**Stage 1:** Complete

**Stage 2:**

| Task | Status | Agent | Branch |
|------|--------|-------|--------|
| 2.1 | Active | backend-agent | feature/api-transacoes |
| 2.2 | Waiting: 2.1 | backend-agent | |
| 2.3 | Waiting: 2.1 | frontend-agent | |
| 2.4 | Waiting: 2.2 | frontend-agent | |
| 2.5 | Waiting: 2.1 | frontend-agent | |

## Worker Tracking

| Agent | Instance | Notes |
|-------|----------|-------|
| devops-docs-agent | 1 | Initialized; completed Task 1.1 (next work in Stage 6) |
| backend-agent | 1 | Initialized; completed 1.3, 1.4; dispatched 2.1 |
| frontend-agent | 1 | Initialized; completed 1.2 (validated by User) |

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
- Stage 2 dispatch: 2.1 (API transações/categorias) sequential in main working dir (vendor/node_modules/.env present — no worktree bootstrap). 2.1 unblocks Frontend 2.3+2.5 (parallel) and Backend 2.2; dispatched alone (not batched with 2.2) to unblock Frontend sooner. 2.1 must create entity Factories (needed for its own feature tests + downstream).
- 2.1 produces the JSON API contract consumed by Frontend (2.3, 2.5) and the offline layer (5.2) — keep payload stable and documented.

