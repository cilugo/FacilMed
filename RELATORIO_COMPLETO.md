# RELATÓRIO COMPLETO — FacilMed

**Data deste relatório:** 18 de setembro de 2026
**Entrega do TCC:** 20 de outubro de 2026 · **restam 32 dias**
**Responsável:** Murilo Martins Souza · **Grupo:** 6 pessoas

---

## 0. Como usar este documento

**Se você é um integrante do grupo:** leia as seções 1, 3, 8 e 12. A seção 8 diz o que é
seu. A 12 diz como deixar sua máquina pronta.

**Se você é uma IA assumindo este projeto:** leia tudo, na ordem. Depois leia `AGENTS.md`
(as regras que você não pode quebrar), `AI_HANDOFF.md` (o estado mais recente) e `PERFIL.md`
(o negócio e o tom). **Em caso de conflito entre documentos, o `AGENTS.md` vence.**

A seção 2 é a mais valiosa deste relatório: ela registra **por que** cada decisão foi
tomada. Sem ela, quem chegar depois reabre discussões já encerradas e repete erros já
investigados.

---

## 1. O projeto em uma página

O FacilMed é uma plataforma web de agendamento de consultas médicas. A pessoa busca por
especialidade, vê quem atende perto dela, **quanto custa**, quais convênios o profissional
aceita e quais horários estão livres — e marca sozinha, sem telefonar para clínica nenhuma.

Do outro lado, médicos autônomos e clínicas se cadastram, publicam a agenda e recebem os
agendamentos.

**O diferencial não é ser mais uma agenda on-line.** É dar ao paciente, *antes* de marcar,
a informação que hoje ele só descobre no telefone: preço da consulta particular, convênio
aceito naquele endereço, e a avaliação de quem já foi.

### Quem usa

| Papel | O que faz |
|---|---|
| **Paciente** | Busca, agenda, cancela, remarca, cadastra carteirinha, avalia |
| **Médico** | Publica perfil, especialidades, locais, preços, horários, ausências |
| **Clínica** | Cadastra unidades e médicos, define a tabela de preços, vê a agenda |
| **Admin** | Verifica CRM, confere carteirinha, gerencia convênios e contas |

### O que o projeto **não** é

Delimitação rígida — está no `AGENTS.md` §2 e vale para qualquer pessoa ou IA:

- **Não é prontuário eletrônico.** Nada de evolução clínica, diagnóstico, receita, atestado
  ou resultado de exame. Prontuário eletrônico é sistema regulado pelo CFM.
- **Não é telemedicina.** Sem videochamada.
- **Não processa pagamento.** Exibe o valor; o pagamento é presencial.
- **Não integra com o SUS.**
- **Não emite parecer clínico de nenhum tipo.**
- **Não armazena documento médico.** Nenhum upload de laudo.

---

## 2. Linha do tempo das decisões — e o porquê de cada uma

> Esta é a seção que o Git não guarda. Se você vai mudar alguma dessas decisões, leia o
> motivo primeiro: quase todas foram tomadas contra uma alternativa que parecia melhor.

### 2.1 Refazer em Laravel, em vez de evoluir o PHP puro

O grupo tinha um protótipo em PHP puro com cerca de 70 arquivos funcionando: login,
cadastro, agendamento, dashboard do médico, painel admin.

**Decisão:** refazer em Laravel.

**Por quê:** o protótipo tinha furos estruturais de *modelagem*, não de código — médico sem
vínculo com local nem convênio, uma especialidade por médico, preço sem lugar para existir,
sem avaliações. E a organização de pastas misturava página com backend (`php/medico/dashboard.php`
era uma página, não um endpoint).

**Alternativa descartada:** corrigir o antigo incrementalmente. Sairia mais barato em horas —
estimei 90 a 110 horas contra 170 — mas não daria migrations versionadas, que um grupo de 6
pessoas precisa para não destruir o banco um do outro.

**Ressalva honesta:** eu recomendei *manter* o PHP puro depois de ler o código e ver o
tamanho do que já existia. O Murilo escolheu Laravel mesmo assim, ciente da conta de horas.
A decisão está tomada; o risco está registrado na seção 10.

**O que foi transplantado do projeto antigo:** a coluna virtual `horario_ativo` (ver §5.3),
o desenho da tabela de recuperação de senha, e o entendimento do domínio.

### 2.2 Laravel 12, não 13

**Decisão:** `composer create-project laravel/laravel:^12.0 facilmed`

**Por quê:** o Laravel 13 (atual, março/2026) exige **PHP 8.3**. O XAMPP parou no **PHP
8.2.12**, de novembro de 2023 — não existe XAMPP com 8.3. O Laravel 12 roda em PHP 8.2 e
tem correções de segurança até **fevereiro de 2027**, bem depois da entrega.

