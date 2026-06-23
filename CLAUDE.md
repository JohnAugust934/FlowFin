APM_RULES {

## Idioma e Formato
- Toda interface, conteúdo e mensagem voltada ao usuário é em **português do Brasil**.
- Moeda exibida em **Real (R$)** no formato brasileiro: ponto separador de milhar e vírgula decimal (ex.: `R$ 1.234,56`).
- Datas exibidas no formato **dd/mm/aaaa**.

## Valores Monetários
- Armazene dinheiro sempre como **inteiro em centavos** (ex.: R$ 12,34 → `1234`). Nunca use `float`/`decimal` para representar dinheiro internamente.
- Converta para centavos na entrada e formate para R$ apenas na exibição, usando um helper de formatação compartilhado.

## Integridade de Dados
- Use **soft delete** em qualquer entidade que o usuário possa apagar — nada é removido fisicamente sem intenção explícita (exceto exclusão definitiva exigida por LGPD).
- Toda consulta a dados financeiros é **escopada ao usuário autenticado** (`user_id`). Nunca retorne dados de um usuário a outro.
- Confirme explicitamente ao usuário que um dado foi salvo; nenhuma ação de escrita termina sem feedback de sucesso ou erro.

## Performance
- Evite o problema N+1: use **eager loading** ao carregar relacionamentos em consultas que alimentam telas.
- **Pagine** listagens em 20 itens por página; nunca carregue listas inteiras de uma vez.
- Agregados de dashboard e resumos mensais devem ser **cacheados** e o cache **invalidado** quando uma transação é criada, editada ou removida.

## Restrições de Hospedagem
- O alvo é hospedagem compartilhada sem Redis e sem worker de fila persistente. **Não** introduza dependências que exijam esses recursos.
- Use **cache em arquivo** e **fila no banco de dados**; processamento em segundo plano e tarefas agendadas acontecem via o **scheduler do Laravel** disparado por um cron único.

## UX e Consistência Visual
- Design **mobile-first**; toda tela funciona bem no celular e no desktop.
- Toda ação do usuário (salvar, editar, excluir, atingir limite) recebe **feedback visual imediato**; estados de loading são visíveis.
- Reutilize os componentes e os tokens do design system (paleta azul/verde, fonte Inter, cores semafóricas). Não introduza estilos fora da paleta nem telas que destoem do restante do app.
- Use **linguagem humana** com o usuário: "entrada" e "saída", nunca jargão contábil. Mantenha as telas limpas e sem poluição visual.

## Validação e Testes
- Lógica de backend (cálculos, regras de negócio, endpoints) é coberta por **feature tests** automatizados.
- Funcionalidades de interface são validadas por **validação visual guiada**: descreva ao usuário o que testar e confirme o comportamento esperado.
- Descreva os critérios de aceite de cada entrega em termos **verificáveis pelo usuário** (comportamento observável), não em termos de implementação.

## Comunicação com o Usuário (não-técnico)
- O usuário não programa e não lê código. Qualquer instrução para ele deve ser **passo a passo, em linguagem leiga**.
- Quando uma etapa exigir ação em plataforma externa ou credenciais (ex.: configurar SMTP, criar cron, ativar SSL, conectar repositório), **pause** e oriente o usuário com instruções explícitas antes de prosseguir.

} //APM_RULES
