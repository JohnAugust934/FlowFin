---
title: FlowFin
---

# APM Tracker

## Task Tracking

**Stage 1:**

| Task | Status | Agent | Branch |
|------|--------|-------|--------|
| 1.1 | Done | devops-docs-agent | |
| 1.2 | Active | frontend-agent | feature/design-system |
| 1.3 | Active | backend-agent | feature/modelo-dados |
| 1.4 | Waiting: 1.3 | backend-agent | |

## Worker Tracking

| Agent | Instance | Notes |
|-------|----------|-------|
| devops-docs-agent | 1 | Initialized; completed Task 1.1 |
| backend-agent | 1 | Dispatched Task 1.3 (first dispatch — initialize) |
| frontend-agent | 1 | Dispatched Task 1.2 (first dispatch — initialize) |

## Version Control

| Repository | Base Branch | Branch Convention | Commit Convention |
|-----------|-------------|-------------------|-------------------|
| FlowFin (`C:\_PROJETOS\FlowFin`) | develop | `feature/<descrição>` off develop; `main` reserved for production releases (GitFlow) | `tipo: descrição` (feat, fix, refactor, docs, test, chore) |

## Working Notes

- GitHub remote (`https://github.com/JohnAugust934/FlowFin`) not connected yet; deferred to Task 6.2 deploy, requires guided non-technical User action. No pushes by default.
- `.apm/` planning docs are tracked in git per User choice; `.apm/worktrees/` and `.apm/bus/` are gitignored.
- Deadline 02/07/2026 (~9 days). Contingency: if time runs short, declare Stage 2 (MVP) the main deliverable and document the rest in the roadmap (Task 6.3). User priority: finish quality over quantity.
- Holistic end-to-end verification planned at end of Stage 2 (MVP usable flow) and Stage 5 (offline sync).
- User is non-technical: SMTP credentials (1.4), GitHub/domain/SSL/cron (Stage 6), and guided visual validations need step-by-step lay-language instructions and explicit pause points.
- Local env (Task 1.1 finding): MySQL local now working (service active, db `flowfin`, creds root/root); `pdo_mysql` enabled in PHP. PHP 8.5, Composer 2.10, Node 24.17. `SESSION_DRIVER=database` → page render needs DB. Laravel 13 has consolidated migrations (users/cache/jobs by default). Prod prerequisite to record for Task 6.1: PHP `pdo_mysql` must be enabled on the host.
- Worktree bootstrap pattern: worktrees lack `vendor/`, `node_modules/`, `.env` (gitignored). Parallel Workers must copy `.env` from project root and run `composer install`/`npm install` in the worktree. `migrate:fresh` hits the shared local `flowfin` DB — fine pre-data.
- Active parallel dispatch: 1.2 (frontend, worktree design-system) + 1.3 (backend, worktree modelo-dados).