**Alternativa descartada:** instalar o PHP avulso do windows.php.net e configurar o
`php.ini` à mão. Daria PHP 8.4 e Laravel 13, mas são seis pessoas editando arquivo de
configuração e habilitando extensão por extensão — gera mais problema do que resolve.

⚠ **Sem o `:^12.0` no comando, o Composer baixa o 13 e falha com erro de versão de PHP.**

### 2.3 Blade + Tailwind + Alpine.js, sem SPA

**Por quê:** 32 dias e um grupo em nível intermediário. Renderização no servidor, JavaScript
só onde precisa (calendário, filtros).

**Alternativas descartadas:** Livewire (mais uma abstração para aprender e depurar) e
API + React/Vue separado (dois projetos, autenticação por token, CORS — dobra o trabalho).

### 2.4 Verificação de CRM: aprovação humana, não automática

**Por quê:** investiguei e **não existe caminho gratuito**. O web service oficial do CFM
custa R$ 772/ano, exige CNPJ com representante legal cadastrado no SEI e leva até 10 dias
úteis para liberar. Gratuito apenas para entidade pública.

**Como ficou:** o médico se cadastra, entra com `status_verificacao = 'pendente'` e **não
aparece na busca**. Um admin confere o CRM no portal do CFM e aprova. No código existe um
adaptador mockado e o esqueleto documentado da integração real.

**O Murilo escolheu inicialmente só o mock.** Ficou mock **mais** a fila de aprovação
real, porque "é um mock" é uma resposta fraca na banca e a fila custa quase nada — o enum
de status já existia no schema antigo.

⚠ **A interface nunca pode dizer "validado junto ao CFM".** Diz "verificado pela equipe
FacilMed".

### 2.5 Operadoras reais (ANS), planos fictícios

**Por quê:** esta é a **única validação externa real** do projeto, e é gratuita. A ANS
publica a lista de operadoras ativas em dados abertos. Importamos o CSV para a tabela
`operadoras_ans`, e nenhum convênio pode existir sem apontar para uma operadora real.

Os **planos** dentro de cada operadora são inventados, porque o banco é simulado.

**Isso precisa estar visível na tela**, senão parece que a plataforma tem contrato assinado
com a Unimed.

### 2.6 Carteirinha: conferência humana pelo comprovante COMPROVA

**Por quê:** não existe forma de validar por algoritmo. Cartão de crédito tem o algoritmo
de Luhn; carteirinha de plano de saúde **não tem dígito verificador nacional** — o padrão
TISS deixa o formato a cargo de cada operadora.

O Murilo sugeriu usar o COMPROVA da ANS. Fui verificar: ele existe, mas **exige login
gov.br do próprio beneficiário** e a ANS declara que não compartilha dados com terceiros.
Não há API.

**Como ficou:** o paciente emite o comprovante no portal da ANS e informa o **código de
controle** (8 dígitos) e a data de emissão. A equipe valida esse código num formulário do
site da ANS e marca a carteirinha como ativa.

**Guardamos apenas o código e a data — nunca o PDF do comprovante**, que traz CPF, nome da
mãe e características do plano. Isso é minimização de dados (LGPD) e rende um bom parágrafo
no TCC.

⚠ O comprovante prova que a pessoa é beneficiária daquela operadora. **Não confirma o
número da carteirinha que ela digitou.**

### 2.7 A plataforma não processa pagamento

**Por quê:** gateway traria status de pagamento, webhook, estorno e cancelamento com
reembolso — uma frente inteira que não é a tese do trabalho. Consulta particular é paga
presencialmente, no valor exibido.

### 2.8 Convênio vinculado ao MÉDICO, não ao endereço

**Decisão do Murilo, contra a recomendação técnica.** Registrada aqui com honestidade.

**O que isso significa:** se o Dr. Carlos aceita Unimed, ele aparece na busca por Unimed
em *todos* os endereços onde atende — inclusive naquele onde, na vida real, o plano não
vale.

**Mitigação obrigatória:** toda tela de confirmação de consulta por convênio exibe
*"Confirme na recepção se o seu plano é aceito neste endereço."* Isso está no `AGENTS.md`
§6 como regra, não como sugestão.

**Ganho:** uma tabela pivot simples (`convenio_medico`) em vez de vínculo com o local.

### 2.9 Preço por vínculo **e** especialidade

**Por quê:** o Murilo pediu duas coisas que se contradiziam — preço pela tabela da clínica
**e** médico com mais de uma especialidade. Se o Dr. Rafael atende cardiologia e clínica
geral na mesma clínica, ele não tem *um* preço: tem dois.

