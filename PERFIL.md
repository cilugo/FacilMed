# Perfil — FacilMed

> Documento de referência do **projeto, do negócio e da operação**: o que é, para quem, com
> que regras e em que tom. **Não é fonte da verdade de dado operacional** — para isso, veja a
> tabela abaixo.
>
> **Última atualização:** 18/09/2026 · **Status:** material vivo; revisar sempre que houver
> mudança relevante em escopo, público, posicionamento ou operação.

## Como este documento se encaixa

| Assunto | Fonte da verdade | Onde fica |
|---|---|---|
| Operadoras de plano de saúde existentes | CSV de dados abertos da ANS | `database/data/operadoras_ans.csv` |
| Validade de um CRM | portal do CFM, conferido por pessoa | registrado em `medicos.status_verificacao` |
| Estrutura de dados do sistema | migrations do Laravel | `database/migrations/` |
| Identidade visual e telas | `Descricao_Visual_FacilMed.pdf` | `docs/` |
| Regras técnicas para agentes de IA | `AGENTS.md` | raiz do projeto |
| Estado do projeto e decisões recentes | `AI_HANDOFF.md` | raiz do projeto |
| Identidade, público, tom, visão geral | **este arquivo** | — |

> Quando este documento cita um dado operacional, é **snapshot datado**. Confira na fonte antes
> de publicar qualquer coisa.

---

## 1. Resumo executivo

FacilMed é uma plataforma web onde a pessoa marca consulta médica sozinha, sem telefonar para
clínica nenhuma. Ela busca por especialidade, vê quem atende perto, quanto custa, quais convênios
o profissional aceita e quais horários estão livres — e confirma. Do outro lado, médicos
autônomos e clínicas se cadastram, publicam a agenda e recebem os agendamentos.

O diferencial não é ser mais uma agenda on-line: é dar ao paciente, **antes** de marcar,
a informação que hoje ele só descobre no telefone — preço da consulta particular, convênio
aceito naquele endereço específico, e avaliação de quem já foi.

### Síntese

- **Nome:** FacilMed
- **Categoria / setor:** Saúde — agendamento e intermediação de consultas
- **O que entrega:** agendamento direto de consulta médica, particular ou por convênio, com
  preço e condições visíveis antes da escolha
- **Onde atua:** web, navegador desktop e mobile. Projeto acadêmico, sem recorte regional real
- **Responsável:** Murilo Martins Souza e grupo (4+ integrantes)
- **Estágio atual:** desenvolvimento — TCC com entrega em 20/10/2026

---

## 2. Propósito e posicionamento

### Propósito

Marcar consulta hoje é opaco. A pessoa liga, é transferida, descobre que aquele médico não
atende o plano dela naquele endereço, não consegue saber quanto custa a particular sem
perguntar, e desliga sem marcar. O FacilMed coloca tudo isso na tela antes da decisão.

### Posicionamento

É um **buscador com agendamento**, não um sistema de gestão de clínica. A clínica que usa o
FacilMed continua tendo o sistema dela para o resto — o FacilMed cuida só da porta de entrada.

Não compete com prontuário eletrônico, com sistema de faturamento TISS, nem com telemedicina.

### Proposta de valor

Ver preço, convênio e horário livre antes de marcar — e marcar em um minuto, sem ligar.

### Mensagens prioritárias

1. **Você vê o preço antes.** Consulta particular com valor na tela, não "ligue e consulte".
2. **Convênio conferido por endereço.** O mesmo médico pode aceitar seu plano numa clínica e
   não no consultório — o FacilMed mostra onde vale.
3. **Marcar e desmarcar sem telefone.** Agendar, remarcar e cancelar pela própria conta.

### Limite do posicionamento

O que o FacilMed **não pode alegar sobre si mesmo**, ainda que soe bem no material do TCC:

- Não pode dizer que "valida o CRM junto ao CFM". A conferência é manual, feita por uma pessoa
  da equipe. Dizer o contrário é falso — a API oficial do CFM é paga e exige CNPJ.
- Não pode dizer que "valida a carteirinha do plano". Valida a **operadora** contra a lista da
  ANS; o número da carteirinha é conferido por pessoa.
- Não pode se apresentar como plataforma de saúde que "acompanha" o paciente, "monitora" a
  saúde dele ou dá qualquer leitura sobre o estado clínico dele. É agendamento. Só.
