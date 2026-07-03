# FlowFin — Guia de Deploy Passo a Passo (Hostinger)

Este guia leva você **do seu computador até o FlowFin publicado e funcionando** na Hostinger,
em linguagem simples e passo a passo. Você **não precisa saber programar** para seguir.

Ele assume um plano da Hostinger **com acesso SSH** (terminal) e um **domínio ou subdomínio
com SSL** — foi assim que o deploy de teste funcionou. Onde for preciso digitar comandos,
basta **copiar e colar**, trocando os trechos `<< ... >>` pelos seus dados.

> 🧭 **Como ler este guia:** faça uma vez o **Deploy inicial** (seções 1 a 9). Depois, para
> cada atualização futura, use só o **Atualização** (seção 10) — é bem mais curto.

---

## Visão geral do fluxo

O FlowFin usa GitFlow:

- **`develop`** — onde o trabalho é integrado (versão de desenvolvimento).
- **`main`** — reservada para **produção**. O deploy oficial sai **sempre da `main`**.

O caminho é: trabalho pronto em `develop` → publica na `main` → o servidor baixa a `main`.
O código já está no GitHub em **https://github.com/JohnAugust934/FlowFin**.

---

## 1. Antes de começar — o que ter em mãos

- [ ] Login do **hPanel** da Hostinger.
- [ ] Um **domínio ou subdomínio** já criado na Hostinger (ex.: `app.seudominio.com`).
- [ ] Acesso **SSH** ativado no seu plano (hPanel → **Avançado → SSH**: anote host, porta, usuário).
- [ ] No seu computador: o FlowFin instalado e funcionando, com **Git** e **Node** (para o build).

> 👤 Se não souber se tem SSH, procure por "SSH Access" no hPanel. Se não existir, me avise —
> há um caminho alternativo via Gerenciador de Arquivos, mas o SSH é bem mais simples.

---

## 2. Ativar o SSL (HTTPS) — obrigatório

O FlowFin é instalável e funciona offline, e isso **só liga em HTTPS**.

👤 **Passo do usuário:**
1. No hPanel, vá em **Segurança → SSL**.
2. Selecione o seu domínio/subdomínio e ative o certificado **Let's Encrypt** (gratuito).
3. Aguarde ficar "Ativo" (pode levar alguns minutos).
4. Ative a opção de **forçar HTTPS** (redirecionar `http` → `https`), se houver.

✅ **Como saber que deu certo:** abrir `https://seu-endereco` mostra o cadeado de seguro.

---

## 3. Apontar o site para a pasta `public`

👤 **Passo do usuário:** ao criar/editar o domínio ou subdomínio no hPanel, defina a pasta do
site (document root) terminando em **`/public`**, por exemplo:

```
domains/SEUDOMINIO/public_html/flowfin/public
```

Isso é essencial: apontar para a pasta errada quebra o app e expõe arquivos internos.

---

## 4. Criar o banco de dados MySQL

👤 **Passo do usuário:**
1. No hPanel, vá em **Bancos de Dados → MySQL**.
2. Crie um **novo banco** e um **usuário**, e associe o usuário ao banco com todos os privilégios.
3. **Anote**: nome do banco, usuário e senha — você vai usar no `.env` (seção 7).

---

## 5. Gerar o build dos assets no seu computador

Como o Node não é confiável no servidor compartilhado, geramos o visual (CSS/JS) **localmente**
e enviamos pronto. No seu computador, dentro da pasta do FlowFin:

```
git checkout develop
git pull
npm install
npm run build
```

Isso cria/atualiza a pasta `public/build`. Em seguida, **publique na `main` levando o build
junto**. Como o `public/build` normalmente fica no `.gitignore`, force a inclusão dele só na
publicação de produção (foi o que funcionou no teste):

```
git checkout main
git merge develop
git add -f public/build
git commit -m "chore: build de produção"
git push origin main
```

> 💡 **Por que `-f` (forçar)?** O build é um artefato gerado; normalmente não vai pro Git. Mas,
> para dispensar o Node no servidor, embutimos o build pronto **na branch `main`**. A `develop`
> continua limpa.

---

## 6. Conectar ao servidor e baixar o código (SSH)

👤 **Passo do usuário** — abra o terminal SSH (no seu computador ou pelo terminal do hPanel) e
conecte com os dados do seu plano:

```
ssh -p <<porta>> <<usuario>>@<<host>>
```

Vá até a pasta onde o site mora e baixe o código da **`main`**:

```
cd domains/SEUDOMINIO/public_html/flowfin
git clone -b main https://github.com/JohnAugust934/FlowFin .
```

> Se a pasta já existir com o projeto, use `git pull origin main` em vez do `clone`.

---

## 7. Instalar dependências e configurar o `.env`

Ainda no SSH, dentro da pasta do FlowFin:

```
composer install --no-dev --optimize-autoloader
cp .env.production.example .env
php artisan key:generate
```

Agora edite o `.env` (pelo editor do SSH, ex.: `nano .env`, ou pelo Gerenciador de Arquivos do
hPanel) e preencha:

- `APP_URL=https://<<seu-endereco>>`
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (dados da seção 4).
- `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` (seção 9 — pode preencher depois).

Confirme que estão assim: `APP_ENV=production`, `APP_DEBUG=false`,
`SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=file`.