**Como ficou:** tabela `precos (vinculo_id, especialidade_id, valor)`. O preço depende de
três coisas: qual médico, em qual endereço, para qual especialidade.

**Quem edita:** se o local pertence a uma clínica, a clínica. Se é consultório próprio, o
médico.

### 2.10 Sem upload de laudo ou diagnóstico

**Pedido inicial do Murilo, revertido por ele mesmo depois dos riscos.**

**Por quê:** a finalidade declarada é adequar o atendimento (rampa, intérprete, tempo maior).
Para isso, o que o profissional precisa saber é **o que a pessoa precisa** — o laudo não
acrescenta nada à finalidade e acrescenta muito risco: dado sensível de saúde na categoria
mais forte da LGPD, e arquivo em `storage/app/public` fica acessível por URL direta.

**Como ficou:** campo de texto opcional, com consentimento explícito registrado, em tabela
separada (`paciente_acessibilidade`).

### 2.11 Cancelamento abaixo de 24h é permitido e registrado, não bloqueado

**Por quê:** bloquear não faz a pessoa comparecer — faz ela faltar. E falta perde o horário,
enquanto cancelamento devolve para outro paciente.

**Como ficou:** cancelar é sempre permitido; abaixo de 24h marca
`cancelamento_tardio = true` no histórico.

### 2.12 Comentário de avaliação é privado

**Decisão do Murilo.** A nota em estrelas é pública; o comentário vai apenas para o médico
avaliado, a clínica dele e o admin.

**Por quê é bom:** dá o retorno a quem precisa dele e reduz o risco jurídico de comentário
público sobre profissional de saúde.

### 2.13 SUS e Teleconsulta fora do escopo

Decisão do Murilo em 18/09/2026.

---

## 3. Estado atual — inventário completo

### 3.1 O que **não** existe ainda

**O projeto Laravel não foi criado.** Não há `composer.json`, não há `vendor/`, não há banco.
Nada do que está escrito foi executado nenhuma vez.

Motivo: a máquina do Murilo tinha **PHP 8.0.7 (junho de 2021)**, e nem `composer` nem `npm`
estavam instalados. A configuração do ambiente é o passo pendente (seção 12).

### 3.2 O que existe — cerca de 93 arquivos

**Documentação de governança** — em `FacilMed/FacilMed-main/`

| Arquivo | O que é |
|---|---|
| `AGENTS.md` | **Fonte canônica.** Escopo, stack, fontes da verdade, como rodar, regras invioláveis, protocolo de handoff. Vence em caso de conflito. |
| `PERFIL.md` | Negócio, público, oferta, operação, identidade visual, tom de voz, riscos |
| `AI_HANDOFF.md` | Estado, decisões, becos sem saída, o que falta |
| `CLAUDE.md` | Versão curta apontando para o AGENTS.md |

**Código Laravel** — em `FacilMed/_novo_laravel/` (e no `facilmed-laravel.zip`)

| Camada | Qtd | Onde |
|---|---|---|
| Migrations | 21 | `database/migrations/` |
| Models Eloquent | 19 | `app/Models/` |
| Seeders | 8 | `database/seeders/` |
| Comando Artisan (importação ANS) | 1 | `app/Console/Commands/` |
| Middlewares | 2 | `app/Http/Middleware/` |
| Controllers | 34 | `app/Http/Controllers/` |
| Rotas | 1 | `routes/web.php` |
| Configuração de navegação | 1 | `config/navegacao.php` |
| Componente Blade (sidebar) | 1 | `resources/views/components/` |
| Guias de instalação | 2 | `PASSO_A_PASSO.md`, `COMO_USAR_ESTES_ARQUIVOS.md` |

**Status de validação:** a sintaxe PHP de todos os arquivos foi verificada com `php -l`.
**Nenhum foi executado.** Quatro controllers tinham um `use` duplicado (erro fatal) que foi
encontrado e corrigido nessa verificação.

**Material de design** — o `Descricao_Visual_FacilMed.pdf`, três mockups de dashboard e três
variações da logo (`imgs/`).

---

## 4. Arquitetura e modelo de dados, explicados

### 4.1 A tabela central: `vinculos`

Se você entender só uma coisa deste modelo, entenda esta.

Um **vínculo** liga um médico a um local de atendimento. Tudo que depende de *"onde o médico
atende"* pendura nele:

```
medico ──┐
         ├── vinculo ──┬── precos (por especialidade)
local ───┘             ├── disponibilidades (blocos de horário)
                       └── consultas
```

**Por que isso importa:** sem essa tabela, o sistema não sabe em qual endereço o médico está
às 14h de terça — e aceita agendar num hospital onde ele nunca atendeu. Era exatamente o furo
do banco antigo.

