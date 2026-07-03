# Documentação do FlowFin

Bem-vindo(a)! Estes documentos foram escritos em **linguagem simples**, passo a passo, para
quem **não programa**. Comece pelo que você precisa agora:

| Documento | Para quê | Quando usar |
|-----------|----------|-------------|
| [01 — Configuração de Produção](01-configuracao-producao.md) | Entender *o que* o servidor precisa ter configurado e por quê | Antes do primeiro deploy, como referência |
| [02 — Guia de Deploy](02-guia-deploy.md) | Publicar e atualizar o FlowFin na Hostinger, do zero | Para colocar o app no ar e em cada atualização |
| [03 — Guia de Uso e Roadmap](03-guia-uso-roadmap.md) | Saber usar todas as funcionalidades e o que vem por aí | Para você e para os usuários do app |

## Atalhos rápidos

- **Quero publicar o app agora:** vá direto ao [Guia de Deploy](02-guia-deploy.md).
- **Já publiquei e quero atualizar:** [Guia de Deploy → seção 12 (Atualização)](02-guia-deploy.md#12-atualização-deploys-seguintes--versão-curta).
- **Algo deu errado no deploy:** [Guia de Deploy → seção 13 (Armadilhas reais)](02-guia-deploy.md#13-armadilhas-reais-aprendidas-no-deploy-de-teste).
- **Quero aprender a usar o FlowFin:** [Guia de Uso](03-guia-uso-roadmap.md).

## Modelo de configuração

O arquivo **`.env.production.example`** (na raiz do projeto) é o modelo do `.env` de produção:
copie-o para `.env` no servidor e preencha os campos `<< ... >>`.
