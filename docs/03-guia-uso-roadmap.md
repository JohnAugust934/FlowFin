# FlowFin — Guia de Uso e Roadmap

O FlowFin é seu controle financeiro pessoal: **rápido de usar, funciona no celular e no
computador, instala como aplicativo e funciona mesmo sem internet**. Este guia mostra, em
linguagem simples, o que dá para fazer hoje, e o que ainda está por vir (roadmap).

> 💡 O FlowFin fala a sua língua: **"entrada"** (dinheiro que chega) e **"saída"** (dinheiro
> que sai) — sem jargão de contabilidade. Valores em **R$** no formato brasileiro e datas em
> **dd/mm/aaaa**.

---

## Primeiros passos

1. **Instale o app.** Abra o endereço do FlowFin no navegador do celular e escolha
   **"Adicionar à tela inicial"** / **"Instalar"**. Ele passa a abrir como um aplicativo.
2. **Crie sua conta.** Informe e-mail e senha; confirme pelo link enviado por e-mail.
   (Verificação de cadastro por e-mail.)
3. **Comece a registrar** suas entradas e saídas. É isso que alimenta todo o resto.

---

## O que dá para fazer hoje

### 1. Registrar entradas e saídas (rápido e offline)
- Lançar uma transação leva **até 3 toques**: valor, categoria e salvar.
- **Funciona offline:** se você estiver sem internet, o registro fica numa **fila** e é
  **sincronizado automaticamente** quando a conexão volta — sem duplicar.
- Um indicador mostra quando você está **offline**, com **registros pendentes** ou **sincronizando**.

### 2. Painel (dashboard) e ranking
- Resumo do mês: total de entradas, saídas e saldo.
- **Ranking** das categorias onde você mais gasta, para enxergar para onde o dinheiro vai.

### 3. Histórico e filtros
- Lista de todas as transações, **paginada** (20 por vez).
- Filtros por período, tipo (entrada/saída) e categoria.

### 4. Consciência
- Visão clara dos seus hábitos de gasto, ajudando a perceber padrões.

### 5. Economia (orçamentos, meta e "onde economizar")
- **Orçamentos** por categoria: defina um limite e acompanhe o quanto já foi consumido.
- **Meta de economia** com acompanhamento do progresso.
- Sugestões de **onde dá para economizar**.

### 6. Mentalidade (sua evolução financeira)
- **Score FlowFin (0–100):** uma nota mensal que combina sua **consistência** em registrar, o
  **respeito aos orçamentos** e o **progresso na meta**.
- **Sequência (🔥 streak):** dias seguidos em que você registrou algo. Falhar um dia inteiro
  zera a sequência (o reset acontece automaticamente de madrugada).
- **Dicas** contextuais (alertas, elogios e dicas educativas) e **mini-conteúdos** educativos.

### 7. Direcionamento (para onde você quer ir)
- **Metas** com propósito e acompanhamento de progresso.
- **Simulador:** descubra quanto guardar por mês para chegar a uma meta.
- **Investimentos:** registre seus investimentos e veja o total aplicado.

### 8. Perfil e dados
- Tela de **perfil** com tema **claro/escuro**.
- **Exportar relatório mensal** em **CSV** ou **PDF**.
- **Exportar todos os seus dados** (LGPD), em arquivo completo.
- **Excluir sua conta** definitivamente (LGPD), com confirmação por senha — apaga seus dados
  de forma permanente.

---

## Dúvidas comuns

- **Preciso de internet?** Para instalar e sincronizar, sim. Mas, depois de instalado, dá para
  **registrar offline**; tudo sincroniza quando a conexão volta.
- **Meus dados são meus?** Sim. Cada conta só enxerga os próprios dados, e você pode exportar
  tudo ou excluir a conta quando quiser (LGPD).
- **Por que minha sequência 🔥 zerou?** Porque um dia inteiro passou sem nenhum registro. Basta
  voltar a registrar para começar uma nova sequência.

---

## Roadmap (o que ainda vem por aí)

Estas funcionalidades **não estão no app hoje** e estão planejadas para o futuro:

- **Login com Google** — entrar com a conta Google, sem senha separada.
- **Passkeys** — login sem senha, por biometria/dispositivo.
- **Importar CSV** — trazer transações de um arquivo (ex.: extrato do banco).
- **Multi-moeda** — usar outras moedas além do Real.
- **Open Finance** — conectar contas bancárias para trazer transações automaticamente.

### Lacuna conhecida (a tratar)
- **Cadastro de recorrências sem tela própria:** o app já entende transações **recorrentes**
  (ex.: assinaturas e contas fixas) por baixo dos panos, e o painel de **"gastos invisíveis"**
  depende delas. Porém **ainda não existe uma tela ligada ao menu** para você marcar/cadastrar
  uma recorrência — hoje não há um caminho na navegação para isso. É o próximo item natural a
  ser conectado para o painel de gastos invisíveis ficar completo.

---

> Para publicar ou atualizar o FlowFin no servidor, veja o **documento 02 — Guia de Deploy**.