### 4.2 As 21 tabelas, por grupo

**Identidade**

- `users` — login comum: nome, email, senha, `tipo` (paciente/medico/clinica/admin),
  telefone, `status` (ativo/inativo/bloqueado), motivo e autor do bloqueio.
  **CPF e CNPJ não ficam aqui de propósito** — pessoa tem CPF, clínica tem CNPJ, então cada
  documento mora no perfil correspondente.
- `pacientes` — cpf, data de nascimento, sexo
- `medicos` — cpf, crm, uf, `status_verificacao`, bio, telefone profissional, foto, e o
  cache `media_avaliacoes` / `total_avaliacoes`
- `clinicas` — cnpj, razão social, nome fantasia, descrição, logo
- `paciente_acessibilidade` — **tabela separada de propósito** (ver §2.10)

**Locais e agenda**

- `locais` — tem `clinica_id` **ou** `medico_id`, exatamente um dos dois (garantido por
  `CHECK` no banco). Se `clinica_id` é nulo, é consultório próprio de autônomo.
- `horarios_funcionamento` — horário do *lugar*, por dia da semana
- `vinculos` — a tabela central
- `precos` — vínculo + especialidade + valor
- `disponibilidades` — blocos recorrentes semanais, presos ao vínculo.
  ⚠ **O almoço não tem campo próprio:** são dois blocos no mesmo dia (08:00–12:00 e
  14:00–18:00), e o buraco entre eles é o almoço. Menos uma tabela para manter.
- `bloqueios` — férias, feriado, imprevisto

**Especialidades**

- `especialidades` — com campo `destaque`, que alimenta os cards da home
- `medico_especialidade` — N:N, porque um médico pode atuar em mais de uma área

**Convênios**

- `operadoras_ans` — importada do CSV da ANS, nunca digitada à mão
- `convenios` — aponta obrigatoriamente para uma operadora real
- `planos` — fictícios
- `convenio_medico` — quais convênios o médico aceita
- `paciente_planos` — a carteirinha, com `codigo_comprova_ans` e `status`

**Consultas**

- `consultas` — paciente, médico, vínculo, especialidade, data, horário, forma de pagamento,
  valor, `status` (agendada/realizada/cancelada/nao_compareceu), `origem` (medico/clinica),
  dados de cancelamento
- `avaliacoes` — `consulta_id UNIQUE`, estrelas 1–5, comentário
- `notificacoes_enviadas` — `UNIQUE (consulta_id, tipo)`

### 4.3 Como o horário livre é calculado

**Isto é calculado, nunca armazenado.** Não crie tabela de "slots": ela desincroniza na
primeira mudança de agenda.

```
blocos de `disponibilidades` do dia da semana
  fatiados pela duracao_consulta_minutos
  MENOS as consultas não canceladas naquele médico/data/horário
  MENOS qualquer intervalo em `bloqueios`
  DENTRO do horário de funcionamento do local
  E no futuro (não oferecer horário que já passou hoje)
```

É a parte mais fácil de errar do sistema inteiro. Precisa virar
`App\Services\CalculadoraDeHorarios` e ter teste.

---

## 5. As regras invioláveis (e por que existem)

Estão no `AGENTS.md` §6. Cada uma precisa estar refletida numa **constraint de banco, numa
Policy ou num FormRequest** — não só na intenção de quem escreveu a tela.

### 5.1 Nada de conteúdo clínico

**Nunca** exibir, calcular ou gerar avaliação clínica sobre o paciente — incluindo "status
de saúde", "pressão arterial: normal", "medicamentos em uso", alerta de saúde ou sugestão
de diagnóstico.

⚠ **Os mockups de vocês têm isso.** O PDF traz um card "Status da sua saúde: Estável", e o
mockup mobile do paciente traz "Resumo da sua saúde — Pressão arterial 120/80 mmHg — Normal".
**Não construam essas telas.** Não há de onde tirar esse dado, e classificá-lo é
interpretação clínica. É a falha mais grave que este projeto pode cometer.

### 5.2 Honestidade sobre verificação

- Médico com `status_verificacao != 'verificado'` **nunca** aparece em busca ou listagem
  pública. Use o scope `Medico::visivel()` — não escreva o `where` à mão.
- **Nunca** dizer "validado junto ao CFM" nem "validado junto à operadora". As duas
  conferências são humanas.
- **Nunca** criar convênio sem operadora real da ANS.

### 5.3 O índice que impede duplo agendamento