- Não pode prometer que o médico vai comparecer, nem responder pela qualidade do atendimento.

---

## 3. Público

### Público principal

- Pessoa adulta que precisa marcar consulta e não tem paciência para o telefone: trabalha em
  horário comercial, não consegue ligar durante o expediente da clínica.
- Pessoa com plano de saúde que não sabe quem aceita o plano dela perto de casa.
- Pessoa sem plano que quer consulta particular e precisa comparar preço antes.

### Necessidade que o projeto resolve

Saber, **antes de decidir**, quem atende, onde, por quanto e em que horário. Hoje essa
informação está espalhada entre o site da operadora (desatualizado), a recepção da clínica
(só por telefone) e o boca a boca.

### Ocasiões e contexto de uso

- Precisa de especialista e não tem indicação de ninguém.
- Recebeu pedido de encaminhamento e precisa marcar em poucos dias.
- Consulta de rotina que fica sendo empurrada justamente porque marcar dá trabalho.
- Fora do horário comercial — à noite, no fim de semana — quando ligar não é opção.

### Quem NÃO é o público

- Quem precisa de atendimento de urgência. O FacilMed **não é** canal de emergência e a
  interface deve deixar isso claro.
- Clínica que procura sistema de gestão completo, com prontuário e faturamento.

---

## 4. Oferta

| Item | O que é | Observação |
|---|---|---|
| Conta de paciente | Busca, agendamento, minhas consultas, avaliação, cadastro de carteirinha | Gratuita |
| Conta de médico autônomo | Perfil público, especialidades, consultório próprio, agenda, preço particular | Precisa de aprovação do CRM antes de aparecer na busca |
| Conta de clínica | Perfil público, unidades, médicos vinculados, agenda por especialidade, preço | Define o preço praticado nas unidades dela |
| Agendamento particular | Paciente escolhe médico e horário; valor exibido na tela | Pagamento **presencial**, a plataforma não cobra |
| Agendamento por convênio | Paciente usa a carteirinha cadastrada; só aparecem profissionais que aceitam a operadora naquele endereço | Elegibilidade real conferida na recepção |
| Agendamento por especialidade | Paciente escolhe clínica + especialidade; o sistema aloca médico disponível e **mostra o nome antes da confirmação** | — |
| Avaliação | Estrelas de 1 a 5, com comentário opcional | Só quem teve consulta realizada. Comentário vai só para a gestão; o público vê a média |
| Lembretes por e-mail | Confirmação no agendamento, lembrete 24h antes, lembrete no dia | — |

### Divergências conhecidas a resolver

- **O PDF de design mostra funcionalidades fora do escopo** — Exames, Receitas, Atestados,
  Prontuários, Relatórios e um card "Status da sua saúde". Nada disso existe no modelo de dados
  nem está no escopo (`AGENTS.md` §2), e o card de status de saúde viola `AGENTS.md` §6.
  **Pendente:** redesenhar os menus laterais antes de começar as telas.
- **Operadoras reais, planos fictícios** — as operadoras vêm dos dados abertos da ANS; os planos
  dentro delas são inventados para a demonstração. A interface precisa deixar isso claro para
  não parecer que a plataforma tem contrato com a Unimed.

---

## 5. Operação

### Como funciona na prática

**Paciente:** cria conta → opcionalmente cadastra a carteirinha do plano → busca por
especialidade, cidade e forma de pagamento → escolhe médico (ou clínica + especialidade) →
vê preço, endereço e horários livres → confirma → recebe e-mail de confirmação, lembrete 24h
antes e lembrete no dia → comparece → depois da consulta marcada como realizada, pode avaliar.

**Médico:** cria conta com CRM e UF → fica **pendente**, invisível na busca → equipe confere o
CRM no portal do CFM → aprovado, aparece na busca → cadastra especialidades, locais onde atende,
preço particular por local, convênios aceitos e blocos de horário → recebe agendamentos.

**Clínica:** cria conta com CNPJ → cadastra unidades, horário de funcionamento e médicos
vinculados → define o preço praticado em cada unidade → recebe agendamentos, inclusive os que
chegam só pela especialidade.

### Prazos, janelas e limites

