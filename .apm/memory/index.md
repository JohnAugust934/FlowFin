---
title: FlowFin
---

# APM Memory Index

## Memory Notes

- **Preferência do usuário — proteção contra limites (handoff proativo):** o usuário pediu explicitamente que nenhum trabalho seja perdido ao se aproximar de limites. Prática permanente: (a) o Manager monitora o tamanho do próprio contexto e o dos Workers (via reports) e **avisa proativamente, antes da compactação automática**, recomendando Handoff (`/apm-6-handoff-manager` para o Manager, `/apm-7-handoff-worker` para Workers); referência ~80% de contexto usado. (b) Se um limite estourar de repente, usar `/apm-9-recover`. (c) Sempre commitar no git e atualizar o Tracker a cada Task revisada/mesclada, para que o estado esteja sempre persistido (o limite de cota de uso pausa o Claude sozinho — nada se perde, retoma após reset). O usuário é não-técnico: ao avisar, instruir o comando exato a rodar.
- **Dinheiro:** usar o helper `App\Support\Money` (`toCents`, `format`, `formatBRL`) para toda conversão R$↔centavos. Colunas monetárias são `bigInteger` (centavos); nunca decimal/float. Entrada "R$ 1.234,56" → `123456`.
- **Model `User`:** usa o estilo de atributos PHP do Breeze (`#[Fillable([...])]`, `#[Hidden([...])]`), não as propriedades `$fillable`/`$hidden`. Estender esses atributos ao adicionar campos. Já implementa `MustVerifyEmail` e tem `monthly_income` (centavos, anulável).
- **Frontend/UI:** Tailwind é **v3** — tokens no `tailwind.config.js` (não `@theme`). Telas devem usar `x-app-layout`/`x-guest-layout` para toasts/flash funcionarem (dependem de `@stack('scripts')`). Disparar toast: `$dispatch('toast', { type, message })`. Componentes do design system em `resources/views/components/` (card, button, badge, progress semafórico, toast, field, brand-logo/icon); componentes do Breeze já retematizados para a marca. App shell mobile-first com barra inferior no mobile.
- **Factories ausentes:** apenas `UserFactory` existe; as Factories das demais entidades (Category, Transaction, Budget, Goal, Recurrence, Investment, EducationalContent) **precisam ser criadas** para os feature tests (a partir da Task 2.1).
- **Tooling local:** `composer` **não** está no PATH do Git Bash — rodar via PowerShell (`C:\tools\composer\composer.bat`); `php`/`npm`/`artisan` funcionam no bash. PHP 8.5, Node 24.17. `npm run build` é **pré-requisito** dos testes que renderizam telas (`@vite`) e do deploy.
- **Worktrees (despacho paralelo):** não contêm `vendor/`/`node_modules/`/`.env` (gitignored). Bootstrap: copiar `.env` da raiz + `composer install`/`npm install` no worktree. Após mesclar mudanças de dependência frontend para a pasta principal, rodar `npm install` lá antes do build. `migrate:fresh` atinge o banco `flowfin` compartilhado.
- **Banco/produção:** MySQL local `flowfin` (creds `root`/`root`); `SESSION_DRIVER=database` (render depende do banco). Pré-requisito de produção: `pdo_mysql` habilitado no PHP do host (registrar na config de produção, Task 6.1). Laravel 13 traz migrations consolidadas (users/cache/jobs).
- **SMTP/e-mail:** validação de envio real depende das credenciais SMTP da Hostinger (passo do usuário na fase de deploy). `.env.example` já documenta as chaves (`smtp.hostinger.com`, 465/SSL ou 587/TLS). Localmente validado com `MAIL_MAILER=log`. Idioma `pt_BR` configurado (`lang/pt_BR.json` + `lang/pt_BR/{auth,passwords,validation}.php`).

## Stage Summaries

### Stage 1 - Fundação & Identidade

Os quatro Tasks da Fundação foram concluídos com sucesso, todos com status Success limpo e sem necessidade de follow-ups. A Task 1.1 (DevOps & Docs Agent) instalou o Laravel 13.8 na raiz preservando os arquivos pré-existentes (marca, CLAUDE.md, .apm), com Breeze Blade, Tailwind v3/Vite, Alpine.js e Chart.js, e `.env` para hospedagem compartilhada (cache em arquivo, fila em banco). Um achado importante — ausência de MySQL e `pdo_mysql` no ambiente local — foi resolvido em coordenação com o usuário, que instalou o MySQL Server; revalidado contra o banco real. A Task 1.2 (Frontend Agent) estabeleceu o design system (paleta azul/verde + semafóricas, Inter self-hosted via `@fontsource/inter`, gradientes), o app shell mobile-first (barra inferior no mobile, superior no desktop) e a biblioteca de componentes, com demo em `/design-system`; a validação visual foi confirmada pelo usuário em mobile e desktop. A Task 1.3 (Backend Agent) entregou o schema completo (8 entidades, dinheiro em centavos `bigInteger`, soft delete, índices em user_id/category_id/date/type) com models Eloquent e o seeder das 9 categorias, validado por `migrate:fresh --seed`. A Task 1.4 (Backend Agent) completou a autenticação localizada em PT-BR (verificação de e-mail, reset de senha), rate limiting 5/min nas rotas sensíveis (retorna 429), e edição de perfil com renda em centavos via helper `App\Support\Money`, com 34/34 testes passando.

Coordenação: 1.1 despachada sozinha (fundação); 1.2 e 1.3 em paralelo via worktrees após o merge da 1.1; 1.4 sozinha num worktree (caminho crítico do Backend) enquanto o usuário fazia a validação visual da 1.2 na pasta principal. Modelo GitFlow estabelecido (`develop` base, `main` para produção, feature branches por Task, planejamento APM versionado no git). Verificação holística de fim de Stage: build de assets + suíte de testes integrada em `develop` — exigiu `npm install` na pasta principal para sincronizar a dependência `@fontsource/inter` adicionada pela 1.2 (artefato de integração de worktree, não defeito); após isso, `npm run build` ✓ e 34/34 testes ✓. Pequeno acoplamento cross-Task: a 1.4 ajustou (aditivamente) o `toast.blade.php` da 1.2 para traduzir a flash de sessão. Commits-chave em `develop`: `e14b9ba` (scaffolding), `54ff649` (design system), `bcfb137` (modelo de dados), `24d3fe4` (auth).

**Task Logs:**
- task-01-01.log.md
- task-01-02.log.md
- task-01-03.log.md
- task-01-04.log.md