```sql
ALTER TABLE consultas
  ADD COLUMN horario_ativo TIME
    GENERATED ALWAYS AS (CASE WHEN status <> 'cancelada' THEN horario ELSE NULL END) VIRTUAL,
  ADD UNIQUE KEY uq_consulta_horario_ativo (medico_id, data_consulta, horario_ativo);
```

**Por que existe:** entre o `SELECT` que checa se o horário está livre e o `INSERT` que
grava, outra pessoa pode marcar o mesmo horário. As duas requisições passam na checagem.
O banco é a única proteção real.

**Por que a coluna é virtual:** quando a consulta é cancelada, o valor vira `NULL`, e o
MySQL/MariaDB não considera `NULL`s duplicados entre si — então o horário volta a ficar
livre para reagendamento.

⚠ **Nunca remova esse índice para fazer o `migrate` passar.** Se der erro no MariaDB, troque
`VIRTUAL` por `PERSISTENT`.

O `AgendamentoController` já tem o `try/catch` de `QueryException` esperando o erro `23000`
e devolvendo *"esse horário acabou de ser preenchido"*.

### 5.4 Privacidade

- O **comentário** de avaliação nunca é público. Três telas autorizadas: médico avaliado,
  clínica dele, admin.
- `paciente_acessibilidade` só é lida pelo profissional **com consulta agendada** com aquele
  paciente. Nunca em listagem, busca, exportação ou log.
- Avaliação só de consulta com status `realizada`, e só pelo paciente dela.

### 5.5 E-mail

**Nunca** enviar sem gravar em `notificacoes_enviadas`. O `UNIQUE (consulta_id, tipo)` é o
que impede o mesmo lembrete sair 24 vezes quando o scheduler roda de hora em hora.

⚠ Depois de qualquer teste com envio real, **limpe as linhas de teste** — senão o lembrete
verdadeiro daquela consulta nunca sai, porque o UNIQUE bloqueia.

### 5.6 Contas e senhas

- Senha: **mínimo 8, máximo 72** (limite do bcrypt). **Não existe limite de 10 caracteres** —
  limitar o tamanho máximo enfraquece sem ganho nenhum.
- Sem regras de composição obrigatória (um maiúsculo, um símbolo): produzem senhas
  previsíveis. Comprimento vence complexidade.
- Toda validação em FormRequest. **Nunca só no JavaScript.**
- Login com `throttle`.
- Conta bloqueada é barrada no middleware, a cada requisição — não escondida na interface.

---

## 6. Becos sem saída — já investigados, não repita

> Esta seção existe para economizar dias de quem chegar depois.

| Tentativa | Por que não funciona |
|---|---|
| **Validar CRM programaticamente de graça** | O web service do CFM custa R$ 772/ano, exige CNPJ e representante legal no SEI, e leva até 10 dias úteis. Não procure "API gratuita do CFM"; não faça scraping do portal (frágil e juridicamente cinzento num trabalho acadêmico). |
| **Validar carteirinha por algoritmo** | Não existe dígito verificador nacional. O padrão TISS deixa o formato a cargo de cada operadora. |
| **Consultar carteirinha pelo COMPROVA da ANS** | Exige conta gov.br **do próprio beneficiário**, é site e não API, e a ANS não compartilha com terceiros. O que existe é o caminho inverso: o beneficiário emite e um terceiro confere o código de controle, manualmente. |
| **Checar horário livre com SELECT antes do INSERT** | Condição de corrida: duas requisições simultâneas passam as duas. Era o bug do protótipo antigo. Use o índice único (§5.3). |
| **XAMPP com PHP 8.3+** | Não existe. O XAMPP parou no 8.2.12, em novembro de 2023. Por isso usamos Laravel 12. |

---

## 7. O que falta fazer

### 7.1 Bloqueadores — travam todo mundo

- [ ] Configurar o ambiente: PHP 8.2, Composer, Node (seção 12)
- [ ] `composer create-project laravel/laravel:^12.0 facilmed`
- [ ] Breeze (`composer require laravel/breeze --dev` + `php artisan breeze:install blade`)
- [ ] Copiar os arquivos do `_novo_laravel/` e rodar `php artisan migrate`
- [ ] Registrar os middlewares em `bootstrap/app.php`
- [ ] `php artisan db:seed`
- [ ] Repositório Git com todo mundo clonando
- [ ] Redesenhar os menus laterais conforme `config/navegacao.php`

### 7.2 Núcleo do sistema

- [ ] **FormRequests** de cadastro (paciente, médico, clínica) com as regras da §5.6
- [ ] **Policies**: `ConsultaPolicy`, `AvaliacaoPolicy`, `PacienteAcessibilidadePolicy`,
      `VinculoPolicy`, `PrecoPolicy`