- Consulta tem duração configurável por bloco de disponibilidade; padrão de 30 minutos.
- Almoço e intervalos são representados como **dois blocos de horário**, não como campo próprio.
- Férias, feriado e imprevisto entram como **bloqueio** de período.
- Cancelamento com menos de 24h é permitido, mas registrado como **cancelamento tardio**.

### Pagamento / contrapartida

A plataforma **não movimenta dinheiro**. Consulta particular é paga presencialmente, no valor
exibido. Consulta por convênio segue as regras da operadora, conferidas na recepção.

### Regras invioláveis de atendimento e entrega

Estas também aparecem, como proibição verificável, em `AGENTS.md` §6:

- Médico não verificado nunca aparece para o paciente.
- A plataforma nunca afirma ter validado CRM junto ao CFM nem carteirinha junto à operadora.
- A plataforma nunca exibe avaliação, status ou alerta sobre a saúde do paciente.
- Comentário de avaliação nunca é público.
- Dado de deficiência declarado pelo paciente nunca aparece fora da consulta em que é necessário.

---

## 6. Diretrizes do produto

### Padrões de qualidade

Uma tela está aceitável quando: funciona em telefone sem rolagem horizontal; todo estado de
erro tem mensagem em português claro dizendo o que fazer; nenhuma ação destrutiva acontece sem
confirmação; e nenhuma informação da §5 aparece para quem não deveria vê-la.

### Como algo novo entra na oferta

Ideia nova passa por três perguntas, nesta ordem: (1) está no escopo do `AGENTS.md` §2?
(2) cabe até 20/10 sem atrasar o que já está em construção? (3) alguém do grupo assume?
Se qualquer resposta for não, vai para a §13 deste documento — não para o código.

---

## 7. Tecnologia e automação

### Em produção

Nada. O projeto roda localmente e é apresentado ao vivo.

### O que é automatizado

- **Lembretes por e-mail** — comando Artisan acionado pelo scheduler do Laravel, com registro
  em `notificacoes_enviadas` para nunca repetir o mesmo envio.
- **Importação de operadoras da ANS** — comando Artisan que lê o CSV de dados abertos e popula
  a tabela de operadoras.
- **Cálculo de horários livres** — derivado de disponibilidade menos consultas menos bloqueios,
  sempre calculado, nunca armazenado.

### Princípios

- **Automação nunca inventa dado.** Operadora vem da ANS; validade de CRM vem de conferência
  humana; horário livre vem do banco. Nada é suposto.
- Toda automação que manda e-mail precisa ser **idempotente**: rodar duas vezes não manda duas
  vezes.

---

## 8. Identidade e comunicação

### Identidade visual

Fontes: as logos em `imgs/` e o `Descricao_Visual_FacilMed.pdf`, em `docs/`.

- **Marca:** símbolo circular formado por uma mão envolvendo um coração, em dois tons de azul —
  azul-marinho para a mão e o contorno externo, azul-claro para o arco interno e o preenchimento
  do coração. Wordmark "FacilMed" em sem serifa, peso médio, azul-marinho.
- **Slogan:** "Sua saúde, conectada."
- **Três variações disponíveis:** `logocomslogan.png` (símbolo + nome + slogan),
  `logonome.png` e `logosemslogan.png`. Use a com slogan apenas em peça institucional; no
  cabeçalho do sistema, a versão sem slogan.
- **Arquivos são PNG.** Para o cabeçalho e o favicon, vale gerar um SVG a partir deles — o PNG
  perde qualidade em tela de alta densidade e pesa mais do que precisa.

- **Cores:** azul-escuro para menu lateral e títulos; azul-claro para destaque e item
  selecionado; branco para os cards; fundo claro. Verde, roxo e rosa **apenas** para diferenciar
  status e categoria — nunca como cor de marca.
- **Componentes:** cards brancos de cantos arredondados com sombra discreta, bordas azuladas,
  botões de borda suave, etiquetas de status, ícones circulares, listas com seta de navegação.
- **Tipografia:** sem serifa. Título grande identifica a página; subtítulo contextualiza;
  número grande destaca indicador; texto menor complementa.
- **Espaço:** bastante branco entre os blocos. A leitura vem do agrupamento em cards, não de
  linhas divisórias.
- **Hierarquia:** informação mais importante em cima e à esquerda; ações frequentes agrupadas
  num bloco de acesso rápido.

### Canais

Apenas a própria plataforma e o e-mail transacional. Não há rede social, blog nem newsletter.

