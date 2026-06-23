---
title: FlowFin
---

# APM Tracker

## Task Tracking

**Stage 1:**

| Task | Status | Agent | Branch |
|------|--------|-------|--------|
| 1.1 | Done | devops-docs-agent | |
| 1.2 | Done | frontend-agent | |
| 1.3 | Done | backend-agent | |
| 1.4 | Active | backend-agent | feature/auth-seguranca |

## Worker Tracking

| Agent | Instance | Notes |
|-------|----------|-------|
| devops-docs-agent | 1 | Initialized; completed Task 1.1 |
| backend-agent | 1 | Initialized; completed Task 1.3; dispatched Task 1.4 |
| frontend-agent | 1 | Initialized; completed Task 1.2 (visual validation pending User) |

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
- Task 1.2 visual validation pending User confirmation (mobile/desktop on `/design-system`); merged to develop so User can validate from main dir. Follow-up if issues.
- Backend 1.3 findings to carry forward: (a) User model uses PHP attribute style `#[Fillable]`/`#[Hidden]` — extend that, don't add `$fillable` props; (b) entity Factories don't exist yet (only UserFactory) — must be created for Task 2.1 feature tests; (c) `migrate:fresh` resets shared `flowfin` DB.
- Frontend 1.2 findings to carry forward: Tailwind v3 (tokens in `tailwind.config.js`); screens must use `x-app-layout`/`x-guest-layout` for toasts/flash (`@stack('scripts')`); toast dispatch via `$dispatch('toast',{type,message})`; Breeze shared components retemized to brand.
- Env quirk: `composer` not in Git Bash PATH — run via PowerShell (`C:\tools\composer\composer.bat`); npm works in bash.
- SMTP: real Hostinger SMTP not available yet (no hosting account confirmed). Task 1.4 validates email flows with `log` mailer; real SMTP deferred to when hosting exists (Stage 6). Pause point recorded.

