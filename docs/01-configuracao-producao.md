# FlowFin — Configuração de Produção (Hostinger)

Este documento explica **como o FlowFin deve ficar configurado no servidor** para
funcionar em produção numa hospedagem compartilhada da Hostinger (sem Redis e sem
processos em segundo plano permanentes). É a base do **Guia de Deploy** (documento 02);
aqui o foco é *o que* precisa estar configurado e *por quê*. Lá é o *passo a passo*.

> Sempre que aparecer **"👤 Passo do usuário"**, é uma ação que **você** precisa fazer
> no painel da Hostinger (hPanel) ou em outra plataforma — não é algo automático.

---

## 1. Arquivo de configuração (`.env`)

O FlowFin lê todas as suas configurações de um arquivo chamado `.env` que fica na raiz
do projeto no servidor. Use o modelo **`.env.production.example`** (incluído no projeto)
como ponto de partida: copie-o para `.env` e preencha os campos marcados com `<< ... >>`.

Os pontos que **obrigatoriamente** mudam em relação ao ambiente de desenvolvimento:

| Item | Valor de produção | Por quê |
|------|-------------------|---------|
| `APP_ENV` | `production` | Liga o modo de produção (otimizações, sem telas de erro detalhadas). |
| `APP_DEBUG` | `false` | **Segurança:** evita expor detalhes internos e dados em telas de erro. |
| `APP_URL` | `https://seudominio` | Precisa ser o endereço real **com HTTPS** — o PWA depende disso. |
| `APP_KEY` | (gerado no servidor) | Chave de criptografia; gere com `php artisan key:generate`. |
| `DB_*` | dados do MySQL da Hostinger | Banco de produção criado no hPanel. |
| `SESSION_DRIVER` | `database` | Sessões no banco (não dependem de Redis). |
| `QUEUE_CONNECTION` | `database` | Fila no banco — exigência da hospedagem compartilhada. |
| `CACHE_STORE` | `file` | Cache em arquivo — não há Redis. |
| `MAIL_*` | SMTP da Hostinger | Necessário para a verificação de e-mail no cadastro. |
| `LOG_LEVEL` | `warning` | Mantém os logs enxutos para não encher o disco. |

> ⚠️ **Nunca** ative Redis ou um worker de fila permanente: o plano de hospedagem não
> tem esses recursos e o FlowFin foi feito para não precisar deles.

---

## 2. Banco de dados (MySQL)

👤 **Passo do usuário:** no hPanel, em **Bancos de Dados → MySQL**, crie um banco e um
usuário, anote o **nome do banco**, o **usuário** e a **senha**, e preencha em `DB_DATABASE`,
`DB_USERNAME` e `DB_PASSWORD` no `.env`. O `DB_HOST` normalmente é `127.0.0.1` (localhost).

O PHP do servidor precisa da extensão **`pdo_mysql`** habilitada (em geral já vem ligada
nos planos da Hostinger). As tabelas são criadas pela migração (`php artisan migrate`),
detalhada no documento 02.

---

## 3. HTTPS / SSL — obrigatório

O FlowFin é um **PWA** (aplicativo instalável que funciona offline). Os recursos de
instalação e o funcionamento offline **só ligam em conexões HTTPS** — é uma exigência dos
navegadores, não do FlowFin. Sem SSL, o site abre, mas **não instala e não funciona offline**.

👤 **Passo do usuário:** no hPanel, em **Segurança → SSL**, ative o certificado **Let's
Encrypt** (gratuito) para o seu domínio ou subdomínio. Depois force o redirecionamento de
`http` para `https` (a Hostinger tem essa opção; o `.htaccess` do FlowFin já trata o resto).

Confirme que `APP_URL` no `.env` começa com `https://`.

---

## 4. Pasta pública (document root)

O FlowFin é um projeto Laravel: **o site deve apontar para a pasta `public/`** do projeto,
nunca para a raiz. Apontar para a raiz expõe arquivos internos e quebra o app.

👤 **Passo do usuário:** ao criar o domínio/subdomínio no hPanel, defina a pasta do site
(document root) como a pasta `public` de onde o FlowFin foi instalado, por exemplo:

```
domains/SEUDOMINIO/public_html/flowfin/public
```

---

## 5. Compressão e cache de assets (`.htaccess`)

O arquivo `public/.htaccess` do FlowFin já vem preparado para produção com:

- **Compressão Gzip** (`mod_deflate`): reduz o tamanho de HTML, CSS, JS, JSON e SVG na rede.
- **Cache longo (1 ano) dos assets do Vite** (`mod_expires`): os arquivos de CSS/JS são
  versionados com um código único no nome a cada build, então podem ser cacheados por muito
  tempo com segurança — quando o conteúdo muda, o nome muda e o navegador baixa a versão nova.