---

## 9. Personalidade e tom de voz

### Personalidade

- **Direto** — diz o preço, o endereço e o horário sem rodeio.
- **Transparente** — quando não sabe, diz que não sabe. "Carteirinha em conferência" em vez de
  um "validado" que não é verdade.
- **Acolhedor sem ser íntimo** — trata de saúde, então não usa gíria nem piada, mas também não
  é burocrático.
- **Calmo** — nada de urgência artificial, contagem regressiva ou "últimas vagas".

### Tom de voz

Segunda pessoa, frase curta, verbo no presente. "Sua consulta está confirmada para quinta,
14h, na Clínica Vila Nova." Não: "Informamos que o seu agendamento foi processado com sucesso."

### Evitar

- Qualquer palavra que sugira leitura clínica: "estável", "risco", "alerta de saúde",
  "monitoramento", "sua saúde está...".
- "Validado" quando a conferência foi humana ou parcial. Use "verificado pela equipe" ou
  "em conferência".
- Urgência fabricada: "corra", "últimas vagas", "não perca".
- Jargão de sistema na cara do usuário: "registro processado", "requisição", "entidade".
- Emoji em e-mail transacional.

---

## 10. Diferenciais

O que distingue o FacilMed **hoje**, não o que se pretende ter:

- **Preço da particular visível antes de marcar**, por tabela de preços — a clínica define o
  valor de cada especialidade nas unidades dela; o médico autônomo define no consultório dele.
  O mesmo profissional pode cobrar preços diferentes por especialidade e por endereço.
- **Operadoras validadas contra dados abertos da ANS** — não é campo de texto livre onde alguém
  digita um convênio que não existe. Os planos, esses, são fictícios.
- **Avaliação com comentário privado** — a nota é pública, o comentário vai só para a gestão.
  Reduz o risco jurídico de comentário público sobre profissional de saúde e ainda assim dá o
  retorno a quem precisa dele.
- **Acessibilidade declarada pelo paciente** chega ao profissional antes da consulta, com
  consentimento explícito e acesso restrito.

---

## 11. Riscos e pontos cegos

> Preservar esta seção mesmo quando ela contrariar o que se quer acreditar.

### Uma pessoa só no backend é o caminho crítico

A divisão é por camada: Cipriano no front, Sidney no back, Mariana e Nicolle na escrita, Murilo
e dupla revisando. Todo o sistema depende de um integrante. Se o Sidney atrasar, adoecer ou
travar numa parte difícil do Laravel, não há segunda pessoa que consiga continuar de onde ele
parou — e quem revisa não conhece o código de dentro. **O que reduz:** a fundação (migrations,
models, autenticação, seeders) feita pelo Murilo nos primeiros dias, o que desbloqueia os dois
lados de uma vez; revisão em Pull Request antes do merge, em vez de reescrita depois; e o front
trabalhando contra os seeders desde o dia 1, sem esperar regra de negócio pronta.

### Revisar reescrevendo custa mais caro do que parece

"A gente pega o que eles fizerem e arruma" cria três problemas ao mesmo tempo: duas pessoas
mexendo no mesmo arquivo (conflito de merge), quem produziu descobrindo que o trabalho dele foi
substituído (desmotivação), e o revisor virando o segundo gargalo. **O que reduz:** revisar em
PR, comentando o que mudar, antes de entrar na `main`.

### O design promete um produto maior que o escopo

O PDF mostra Exames, Receitas, Atestados, Prontuários e Relatórios no menu. Se essas telas
forem construídas, o escopo dobra e nada fica pronto; se ficarem no menu sem funcionar, a banca
clica e encontra link morto. **O que reduz:** redesenhar os menus agora, antes da primeira tela.

### "Verificação de CRM" é a promessa mais frágil do TCC

É o diferencial mais citado e o menos sustentado: a verificação é humana. Se o texto do trabalho
ou a tela disserem "validação automática junto ao CFM", é falso e a banca pode cobrar.
**O que reduz:** tratar a limitação como conteúdo — um trecho do TCC explicando por que a API
oficial (paga, exige CNPJ) ficou fora e como a fila de aprovação supre.

### Dado de deficiência é dado sensível

