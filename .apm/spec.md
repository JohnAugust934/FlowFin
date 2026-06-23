---
title: FlowFin
modified: Spec creation by the Planner.
---

# APM Spec

## Overview

FlowFin é um web app de controle financeiro pessoal, mobile-first, voltado a pessoas leigas em finanças. O problema central é a falta de consciência e controle de gastos do público comum: o app resolve isso com registro de transações extremamente simples, visualização clara de para onde vai o dinheiro, ferramentas de economia e mecanismos de construção de hábitos financeiros. O escopo completo cobre cinco pilares — Simplicidade, Consciência, Economia, Mentalidade e Direcionamento — mais a infraestrutura de base (autenticação, perfil, PWA, export, LGPD). Sucesso é definido como todas as funcionalidades planejadas funcionando, prontas para produção na Hostinger, atendendo os padrões de qualidade inegociáveis (performance mobile, zero perda de dados, simplicidade, feedback visual, consistência visual). Projeto pessoal (~50 usuários), construído integralmente pelo Claude Code para um usuário não-técnico, com prazo-alvo 02/07/2026.

## Workspace

- **Diretório raiz:** `C:\_PROJETOS\FlowFin\` — repositório de trabalho único (working target). Projeto novo, sem código-fonte preexistente e sem repositório git inicializado.
- **Assets de marca:** `logo_flowfin.svg` e `icon_flowfin.svg` na raiz do projeto. Devem ser reorganizados para a estrutura de assets públicos da aplicação durante a implementação (ex.: `public/img/brand/`), preservando os arquivos originais.
- **Sem `CLAUDE.md` preexistente** — será criado durante o Work Breakdown com o bloco APM_RULES.
- **APM:** `.apm/` contém os documentos de planejamento (Spec, Plan, Tracker, Memory Index) e `.claude/` contém guias, comandos e skills do framework.
- **Sem documentos de requisitos externos** — todos os requisitos foram capturados via Context Gathering e residem nesta Spec e no Plan.

---

> **Notes:** Observações sobre o ambiente que o Manager vai encontrar:
> - **Controle de versão não inicializado.** Não há repositório git. O Manager estabelece as convenções de versionamento no início da Implementation Phase. A Hostinger compartilhada suporta deploy via Git (push-to-deploy) ou FTP.
> - **Preferência de versionamento do usuário:** aplicar **GitFlow** no projeto e versionar tudo no repositório GitHub do usuário — `https://github.com/JohnAugust934/FlowFin`. O usuário é não-técnico; conectar o remoto e qualquer autenticação no GitHub exigem orientação passo a passo. O Manager define o fluxo de branches GitFlow e o registra nas Regras no início da Implementation Phase.
> - **Usuário não-técnico.** O usuário não programa e não lê código. Toda etapa que exige ação dele em plataformas externas (criar conta/plano na Hostinger, registrar domínio `flowfin.com.br`, gerar credenciais SMTP do e-mail Hostinger, configurar o cron único, e futuramente credenciais Google) precisa de instruções passo a passo explícitas, em linguagem leiga. A validação visual depende dele testar o app e confirmar comportamento.
> - **Prazo e prioridade.** Prazo-alvo 02/07/2026, escopo completo. O usuário priorizou explicitamente **acabamento sobre quantidade**: se o tempo apertar, entregar menos funcionalidades porém bem-acabadas e produzir um roadmap documentado do restante. O conjunto "infraestrutura base + Simplicidade + Consciência" forma o núcleo utilizável mais cedo.
> - **Hospedagem compartilhada Hostinger** impõe limites de recursos (sem Redis, sem worker persistente). As decisões de runtime já refletem isso (cache em arquivo, fila no banco via scheduler, cron único).

## Princípios de Produto

O produto é guiado por cinco pilares e cinco padrões de qualidade inegociáveis. Toda decisão de design subordina-se a eles.