- [ ] **`App\Services\CalculadoraDeHorarios`** — a conta da §4.3, com teste
- [ ] **`App\Services\AlocadorDeMedico`** — escolhe o médico quando o paciente marca pela
      clínica; regra de desempate: quem tem mais horário livre no dia
- [ ] Perfil do médico: especialidades, vínculos, preços, disponibilidade, bloqueios
- [ ] Perfil da clínica: unidades, horários, médicos (com senha temporária)
- [ ] Admin: aprovação de CRM, conferência de carteirinha, bloqueio de conta
- [ ] Busca com filtros
- [ ] Agendamento direto e por especialidade
- [ ] Minhas consultas: cancelar, remarcar

### 7.3 Views Blade — nada existe ainda

- [ ] Layout base + componente `x-icone` (**a sidebar que entreguei depende dele**)
- [ ] Componentes: card, botão, badge de status, campo de formulário, paginação
- [ ] Home, busca, perfil público de médico e clínica
- [ ] Telas de paciente, médico, clínica e admin
- [ ] Telas de cadastro dos três tipos

### 7.4 E-mails

- [ ] Três Mailables: confirmação, lembrete 24h antes, lembrete no dia
- [ ] Comando Artisan que varre as consultas e dispara
- [ ] Registro no scheduler (`routes/console.php`)
- [ ] Gravação em `notificacoes_enviadas` a cada envio

⚠ **Regra de deduplicação:** consulta marcada para daqui a 6 horas não deve receber
"confirmação", "24h antes" e "no dia" quase juntos. Só envie o lembrete de 24h se faltar
mais de 24h no momento do agendamento.

### 7.5 Fechamento

- [ ] Testes do fluxo crítico: agendar, conflito de horário, cancelar, avaliar
- [ ] README de verdade (o atual tem uma linha)
- [ ] Documentação do TCC
- [ ] Ensaio da apresentação com o roteiro do `AGENTS.md` §5

### 7.6 Lacunas conhecidas entre o design e o banco

| O que o mockup mostra | Situação |
|---|---|
| Gráfico "Acessos ao sistema" (admin) | **Não há tabela de log.** Criar `logs_acesso` ou tirar o gráfico. **Falha minha** — não incluí nas migrations. |
| Status "Em espera" | Não existe no enum. Decidir se há um passo de confirmação pela clínica. |
| "Média de espera: 12 min" | Exige registrar check-in e início do atendimento. Não está no modelo. |

---

## 8. Divisão do grupo

Definida pelo Murilo. Os riscos estão na seção 10 — a divisão foi mantida por escolha dele,
e as mitigações abaixo não a alteram.

### Sidney — Back-end

**Semana 1:** ajudar na fundação, entender as migrations e os models (leia os comentários —
cada decisão está explicada lá).

**Semanas 2–4:**
- `CalculadoraDeHorarios` e `AlocadorDeMedico` (os dois serviços mais difíceis)
- Agendamento: criar, cancelar, remarcar
- Policies e FormRequests
- E-mails e scheduler

**Leia antes de começar:** seções 4.3, 5.3 e 5.5 deste relatório.

### Cipriano — Front-end

**Semana 1 — não espere o back:**
- Layout base e o componente `x-icone` (**a sidebar depende dele**)
- Componentes: card, botão, badge de status, campo de formulário
- Paleta da logo: azul-marinho `#1e3a8a` no menu, azul-claro `#3b82f6` no ativo, cards
  brancos arredondados, fundo claro

**Semanas 2–4:** todas as telas, contra os dados dos seeders.

**Leia antes:** `PERFIL.md` §8 (identidade visual) e `config/navegacao.php`.
⚠ **Não construa as telas de Exames, Receitas, Atestados, Prontuários, Documentos nem
"Resumo da saúde".** Veja §5.1.

### Mariana — Documentação + área administrativa

**Escrita (comece hoje, não espere o sistema):** o `PERFIL.md` e este relatório já têm
justificativa, decisões e alternativas descartadas — que é a parte mais difícil de escrever
depois, quando ninguém lembra por que decidiu o quê.

**Se sobrar tempo:** a área do admin é a mais CRUD e a mais fácil de aprender Laravel.

### Nicolle — Documentação + qualidade

- Textos da interface e mensagens de erro (é trabalho real, não enfeite: mensagem de erro
  ruim é reclamação na banca)
- Roteiro de teste manual do `AGENTS.md` §5, executado a cada semana
- Dados de demonstração: conferir se os seeders geram cenário convincente
- Escrita do TCC junto com a Mariana

### Murilo — Fundação + revisão

**Semana 1:** montar o projeto, rodar as migrations, criar o repositório, resolver os
problemas de ambiente dos outros.