Tratado com displicência — num campo solto da tabela de pacientes, visível em listagem — vira
o problema mais sério do projeto sob a LGPD. **O que reduz:** tabela separada, consentimento
registrado, acesso restrito por Policy, e uma seção no TCC explicando a escolha.

### Ninguém do grupo entregou projeto em Laravel antes

O nível declarado é "tutorial / CRUD básico". Policies, Mail, scheduler e relacionamentos N:N
com atributos são coisas que ninguém ainda fez. **O que reduz:** fundação feita por uma pessoa
só nos primeiros dias, e as partes difíceis (Policy, Mail, scheduler) concentradas em uma frente
única em vez de espalhadas.

---

## 12. Prioridades recomendadas

1. Fundação: migrations, models, relacionamentos, seeders e autenticação com papéis. Nada
   começa antes disso.
2. Redesenhar os menus laterais para refletir o escopo real.
3. Fluxo do paciente ponta a ponta: buscar → agendar → minhas consultas → cancelar.
4. Área do médico: vínculos, preço, disponibilidade, agenda.
5. Área da clínica e do admin: aprovação de CRM, unidades, alocação por especialidade.
6. E-mails e scheduler.
7. Avaliações.
8. Integração, testes e documentação — uma semana inteira, reservada de verdade.

---

## 13. Informações ainda não consolidadas

Não tratar como definido até atualização formal deste documento.

- **Versão do Laravel** — a definir no dia 1 e registrar no `AGENTS.md` §3.
- **Horário de disparo do lembrete "no dia"** — sugestão de 7h, não confirmada.
- **Coparticipação em consulta por convênio** — registrar valor ou deixar R$ 0,00? Não decidido.
- **Quais clínicas fictícias vão existir na demonstração** — nomes, endereços, especialidades e
  tabela de preços de cada uma. Precisa estar definido antes do seeder.
- **Critério das especialidades em destaque na home** — as mais buscadas, as com mais médicos
  cadastrados, ou uma lista curada pelo admin? Por ora, campo `destaque` na tabela.

---

## 14. Contexto curto para uso em IA

```text
FacilMed é uma plataforma web de agendamento de consultas médicas, TCC de um grupo de 4+ alunos
com entrega em 20/10/2026. Stack: PHP 8.2+ com Laravel, Blade + Tailwind + Alpine.js, MySQL,
rodando localmente em XAMPP. Está em desenvolvimento; nada foi publicado.

O paciente busca por especialidade, vê preço da consulta particular, convênios aceitos naquele
endereço e horários livres, e agenda sozinho — com médico específico ou escolhendo clínica +
especialidade (aí o sistema aloca um médico e mostra o nome antes de confirmar). Médicos e
clínicas se cadastram; o médico só aparece na busca depois que a equipe confere o CRM.

Tom: direto, transparente, acolhedor sem intimidade, calmo. Segunda pessoa, frase curta.
Evitar: urgência fabricada, jargão de sistema, emoji em e-mail, e qualquer palavra que sugira
leitura clínica ("estável", "risco", "alerta de saúde").

A IA NÃO deve inventar: preço (vive em vinculos.valor_particular), operadora de plano (vive na
tabela operadoras_ans, importada dos dados abertos da ANS), horário livre (calculado a partir
de disponibilidades menos consultas menos bloqueios) ou validade de CRM (conferida por pessoa,
registrada em medicos.status_verificacao).

Proibições absolutas: nunca dizer que o CRM foi validado junto ao CFM ou a carteirinha junto à
operadora — as duas conferências são humanas; nunca gerar conteúdo clínico de qualquer tipo
(diagnóstico, receita, atestado, prontuário, status de saúde); nunca expor comentário de
avaliação para o público; nunca expor dado de deficiência fora do contexto da consulta.

Em caso de conflito entre documentos, AGENTS.md vence.
```

---

## 15. Regra de manutenção deste documento

Ao atualizar:

1. registrar a nova data no início;
2. **substituir** a informação antiga, em vez de manter versões conflitantes lado a lado;
3. distinguir decisão final de teste e de ideia — ideia vai para a §13;
4. conferir todo dado operacional contra a **fonte da verdade** (tabela do topo), nunca contra
   a memória nem contra uma versão anterior deste arquivo;
5. documentar qualquer mudança que afete quem usa, quem produz ou quem comunica;
6. **preservar a seção de riscos**, mesmo quando ela contrariar decisões desejadas.
