# CLAUDE.md — FacilMed

> **A fonte canônica de contexto e regras deste projeto é o [`AGENTS.md`](./AGENTS.md).**
> Leia-o primeiro e siga-o integralmente. Este arquivo só reforça, ao Claude Code, os pontos
> específicos do ecossistema Claude — para não duplicar (nem divergir) do AGENTS.md.

## Antes de qualquer coisa

1. Leia `AGENTS.md`, `AI_HANDOFF.md` e `PERFIL.md`.
2. Rode `git log --oneline -10` e `git status` para ver onde o último agente parou.
3. Siga o **Protocolo de Handoff** (§10 do AGENTS.md): resuma → liste arquivos →
   plano curto → riscos → **aguarde aprovação** antes de editar.

## Regra de execução — planejar vs. executar

Antes de executar qualquer tarefa, avaliar **onde** ela deve ser feita.

- **Tarefas simples** (documentação, organização, revisão de texto, ajuste não técnico):
  perguntar ao usuário se quer que seja feita direto na sessão atual.
- **Tarefas técnicas** (código, terminal, banco de dados, automação, integração):
  **não executar direto em ferramenta de conversa.** Primeiro levantar o contexto, montar um
  plano estruturado e perguntar ao usuário se quer levar esse plano para execução no Claude Code.

## Contexto de execução deste projeto

- O projeto roda **na máquina do aluno**, em XAMPP, não em ambiente de nuvem. Comandos
  `php artisan`, `composer` e `npm` precisam rodar lá — não num container remoto que não tem
  o banco MySQL do XAMPP.
- São **4+ pessoas** no mesmo repositório. Antes de criar migration, conferir se outra já foi
  criada para o mesmo assunto. Migration já aplicada **não se edita**: cria-se outra.
- O grupo está em nível intermediário em Laravel. Ao escrever código, **explique a escolha**
  quando usar recurso menos óbvio (Policy, observer, relacionamento com atributos, coluna
  virtual). Código que ninguém do grupo sabe defender na banca é passivo, não entrega.

## Conectores e ferramentas

- **Confirme o que está realmente conectado antes de afirmar que falta algo.** Rode
  `claude mcp list` (ou `/mcp`). Uma ferramenta pode estar **conectada mas não carregada** na
  sessão — nesse caso, recarregue a sessão e confira de novo. **Não conclua "não tenho acesso"
  sem checar.**
- Este projeto não depende de nenhum MCP específico. O acesso ao código é pelo sistema de
  arquivos local.

## Antes de encerrar a sessão

- **Sempre atualize `AI_HANDOFF.md`** com o que foi feito, decisões tomadas, o que falta e o
  próximo passo. Não deixe a próxima IA adivinhar. Faça isso **a cada checkpoint**, não só no fim.
- Checkpoint com **commit** (não stash).
- Se instalou pacote novo, registre a decisão no handoff (AGENTS.md §3).

## Lembretes de segurança

- Nunca edite `.env` nem exponha credenciais.
- Nada de comandos destrutivos sem aprovação — inclusive `migrate:fresh` quando o banco é
  compartilhado com outro integrante.
- ⚠ **E-mail é o único side-effect do projeto.** Desenvolva com `MAIL_MAILER=log`. Envio real
  só com aprovação explícita, e depois limpe as linhas de teste em `notificacoes_enviadas` —
  senão o lembrete verdadeiro daquela consulta nunca sai. Rito completo: AGENTS.md §7.1.

## As três regras de negócio que custam caro esquecer

Repetidas do AGENTS.md §6 porque quebrá-las faz o sistema mentir para quem usa:

1. **Nunca gere conteúdo clínico.** Nada de diagnóstico, receita, atestado, prontuário, nem
   rótulo sobre o estado de saúde do paciente ("estável", "risco", "alerta"). Isto é um sistema
   de agendamento. O PDF de design mostra um card "Status da sua saúde" — **não implemente.**
2. **Nunca diga que o CRM foi validado junto ao CFM, nem a carteirinha junto à operadora.**
   As duas conferências são humanas. A tela diz "verificado pela equipe" ou "em conferência".
3. **Nunca remova a coluna virtual `horario_ativo` nem o índice único de `consultas`.** É o que
   impede dois pacientes no mesmo horário. Checagem em PHP antes do INSERT não substitui.