---

## 8. Criar as tabelas e otimizar

```
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Ajuste as permissões das pastas que o Laravel escreve:

```
chmod -R 775 storage bootstrap/cache
```

> ℹ️ **Sobre `php artisan storage:link`:** na Hostinger esse comando **falha** (a função
> `exec()` do PHP é bloqueada). **Para o FlowFin, pule-o** — o app não serve uploads públicos,
> então não faz falta.

**(Opcional) Usuário de teste para validar sem cadastrar:** se quiser entrar sem depender do
e-mail de verificação enquanto valida, rode:

```
php artisan db:seed --class=TestDataSeeder
```

Isso cria o login `teste@flowfin.com.br` / senha `senha1234` (com e-mail já verificado) e alguns
dados de exemplo. **Não rode isso se já houver dados reais que você não queira misturar.**

---

## 9. Configurar o e-mail (SMTP) para a verificação de cadastro

O cadastro de novos usuários envia um e-mail de verificação. Para isso funcionar em produção,
configure uma caixa de e-mail da Hostinger.

👤 **Passo do usuário:**
1. No hPanel, em **E-mails**, crie uma caixa (ex.: `nao-responder@seudominio.com`) e defina a senha.
2. No `.env`, preencha:
   - `MAIL_MAILER=smtp`
   - `MAIL_HOST=smtp.hostinger.com`
   - `MAIL_PORT=465` e `MAIL_ENCRYPTION=ssl` (ou `587` + `tls`)
   - `MAIL_USERNAME=` o endereço da caixa criada
   - `MAIL_PASSWORD=` a senha da caixa
   - `MAIL_FROM_ADDRESS=` o mesmo endereço
3. Rode de novo `php artisan config:cache` para aplicar.

> Enquanto o SMTP não estiver pronto, você ainda consegue validar o app entrando com o usuário
> de teste do seeder (seção 8), que já vem com o e-mail verificado.

---

## 10. Configurar o cron (rotina diária automática)

O FlowFin precisa de **um único** cron que dispara o agendador do Laravel a cada minuto. É ele
que efetiva o reset diário da "sequência" (🔥) dos usuários.

👤 **Passo do usuário:**
1. No hPanel, vá em **Avançado → Cron Jobs**.
2. Crie um cron com frequência **a cada minuto** (`* * * * *`).
3. No comando, use (trocando o caminho pelo real do seu FlowFin):

```
cd /home/<<usuario>>/domains/SEUDOMINIO/public_html/flowfin && php artisan schedule:run >> /dev/null 2>&1
```

✅ **Como conferir:** pelo SSH, `php artisan schedule:list` deve mostrar a rotina diária
agendada para `00:10`. É **um cron só** — não crie outros.

---

## 11. Validação final (faça você mesmo)

Abra `https://seu-endereco` no celular e confira:

- [ ] O site abre com o cadeado de seguro (HTTPS).
- [ ] As telas aparecem com o visual certo (cores, fontes) — confirma que o `public/build` subiu.
- [ ] Dá para **instalar o app** (o navegador oferece "Adicionar à tela inicial"/instalar).
- [ ] Em **modo avião**, o app instalado ainda abre e você consegue **registrar uma entrada/saída**;
      ao voltar a ter internet, o registro **sincroniza** (aparece na lista, sem duplicar).
- [ ] Login funciona (usuário de teste, ou um cadastro real se o SMTP já estiver pronto).

Se algo falhar, veja as **Armadilhas** abaixo.

---

## 12. Atualização (deploys seguintes — versão curta)

Quando você já publicou uma vez e quer enviar uma nova versão:

**No seu computador:**
```
git checkout develop && git pull && npm install && npm run build
git checkout main && git merge develop
git add -f public/build && git commit -m "chore: build de produção"
git push origin main
```

**No servidor (SSH):**
```
cd /caminho/para/o/flowfin
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Pronto. O cron e o SSL já continuam valendo — não precisa refazer.

---

## 13. Armadilhas reais (aprendidas no deploy de teste)

| Sintoma | Causa | O que fazer |
|---------|-------|-------------|
| App abre, mas **não instala / não funciona offline** | Sem HTTPS | Ative o SSL (seção 2) e confirme `APP_URL=https://...`. |
| Telas **sem visual** (texto cru) | Faltou o `public/build` no servidor | Gere o build localmente e envie na `main` com `git add -f public/build` (seção 5). |
| Erro **`Call to undefined function ... exec()`** ao rodar `storage:link` | A Hostinger bloqueia `exec()` | **Pule** o `storage:link` — o FlowFin não precisa dele. |
| Site mostra arquivos/erros internos | Document root errado | Aponte o site para a pasta **`public`** (seção 3). |
| Mudei o `.env` e nada mudou | Config em cache | Rode `php artisan config:cache` (ou `config:clear`). |
| O 🔥 (sequência) não reseta | Cron não configurado | Crie o cron único (seção 10) e cheque `schedule:list`. |
| E-mail de cadastro não chega | SMTP não configurado | Configure `MAIL_*` (seção 9) ou use o usuário de teste para validar. |

---

> Para entender *o que* cada configuração faz, veja o **documento 01 — Configuração de Produção**.
> Para conhecer as funcionalidades do app, veja o **documento 03 — Guia de Uso e Roadmap**.
