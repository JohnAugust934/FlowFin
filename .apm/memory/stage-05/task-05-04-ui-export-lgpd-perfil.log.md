---
stage: 5
task: 4
title: "UI Export + LGPD + Perfil (Frontend Agent)"
agent: frontend-agent
status: Completed
important_findings: true
compatibility_issues: false
---

# Task 5.4 — UI Export + LGPD + Perfil

## Summary
Entregue a interface dos endpoints da 5.3: **exportação de relatório mensal (CSV/PDF)**,
**export completo dos dados (LGPD)** e **exclusão definitiva da conta (LGPD)** — além do
**reskin do Perfil** para o design system claro/escuro, fechando o finding de tema pendente
da 2.7. Build OK, **160/160** testes verdes. Branch `feature/ui-export-lgpd-perfil`,
commit `7e4ada8`.

## Details
- **Perfil retematizado** (`resources/views/profile/edit.blade.php` + partials): de Breeze
  cinza/claro para o design system (`x-card`, `btn-primary/secondary/danger`, neutrals com
  variantes `dark:`). Tudo PT-BR ("Dados pessoais", "Nome", "E-mail", "Renda mensal estimada",
  "Alterar senha"). A **renda mensal estimada** já existia no form/model (centavos via
  `App\Support\Money`) — reutilizada, sem mudança de contrato.
- **Seção de relatórios** (`partials/export-data-form.blade.php` + componente Alpine
  `exportData`): seletor de mês (`type=month`, default mês atual, `max` = mês atual) +
  escolha de formato CSV/PDF, botão "Baixar relatório do mês"; e "Baixar todos os meus dados"
  (LGPD). Downloads disparados por **navegação direta** à rota autenticada (um `<a>` temporário
  com click) — exports não passam bem por fetch JSON. Feedback de loading no botão + toast
  "Preparando…". URLs: `/api/export/monthly?month=aaaa-mm&format=csv|pdf` e `/api/export/full`.
- **Exclusão de conta LGPD** (`partials/delete-user-form.blade.php` + componente Alpine
  `accountDeletion`): botão destrutivo → modal de confirmação (teleport p/ body, scrim + glass)
  avisando que é **definitiva e irreversível**, pede a **senha atual** e chama
  `DELETE /api/account` (novo método `api.deleteAccount` em `api.js`). **422** → mensagem de
  senha incorreta no campo; sucesso → toast + redireciona para `/`.
- **Feedback de salvar**: o flash de sessão (`profile-updated` / `password-updated`) já tem
  tradução PT-BR em `lang/pt_BR.json` e aparece como toast (locale do app = `pt_BR`). Mantido,
  sem mexer em controllers.

## Output
- Modificados: `resources/views/profile/edit.blade.php`,
  `profile/partials/update-profile-information-form.blade.php`,
  `profile/partials/update-password-form.blade.php`,
  `profile/partials/delete-user-form.blade.php`,
  `resources/js/flowfin/api.js`, `resources/js/flowfin/components.js`.
- Novo: `profile/partials/export-data-form.blade.php`.
- Commit: `7e4ada8` (branch `feature/ui-export-lgpd-perfil`).

## Validation
- `npm run build`: OK (app.js 82,58 KB / 26,36 KB gzip). `php artisan test`: **160/160**
  (518 asserções). Nenhuma rota/controller novo criado (endpoints já vinham da 5.3), por isso
  não houve necessidade de novos feature tests; os 9 testes da 5.3 (export/exclusão) seguem verdes.

## Important Findings
- **Setup de worktree para rodar a suíte (registrar para o time):** o worktree não tem
  `vendor/`, `.env` nem `public/build` (gitignored). Rodar testes exige: `npm run build` no
  worktree; **uma cópia real** de `vendor/` (não junction/symlink) — o Laravel 11 infere o
  `base_path` pela localização do ClassLoader do Composer, então um junction apontando para o
  vendor do repo principal faz os testes rodarem contra os arquivos do repo principal (manifest
  Vite/views errados); e um `.env` com `APP_KEY` (copiei o do repo principal). Esses artefatos
  ficam fora do commit por já estarem no `.gitignore`.
- **Decisão para o Manager — onde encaixei os acessos:** mantive tudo **dentro da página de
  Perfil** (já acessível pelo menu do avatar/≡), em seções de `x-card`: Dados → Relatórios/dados
  → Senha → Excluir conta. Evita poluir a navegação primária. Se preferir um item próprio de
  "Relatórios" no menu, é ajuste pequeno.
- **Exclusão usa o endpoint da API (`DELETE /api/account`, purge físico)**, não a rota Breeze
  `profile.destroy` (que continua existindo e testada). A UI antiga apontava para a rota Breeze;
  agora aponta para o endpoint LGPD com reautenticação e 422 claro, conforme a Task.