- **Sem cache para o Service Worker e o manifesto do PWA**: esses precisam atualizar na hora,
  senão o app instalado fica preso numa versão antiga.

Os blocos estão protegidos por `<IfModule ...>`, então **se algum módulo não existir no
servidor, eles são simplesmente ignorados** — não quebram o site. Os módulos `mod_deflate`,
`mod_expires` e `mod_headers` estão ativos por padrão nos planos da Hostinger.

---

## 6. Build dos assets de produção (Vite)

As telas do FlowFin usam arquivos de CSS/JS gerados pelo **Vite** (o comando `npm run build`).
Esses arquivos ficam em `public/build/` e **precisam existir no servidor**, senão as telas
não carregam o visual.

Como o **Node.js não é confiável na hospedagem compartilhada**, a estratégia recomendada
(e já validada no deploy de teste) é:

> **Gerar o build no seu computador e enviar a pasta `public/build` junto com o deploy.**

Isso dispensa rodar Node no servidor. O passo a passo (incluindo como versionar o
`public/build` na branch de publicação) está no documento 02. A alternativa de rodar
`npm run build` no próprio servidor só vale se o seu plano tiver Node disponível e estável —
para um fluxo à prova de imprevistos, prefira buildar localmente.

---

## 7. Tarefas agendadas e fila (cron único)

O FlowFin tem **uma** rotina automática diária: recalcular a "sequência" (🔥 *streak*) de
dias com registro de cada usuário, **efetivando o reset** de quem deixou um dia passar sem
registrar. Isso roda às **00:10** todo dia.

Numa hospedagem compartilhada não existe processo permanente para isso. A solução padrão do
Laravel é um **cron único** que chama o agendador a cada minuto; o Laravel decide o que rodar:

```
* * * * * cd /caminho/para/o/flowfin && php artisan schedule:run >> /dev/null 2>&1
```

A rotina é despachada para a **fila no banco** (`QUEUE_CONNECTION=database`) e processada por
esse mesmo cron — por isso `QUEUE_CONNECTION` precisa ser `database` no `.env`.

👤 **Passo do usuário:** no hPanel, em **Avançado → Cron Jobs**, crie **um** cron job que roda
**a cada minuto** com o comando acima (ajustando o caminho real do FlowFin no servidor). O
passo a passo com telas está no documento 02.

> É **um cron só**. Não crie vários crons para tarefas diferentes — o `schedule:run` cuida de
> tudo. Você pode validar que está agendado com `php artisan schedule:list`.

---

## 8. Symlink de storage — pode pular

Em muitos projetos Laravel roda-se `php artisan storage:link`. Na Hostinger **esse comando
falha**, porque a função `exec()` do PHP fica desabilitada por segurança (erro do tipo
*"Call to undefined function ... exec()"*).

**Para o FlowFin isso não é problema:** o app **não serve arquivos enviados por usuários**
(uploads públicos), então o symlink de storage **não é necessário** — pode pular esse passo
sem nenhum efeito. Caso, no futuro, o app passe a servir uploads públicos, o symlink pode ser
criado manualmente pelo Gerenciador de Arquivos do hPanel ou via `ln -s`.

---

## 9. Otimizações de produção (cache de config/rotas/views)

Depois de configurar o `.env`, rode no servidor (uma vez a cada deploy):

```
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Isso deixa o app mais rápido. **Importante:** sempre que você mudar o `.env`, rode de novo
`php artisan config:cache` (ou `php artisan config:clear` antes), senão a mudança não é vista.

---

## 10. Checklist de produção (resumo verificável)

- [ ] `.env` criado a partir de `.env.production.example`, com `APP_ENV=production` e `APP_DEBUG=false`.
- [ ] `APP_URL` começa com `https://` e o **SSL está ativo** (Let's Encrypt).
- [ ] Banco MySQL criado e preenchido em `DB_*`; `pdo_mysql` habilitado.
- [ ] `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=file`.
- [ ] `MAIL_*` com a caixa de e-mail SMTP da Hostinger (para verificação de cadastro).
- [ ] Document root do site aponta para a pasta **`public`** do FlowFin.
- [ ] `public/build` presente no servidor (build gerado localmente e enviado).
- [ ] **Um** cron job rodando `php artisan schedule:run` a cada minuto.
- [ ] `php artisan migrate --force` executado; `config:cache`/`route:cache`/`view:cache` rodados.
- [ ] Site abre em HTTPS, **instala como app** e **funciona offline**.

> Detalhes de *como* executar cada item estão no **documento 02 — Guia de Deploy**.