**Semanas 2–5:** revisão de Pull Request **antes** do merge, e as partes difíceis que
sobrarem.

⚠ **Não reescreva o código dos outros depois que já está na `main`.** Comente no PR. Três
motivos: evita conflito de merge, não desmotiva quem produziu, e — o que mais importa na
banca — quem teve o código substituído não sabe explicar o que está lá.

### Regras para todos

1. **Branch por frente. Ninguém commita na `main`.**
2. **Migration já aplicada não se edita** — cria-se uma nova. Editar uma que já rodou quebra
   o banco dos outros silenciosamente.
3. **Commits pequenos, uma intenção por commit.**
4. **Atualize o `AI_HANDOFF.md` a cada checkpoint.**
5. **Regra de negócio mora em Policy, FormRequest, Model ou constraint** — nunca em `if`
   dentro de Blade.

---

## 9. Cronograma até 20/10

| Período | Foco | Entregável |
|---|---|---|
| **18–24/09** | Fundação | Projeto rodando, banco populado, repositório, layout base |
| **25/09–01/10** | Cadastros e perfis | Os três cadastros funcionando, perfil do médico e da clínica, admin aprovando CRM |
| **02–08/10** | O núcleo | Busca, horários livres, agendamento, cancelar, remarcar. **Semana mais pesada — proteja ela** |
| **09–15/10** | Fechamento funcional | E-mails, avaliações, dashboards |
| **16–20/10** | Integração | Testes, documentação, ensaio. **Não construa nada aqui** |

**Regra de priorização:** faça o **caminho feliz inteiro** antes de caprichar em qualquer
tela. Cadastrar → buscar → agendar → ver em "minhas consultas", mesmo feio.

Se no dia 10 de outubro vocês tiverem tudo pela metade, não têm nada para apresentar. Se
tiverem o fluxo completo funcionando e feio, já têm TCC — e sobra tempo para embelezar.

---

## 10. Riscos

### Uma pessoa só no backend é o caminho crítico

Todo o sistema depende do Sidney. Se ele travar numa parte difícil, adoecer ou sumir, não
existe ninguém que continue de onde ele parou — e quem revisa não conhece o código por
dentro.

**Mitigação:** o Murilo faz a fundação nos primeiros dias, desbloqueando os dois lados de
uma vez; revisão em PR antes do merge; e o front trabalhando contra os seeders desde o dia 1.

### Ninguém do grupo entregou projeto em Laravel

Policies, Mail, scheduler e relacionamentos N:N com atributos são novidade para todos. Essa
curva não aparece no cronograma, mas aparece na semana 2.

**Mitigação:** os comentários no código explicam *por que*, não só *o quê*. Leiam.

### O design promete um produto maior que o escopo

Se as telas do mockup forem construídas como estão, o escopo dobra. Se ficarem no menu sem
funcionar, a banca clica e encontra link morto.

**Mitigação:** `config/navegacao.php` já está corrigido. Use-o.

### "Verificação de CRM" é a promessa mais frágil do TCC

É o diferencial mais citado e o menos sustentado: a verificação é humana.

**Mitigação:** trate a limitação como conteúdo — um trecho do TCC explicando por que a API
oficial ficou fora e como a fila de aprovação supre. Isso vale mais que fingir automação.

### Nada do que foi escrito rodou

93 arquivos, zero execuções. Erros que `php -l` não pega só aparecem no `migrate`.

**Mitigação:** rodar o quanto antes, com poucos arquivos, em vez de acumular mais camadas
por cima de código não testado.

---

## 11. Decisões ainda abertas

| Assunto | Situação |
|---|---|
| Gráfico "Acessos ao sistema" | Criar `logs_acesso` ou tirar da tela? |
| Status "Em espera" | Existe um passo de confirmação pela clínica? |
| Check-in e tempo de espera | Entra no escopo? |
| Horário do lembrete "no dia" | Sugestão: 7h. Não confirmado |
| Coparticipação em consulta por convênio | Registrar valor ou deixar R$ 0,00? |
| Versão exata do Laravel | Registrar no `AGENTS.md` §3 depois de instalar |

---

## 12. Como preparar sua máquina

**Todo integrante precisa fazer isto.** São uns 30 minutos.

### 1. PHP 8.2

O XAMPP que circulou no grupo tem **PHP 8.0.7, de 2021** — não serve. Confira:

```cmd
C:\xampp\php\php.exe -v
```

Se der menos de 8.2, baixe o **XAMPP 8.2.12** em https://www.apachefriends.org e instale em
**pasta separada** (`C:\xampp82`), não por cima — assim você não perde os bancos nem o
projeto antigo.

### 2. PATH