**Cinco pilares:**
1. **Simplicidade** — pessoa leiga usa sem medo.
2. **Consciência** — saber exatamente para onde vai o dinheiro.
3. **Economia** — identificar onde cortar ou reduzir.
4. **Mentalidade** — construir hábitos financeiros saudáveis.
5. **Direcionamento** — gastar/investir no que realmente importa.

**Padrões de qualidade inegociáveis** (aplicam-se a todo o produto):
- **Performance mobile (nº 1):** dashboard carrega em menos de 2–3 segundos no celular; rolagem e registro fluidos; desktop impecável.
- **Zero perda de dados:** soft delete em tudo; backup do banco; feedback explícito de salvamento; preservação offline e sincronização ao reconectar.
- **Simplicidade:** registrar transação em no máximo 3 toques (valor → categoria → salvar); sem jargão contábil; sem telas lotadas.
- **Feedback visual imediato:** toda ação confirmada visualmente (salvou/deletou/editou/atingiu limite); estados de loading visíveis.
- **Consistência visual:** paleta, tipografia e estilo idênticos em todas as telas.

## Escopo

### No escopo (projeto completo, organizado por pilar)

**Infraestrutura base:** cadastro/login e-mail+senha, verificação de e-mail, recuperação de senha; perfil do usuário (nome, renda mensal estimada); export CSV/PDF; PWA instalável; responsivo mobile-first; LGPD (exportar e excluir conta/dados); backups.

**Pilar 1 — Simplicidade:** dashboard do mês (entrou/saiu/sobrou); registro de transação em ≤3 toques; categorias pré-definidas com ícones/cores; categorias personalizadas; linguagem humana (entrada/saída).

**Pilar 2 — Consciência:** resumo por categoria com gráfico de rosca; histórico com filtros (período, categoria, tipo); linha do tempo (barras dia a dia); top 3 maiores gastos; comparativo mês a mês; transações recorrentes (contas fixas).

**Pilar 3 — Economia:** orçamento por categoria com alertas 80%/100%; detector de gastos "invisíveis" (assinaturas/recorrentes); relatório "Onde economizar"; meta de economia mensal com progresso.

**Pilar 4 — Mentalidade:** classificação Necessidade vs. Desejo; Score FlowFin (0–100); dicas contextuais; streak de registro; mini-conteúdos educativos.

**Pilar 5 — Direcionamento:** metas financeiras com propósito (nome, valor-alvo, prazo); simulador simples de economia; visão "Prioridades"; registro simplificado de investimentos.

### Diferido para o futuro (não construir agora; documentar no roadmap)

Login social com Google (OAuth); autenticação por passkeys; importação de extratos CSV; multi-idioma e multi-moeda; integração bancária / Open Finance; IA para sugestões (além de regras simples).

## Arquitetura e Stack Técnica

| Camada | Decisão | Racional |
|---|---|---|
| Backend | **PHP 8+ com Laravel** | Framework robusto e bem documentado; adequado à Hostinger. |
| Autenticação | **Laravel Breeze** (Blade) | Pronto e leve (registro, login, verificação de e-mail, recuperação de senha). Mais leve que Jetstream. |
| Banco de dados | **MySQL 8** | Nativo na Hostinger; confiável e performático para o volume previsto. |
| UI | **Blade + Tailwind CSS + Alpine.js** | Leve e rápido no mobile. Alpine escolhido em vez de Livewire por ser mais leve e por compatibilidade com o modelo offline (Livewire depende de round-trips ao servidor). |
| Gráficos | **Chart.js** | Rosca (categorias), barras (fluxo diário), linha (evolução). |
| Build de assets | **Laravel Vite** | Minificação de CSS/JS. |
| Dados dinâmicos / offline | **Endpoints JSON internos** | Operações críticas (CRUD de transações, dados do dashboard) expostas como JSON para permitir enfileiramento e sincronização offline pelo PWA. |

