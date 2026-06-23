---
title: FlowFin
---

# APM Tracker

## Task Tracking

**Stage 1:**

| Task | Status | Agent | Branch |
|------|--------|-------|--------|
| 1.1 | Active | devops-docs-agent | feature/scaffolding-laravel |
| 1.2 | Waiting: 1.1 | frontend-agent | |
| 1.3 | Waiting: 1.1 | backend-agent | |
| 1.4 | Waiting: 1.1, 1.3 | backend-agent | |

## Worker Tracking

| Agent | Instance | Notes |
|-------|----------|-------|
| devops-docs-agent | - | Uninitialized |
| backend-agent | - | Uninitialized |
| frontend-agent | - | Uninitialized |

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

