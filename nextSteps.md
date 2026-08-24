## Status do roadmap

Os itens abaixo foram implementados desde a versão inicial deste documento: ciclo completo dos lançamentos, materialização agendada de recorrências, saldos reais, cartões com limite e faturas, dashboard por período, orçamentos, metas, transferências, segurança de referências, importação/exportação e notificações calculadas.

As próximas prioridades são controle detalhado de faturas, importação com conciliação bancária, UX mobile/acessibilidade, notificações persistentes e endurecimento operacional para produção.

## 1. Histórico: completar o ciclo dos lançamentos

Hoje é possível criar, confirmar e cancelar, mas faltam operações essenciais:

- editar uma transação;
- excluir uma transação individual;
- duplicar um lançamento;
- alterar categoria, conta, cartão e valor;
- escolher entre editar apenas aquela ocorrência ou toda a série recorrente;
- desfazer confirmação;
- alterar o status para cancelado;
- registrar data real de pagamento, diferente da data prevista.

Seria importante criar uma camada específica, por exemplo TransactionService, para centralizar essas regras e evitar que toda a lógica fique dentro do componente Livewire.

## 2. Corrigir a materialização das recorrências

Atualmente o Dashboard gera ocorrências dentro do método render(). Isso funciona, mas não é uma boa responsabilidade para uma renderização de tela.

Além disso, a página de transações não materializa novas ocorrências. Isso pode causar comportamentos inconsistentes entre dashboard e transações.

Próximo desenho recomendado:

- criar um comando Artisan, como transactions:materialize;
- executar esse comando via Scheduler diariamente;
- gerar ocorrências futuras com uma janela configurável;
- manter o render() apenas para consultar dados;
- usar filas caso o volume cresça;
- criar testes para alterações e encerramento de séries recorrentes.

Essa é provavelmente a melhoria técnica mais importante antes de aumentar muito a quantidade de funcionalidades.

## 3. Implementar saldo real das contas

Hoje a conta exibe apenas o initial_balance. O sistema ainda não calcula:

- saldo atual;
- saldo previsto;
- total recebido;
- total pago;
- saldo por conta;
- saldo consolidado;
- movimentações de entrada e saída.

O modelo deveria calcular algo como:

saldo atual =
saldo inicial
+ entradas confirmadas
- despesas confirmadas

Também seria importante diferenciar:

- saldo realizado;
- saldo previsto;
- saldo disponível;
- saldo comprometido em lançamentos futuros.

## 4. Melhorar o controle de cartões

O dashboard atualmente mostra o limite cadastrado, mas não o limite realmente disponível.

Faltam:

- valor utilizado no cartão;
- limite disponível;
- fatura atual;
- próxima fatura;
- fechamento da fatura;
- vencimento;
- agrupamento de compras por fatura;
- pagamento da fatura;
- visualização de compras parceladas;
- alerta de limite próximo do fim.

Também recomendo separar claramente:

- data da compra;
- data de fechamento;
- data de vencimento;
- competência da fatura.

Atualmente a transação vinculada ao cartão altera diretamente a due_date, o que pode dificultar análises futuras sobre quando a compra realmente aconteceu.

## 5. Tornar o dashboard configurável

Há alguns elementos que parecem estáticos atualmente:

- “Este ano” é exibido, mas não há seleção de período;
- o gráfico usa dados fixos de 12 meses;
- a busca no topo não está conectada às transações;
- o botão de notificações é apenas visual;
- o progresso do limite do cartão não representa necessariamente o uso real.

Próximas interações úteis:

- filtro por mês;
- filtro por ano;
- intervalo personalizado;
- gráfico por categoria;
- gráfico de receitas versus despesas;
- comparação com o mês anterior;
- maiores despesas;
- previsão de saldo;
- lançamentos vencendo nos próximos 7 dias;
- alertas de orçamento.

## 6. Adicionar orçamentos e metas

Essa seria uma funcionalidade de alto valor para o usuário.

Exemplos:

- limite mensal para alimentação;
- limite para lazer;
- meta de reserva de emergência;
- meta para viagem;
- percentual da renda comprometido;
- alerta ao atingir 80% ou 100% do orçamento.

Para isso, provavelmente seria necessário criar entidades como:

- budgets;
- financial_goals;
- notifications.

## 7. Criar transferências entre contas

O sistema possui contas, mas ainda não existe transferência.

Uma transferência não deveria ser tratada como uma simples despesa e uma simples receita, pois isso distorce os relatórios. O ideal seria criar:

- transfers;
- conta de origem;
- conta de destino;
- valor;
- data;
- status;
- observação.

Isso permitirá calcular corretamente o saldo de cada conta.

## 8. Melhorar segurança e regras de domínio

Embora já existam verificações de propriedade nos lançamentos, elas podem ficar mais consistentes.

Recomendo:

- usar FormRequest ou objetos de validação próprios;
- aplicar Policies para contas, cartões, categorias e transações;
- restringir diretamente os exists ao usuário autenticado;
- validar se categoria combina com o tipo do lançamento;
- impedir despesa em categoria de entrada;
- impedir entrada vinculada a cartão;
- usar transações de banco ao criar séries e ocorrências;
- adicionar índices para consultas frequentes;
- aplicar limites de requisição em login e ações sensíveis.

Exemplo conceitual:

Rule::exists('categories', 'id')
->where('user_id', auth()->id())
->where('type', $this->form['type']);

## 9. Melhorar a experiência do usuário

O Livewire já permite deixar a interface bem dinâmica. Eu priorizaria:

- mensagens de sucesso após cada ação;
- estado de carregamento nos botões;
- desabilitar botão durante salvamento;
- validação por campo;
- modal de edição;
- filtros persistidos na URL;
- seleção rápida de período;
- atalhos de teclado;
- confirmação apenas quando realmente necessário;
- suporte melhor para mobile;
- paginação e ordenação sem recarregamento;
- empty states mais orientativos.

Também seria bom substituir os símbolos textuais atuais, como ↙, ↗ e ♧, por ícones consistentes e acessíveis.

## 10. Importação e exportação

Depois que o domínio estiver mais sólido, as funcionalidades que mais aumentariam a utilidade prática seriam:

- exportação CSV;
- exportação OFX;
- importação de extrato bancário;
- categorização automática;
- regras por estabelecimento;
- detecção de duplicidades;
- conciliação bancária.

Uma boa primeira versão seria permitir importar CSV e mapear colunas antes de confirmar a importação.

## Ordem recomendada

Eu seguiria esta sequência:

1. Edição, exclusão e cancelamento correto de transações.
2. Refatoração da recorrência para comando agendado.
3. Saldo real das contas.
4. Controle de faturas e limite dos cartões.
5. Dashboard com períodos e gráficos realmente dinâmicos.
6. Transferências entre contas.
7. Orçamentos e metas.
8. Notificações e lembretes.
9. Importação/exportação.
10. Categorização automática e conciliação.
