# FlowFin 💙💚

**FlowFin** é um app de finanças pessoais pensado para quem quer organizar o dinheiro sem complicação: registre suas **entradas** e **saídas**, acompanhe seu mês no dashboard e crie o hábito de cuidar das finanças — direto do celular, até mesmo offline.

## ✨ Funcionalidades

- **Registro rápido de entradas e saídas** com categorias personalizáveis
- **Dashboard mensal** com resumo do mês, ranking de gastos por categoria e evolução
- **Histórico com filtros** (período, categoria, tipo) e paginação
- **Recorrências** — lançamentos que se repetem todo mês são criados automaticamente
- **Orçamentos por categoria** com alertas semafóricos ao se aproximar do limite
- **Metas de economia** e acompanhamento de progresso
- **Score financeiro, sequência de dias (streak) e dicas** para criar o hábito
- **Investimentos** com simulador de rendimento
- **Insights automáticos** sobre seus hábitos de gastos
- **Exportação de relatórios** em CSV e PDF
- **Privacidade (LGPD)** — exportação dos seus dados e exclusão definitiva de conta
- **PWA offline-first** — instale na tela inicial e registre transações mesmo sem internet; tudo sincroniza quando a conexão volta

Interface 100% em **português do Brasil**, valores em **Real (R$)**, design **mobile-first** com tema claro e escuro.

## 🛠️ Tecnologias

- **Backend:** PHP 8.3 · Laravel 13
- **Frontend:** Blade · Alpine.js · Tailwind CSS · Vite
- **PWA:** Service Worker com suporte offline e sincronização
- **Infra simples:** cache em arquivo, fila no banco de dados e um único cron (`schedule:run`) — roda em hospedagem compartilhada, sem Redis

## 🚀 Como rodar localmente

Pré-requisitos: PHP 8.3+, Composer, Node.js 20+.

```bash
git clone <url-do-repositorio> flowfin
cd flowfin

composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan migrate --seed

npm run build
php artisan serve
```

Acesse `http://localhost:8000`.

## ✅ Testes

```bash
php artisan test
```

A suíte cobre os fluxos de negócio da API (transações, dashboard, orçamentos, metas, recorrências, exportações, LGPD e mais).

## 📚 Documentação

Guias completos na pasta [`docs/`](docs/):

- [Configuração de produção](docs/01-configuracao-producao.md)
- [Guia de deploy (hospedagem compartilhada)](docs/02-guia-deploy.md)
- [Guia de uso e roadmap](docs/03-guia-uso-roadmap.md)

## 🌳 Fluxo de versionamento

- `develop` — branch de integração (base para features)
- `main` — releases de produção
- `feature/<descricao>` — uma branch por funcionalidade, a partir de `develop`

---

Feito com 💙 para descomplicar suas finanças.