Variáveis de ambiente do sistema → `Path` → Novo → `C:\xampp82\php`

**Feche o terminal e abra outro.** O PATH não atualiza em janela já aberta — é o erro mais
comum aqui.

```cmd
php -v
```

Se ainda mostrar 8.0.7, o caminho antigo está acima na lista. Mova o novo para cima.

### 3. Composer e Node

- Composer: https://getcomposer.org/Composer-Setup.exe — aponte para `C:\xampp82\php\php.exe`
- Node LTS: https://nodejs.org

### 4. Conferir

Terminal novo:

```cmd
php -v        # 8.2.x
composer -V
node -v
npm -v
```

⚠ **Dois XAMPP instalados brigam pela porta 3306.** Ligue o MySQL de um painel por vez.

### 5. Montar o projeto

O passo a passo completo, com o que fazer se cada etapa falhar, está em
`_novo_laravel/PASSO_A_PASSO.md`.

**Contas de teste** (senha de todas: `facilmed2026`):

| Papel | E-mail |
|---|---|
| Admin | `admin@facilmed.test` |
| Clínica | `contato@vidaplena.test` |
| Médico verificado | `helena@facilmed.test` |
| Médico pendente | `andre@facilmed.test` |
| Paciente | `ana@facilmed.test` |

### 6. Conferir que funcionou

```bash
php artisan migrate:status              # todas "Ran"
php artisan tinker
>>> \App\Models\Medico::count()          # 6
>>> \App\Models\Medico::visivel()->count()   # 5
```

Se der 6 e 6, a regra "médico não verificado não aparece na busca" quebrou — e é uma das
invioláveis.

⚠ **As views ainda não existem.** Ao logar, você vai ver `View [paciente.dashboard] not
found`. **Isso é esperado**, e significa que autenticação, middleware e roteamento estão
certos.

---

## 13. Prompt para a próxima IA

```text
Você vai continuar o FacilMed, um TCC de agendamento de consultas médicas em Laravel 12.

Leia, nesta ordem: RELATORIO_COMPLETO.md, AGENTS.md, AI_HANDOFF.md e PERFIL.md.
Rode `git log --oneline -10` e `git status`.

Em caso de conflito entre documentos, o AGENTS.md vence.

Antes de editar qualquer arquivo:
1. Resuma o estado atual.
2. Liste os arquivos relevantes para a tarefa.
3. Mostre um plano curto.
4. Aponte os riscos.
5. Aguarde aprovação.

Regras que você não pode quebrar (AGENTS.md §6):
- Nunca gere conteúdo clínico: diagnóstico, receita, atestado, prontuário, nem rótulo sobre
  o estado de saúde do paciente. O PDF de design mostra um card "Status da sua saúde" e um
  "Pressão arterial 120/80 - Normal": NÃO implemente.
- Nunca diga que o CRM foi validado junto ao CFM nem a carteirinha junto à operadora. As
  duas conferências são humanas.
- Nunca remova a coluna virtual `horario_ativo` nem o índice único de `consultas`.
- Nunca exponha o comentário de avaliação para o público, nem o dado de acessibilidade
  fora do contexto da consulta.
- Nunca construa nada que o AGENTS.md §2 exclua do escopo, mesmo que apareça nos mockups.

Não edite .env, não altere credenciais, não execute comandos destrutivos.
Não edite migration já aplicada — crie uma nova.

Antes de encerrar: atualize o AI_HANDOFF.md e feche com commit.
```

---

## 14. Onde está cada coisa

| Assunto | Arquivo |
|---|---|
| Regras técnicas, escopo, o que nunca fazer | `AGENTS.md` |
| Negócio, público, tom, identidade visual | `PERFIL.md` |
| Estado mais recente e próximo passo | `AI_HANDOFF.md` |
| Visão completa (este documento) | `RELATORIO_COMPLETO.md` |
| Instalação passo a passo | `_novo_laravel/PASSO_A_PASSO.md` |
| Onde cada arquivo do zip vai | `_novo_laravel/COMO_USAR_ESTES_ARQUIVOS.md` |
| Estrutura do banco | `database/migrations/` (fonte da verdade) |
| Operadoras de plano de saúde | Tabela `operadoras_ans`, importada da ANS |
| Telas e identidade visual | `Descricao_Visual_FacilMed.pdf` (estilo, **não** escopo) |
| Protótipo em PHP puro | `FacilMed-main/` — **somente leitura, referência histórica** |

---

*Relatório gerado em 18/09/2026. Mantenha-o atualizado: quando uma decisão mudar, altere a
seção 2 e registre o motivo. É o que impede a próxima pessoa — ou a próxima IA — de reabrir
uma discussão já encerrada.*