**Arquitetura offline-first para escrita de transações:** o registro de transação é a operação mais crítica e não pode falhar por falta de conexão. O fluxo de criação/edição de transação passa por endpoints JSON autenticados por sessão. Quando offline, o cliente persiste a operação localmente (IndexedDB) numa fila e a sincroniza automaticamente ao reconectar, com feedback visual de estado (pendente/sincronizado). O Service Worker do PWA faz cache da casca do app para carregamento rápido e funcionamento offline da interface.

## Runtime e Restrições de Hospedagem (Hostinger compartilhada)

A hospedagem compartilhada não oferece Redis nem worker de fila persistente. As decisões abaixo são obrigatórias:

- **Cache:** driver de arquivo (`file`). Sem Redis.
- **Filas:** driver de banco de dados (`database`). Jobs em segundo plano (gerar relatórios, enviar e-mails, recalcular agregados) são processados por um comando disparado pelo scheduler, não por worker contínuo.
- **Tarefas agendadas:** um único cron na Hostinger apontando para o scheduler do Laravel (`schedule:run`). O scheduler dispara: cálculo de Score mensal, reset de streaks, resumos periódicos, processamento da fila.
- **Otimização obrigatória:** eager loading (evitar N+1); cache dos agregados de dashboard/resumos mensais (invalidado em nova transação); paginação (20 itens por página, nunca carregar listas inteiras); compressão Gzip via `.htaccess`; SSL/HTTPS (Let's Encrypt da Hostinger).

## Modelo de Dados e Integridade

Decisões que valem para todo o modelo de dados:

- **Valores monetários armazenados como inteiro em centavos** (ex.: R$ 12,34 → `1234`). Nunca usar tipo decimal/float para dinheiro. Formatação para R$ apenas na exibição.
- **Soft delete** em todas as entidades que o usuário pode "apagar" (transações, categorias, metas, etc.) — nada é removido de fato sem intenção explícita.
- **Índices** em colunas de consulta frequente: `user_id`, `category_id`, `date`, `type` (entrada/saída).
- **Migrations e Seeders:** schema versionado por migrations; categorias pré-definidas populadas por seeder.
- **Isolamento por usuário:** toda consulta de dados financeiros é escopada ao usuário autenticado (`user_id`).

**Categorias pré-definidas** (seeder, com ícone e cor): Moradia, Alimentação, Transporte, Saúde, Lazer, Educação, Assinaturas, Compras, Outros. O usuário pode criar categorias personalizadas.

**Transação:** possui tipo (entrada/saída), valor (centavos), categoria, data, descrição opcional, classificação Necessidade vs. Desejo (para saídas), e marcação de recorrência (para contas fixas).

## Localização

- Idioma **100% português do Brasil**.
- Moeda **Real (R$)** apenas; formato brasileiro: ponto separador de milhar, vírgula decimal (ex.: `R$ 1.234,56`).
- Datas no formato **dd/mm/aaaa**.
- Fuso horário e início de mês conforme calendário brasileiro.

## Autenticação e Segurança

- **Laravel Breeze**: registro, login, verificação de e-mail, recuperação de senha.
- **E-mail via SMTP** do servidor de e-mail da Hostinger (recuperação de senha, verificação, alertas, resumos).
- Proteções nativas do Laravel: CSRF, XSS, SQL Injection.
- **Rate limiting** nas rotas sensíveis (login, registro, recuperação) contra abuso.
- **LGPD:** o usuário pode exportar todos os seus dados e excluir a própria conta com seus dados.

## Identidade Visual e Padrões de UX

- **Paleta:**
  - Azul (confiança/estabilidade): gradiente `#2563EB` → `#1E3A8A`.
  - Verde esmeralda (crescimento/prosperidade): gradiente `#10B981` → `#059669`.
  - Cinza neutro (texto secundário): `#6B7280`.
  - Cores semafóricas para orçamentos/metas: verde (ok), amarelo (atenção ~80%), vermelho (estourado ~100%).
- **Tipografia:** fonte **Inter** — peso 800 na marca, pesos variados na interface.
- **Marca:** ícone = duas curvas (azul + verde) com seta direcional; logo = ícone + "FlowFin" + subtítulo "CONTROLE FINANCEIRO". Arquivos SVG já existentes.
- **Estilo:** clean, minimalista, mobile-first, sem poluição visual.
- **Padrões de UX obrigatórios:** registro em ≤3 toques; feedback visual em toda ação; barras de progresso para metas/orçamentos; comparativos com setas de variação (↑/↓ %); linguagem humana (entrada/saída, sem jargão contábil).

## Regras de Negócio

Estas regras definem comportamento do produto. Foram propostas pelo Planner com base em boas práticas de educação financeira e estão sujeitas à revisão do usuário.

**Score FlowFin (0–100), calculado mensalmente:** média ponderada de três fatores, cada um normalizado de 0 a 100:
- **Consistência de registro (peso 40%):** proporção de dias do mês com ao menos um registro de transação.
- **Orçamentos respeitados (peso 30%):** proporção de categorias com orçamento definido que terminaram o mês dentro do limite. (Se nenhum orçamento definido, fator neutro/excluído da média.)
- **Progresso na meta de economia (peso 30%):** percentual da meta de economia mensal efetivamente atingido (limitado a 100%).

**Streak de registro:** número de dias consecutivos com ao menos um registro; reseta quando um dia passa sem registro. Reset processado pelo scheduler.

**Alertas de orçamento:** aviso visual ao atingir 80% (amarelo) e 100%+ (vermelho) do limite mensal por categoria; opcionalmente notificação por e-mail ao estourar.

**Detector de gastos "invisíveis":** agrupa transações recorrentes e assinaturas, somando o impacto mensal combinado para evidenciar o peso conjunto.

**Relatório "Onde economizar":** identifica as maiores categorias de saída classificadas como "Desejo" e os gastos recorrentes, e sugere cortes percentuais com o valor economizado correspondente (ex.: "Reduza 30% em delivery → economiza R$ X/mês"). Regras determinísticas, sem ML.

**Classificação Necessidade vs. Desejo:** ao registrar uma saída, o usuário marca necessidade ou desejo; o resumo mensal mostra o percentual de cada um.

**Simulador:** cálculo básico "guardando R$ X por mês, atinge R$ Y em Z meses".

**Conteúdo educativo:** mini-conteúdos (1 parágrafo) e dicas contextuais escritos pelo Planner/implementação, baseados no comportamento do usuário (ex.: streak ativo, gasto acima do mês anterior). Inclui temas como reserva de emergência e regra 50-30-20.

## PWA e Sincronização Offline

- **PWA instalável:** manifest, ícones, Service Worker com cache da casca do app.
- **Offline-first para escrita de transações:** operações de transação enfileiradas localmente (IndexedDB) quando offline, sincronizadas ao reconectar, com feedback de estado.
- **Confirmação de salvamento:** o usuário sempre vê confirmação explícita ("Transação registrada ✓") e, quando aplicável, o estado de sincronização pendente.

## Export de Dados e LGPD

- **Export de relatório mensal** em CSV e PDF (ex.: DomPDF / Laravel Excel).
- **Export completo de dados** do usuário (LGPD).
- **Exclusão de conta e dados** pelo próprio usuário (LGPD).

## Requisitos de Performance

- Dashboard carrega em **< 2–3 s** no celular.
- Agregados de dashboard e resumos mensais **cacheados** (invalidados ao registrar/editar transação).
- **Paginação** de 20 itens; nunca carregar listas inteiras.
- **Eager loading** para evitar N+1; índices conforme o modelo de dados.
- Assets minificados (Vite) e Gzip habilitado.

## Entregáveis de Documentação

Ao final, além do app funcionando:
1. **Guia de deploy passo a passo** na Hostinger (em linguagem leiga).
2. **Guia rápido de uso** do app.
3. **Roadmap** das funcionalidades não concluídas e do escopo diferido.
4. **`CLAUDE.md`** com padrões de execução (criado no Work Breakdown).
