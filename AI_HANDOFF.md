# AI Handoff — FacilMed

> Passagem de bastão entre agentes. **Foque no que o Git NÃO captura:** intenção, decisões,
> becos sem saída já testados e o próximo passo.
>
> **Este arquivo é lido a cada sessão: mantenha-o ENXUTO.** Quando uma frente de trabalho
> fechar, arquive o histórico dela em `docs/handoff-arquivo.md` em vez de deixar este crescer.

## ▶ PRÓXIMO PASSO — rodar `composer create-project`, instalar o Breeze e aplicar as migrations que já estão prontas

**Retomar por aqui:** `_novo_laravel/COMO_USAR_ESTES_ARQUIVOS.md` — passo a passo completo.

**Estado exato em 18/09/2026:** **o projeto Laravel ainda não existe**, mas a camada de dados
já está escrita e esperando. Existe em `_novo_laravel/`: 21 migrations (`database/migrations/`)
e 19 models (`app/Models/`), nenhuma delas aplicada — não há `composer.json`, não há
`vendor/`, não há banco criado. Também existem: o protótipo antigo em PHP puro (**somente
leitura**), o `Descricao_Visual_FacilMed.pdf`, os mockups de dashboard e estes quatro
documentos. Nada publicado, nenhum deploy previsto.

**O que ainda NÃO foi escrito:** seeders, comando de importação da ANS, Policies,
FormRequests, controllers, rotas, views Blade, Mails, o comando do scheduler e os testes.

**Decisões já tomadas (não reabrir):** ver "Decisões tomadas". As mais caras de reabrir:
refazer do zero em Laravel, CRM por aprovação humana, sem processamento de pagamento, sem
upload de laudo.

**⚠ Risco número 1 desta frente:** a divisão de trabalho é por camada (um no front, um no back)
e o backend tem **uma pessoa só**. É o caminho crítico do projeto inteiro. Se as telas
começarem antes das migrations estarem commitadas, o front constrói sobre um banco que ainda
muda. Mitigação acordada abaixo, em "Divisão do grupo".

**Pendente do Murilo:** nada bloqueia. Todas as decisões de modelagem estão fechadas.

---

## Estado atual (18/09/2026)

- Branch: — (repositório ainda não criado)
- Último commit: —
- Última ferramenta / agente usada: Claude (Cowork) — requisitos, modelagem e documentação
- "Estado bom" (AGENTS.md §5) passando? **Não se aplica** — não há projeto para rodar
- No ar / publicado agora: **nada**

## Divisão do grupo

Definida pelo Murilo em 18/09/2026:

| Pessoa | Frente |
|---|---|
| Mariana e Nicolle | Parte escrita / documentação do TCC |
| Cipriano | Front-end |
| Sidney | Back-end |
| Murilo (+ dupla) | Revisão e correção do que os outros produzirem |

**Riscos conhecidos desta divisão e como reduzir** — a divisão foi mantida por escolha do
Murilo; estas mitigações não a alteram:

1. **Backend com uma pessoa é o gargalo.** Todo o resto espera por ele. *Mitigação:* o Murilo
   faz a **fundação** (migrations, models, autenticação, seeders) nos primeiros dias, em vez de
   esperar para revisar depois. Isso desbloqueia Cipriano e Sidney no mesmo dia e usa o papel de
   revisor antes do problema, não depois.
2. **O front não pode ficar esperando o back.** *Mitigação:* assim que os seeders existirem,
   Cipriano trabalha contra dados de verdade no banco. Nenhuma tela precisa esperar regra de
   negócio pronta.
3. **"Pegar o que fizeram e arrumar" gera retrabalho e desmotiva.** *Mitigação:* revisar em
   **Pull Request, antes do merge**, comentando o que mudar — em vez de reescrever depois que
   já está na `main`. Mesmo esforço, sem o trabalho de duas pessoas no mesmo arquivo e sem
   alguém descobrindo que o código dele foi substituído.
4. **Duas pessoas só na parte escrita ficam ociosas cedo.** *Mitigação:* Mariana e Nicolle
   assumem também os textos da interface, as mensagens de erro, o conteúdo das páginas
   institucionais e o roteiro de teste manual do AGENTS.md §5. É trabalho real, não enfeite, e
   não exige Laravel.

## Decisões tomadas (e o porquê)

- **Refazer o projeto do zero em Laravel** — o protótipo em PHP puro tem furos estruturais de
  modelagem (sem vínculo médico↔local, uma especialidade por médico, sem preço, sem avaliações)
  e mistura página com backend. *Reaproveitar:* a coluna virtual `horario_ativo`, o desenho da
  recuperação de senha e o entendimento do domínio. Descartado: corrigir o antigo
  incrementalmente — mais caro e sem migrations versionadas, que um grupo desse tamanho precisa.

- **Blade + Tailwind + Alpine.js, sem SPA** — 33 dias, grupo em nível intermediário. Descartados:
  Livewire (mais uma abstração para depurar) e API + SPA (dois projetos).

- **Verificação de CRM por aprovação humana, com adaptador mockado no código** — o web service
  oficial do CFM custa R$ 772/ano, exige CNPJ com representante legal no SEI e leva até 10 dias
  úteis. O Murilo escolheu o mock; ficou mock **mais** fila de aprovação real, porque "é um mock"
  é resposta fraca na banca e a fila custa quase nada. Descartados: API paga de terceiro e
  scraping do portal do CFM.

- **Operadoras reais (ANS), planos fictícios** — as operadoras vêm do CSV de dados abertos da
  ANS, então não existe convênio inventado. Os planos dentro de cada operadora são fictícios,
  porque o banco é simulado. Essa distinção precisa aparecer na tela e no TCC.

- **Carteirinha conferida pelo comprovante COMPROVA da ANS, por pessoa** — o Murilo pediu para
  usar o COMPROVA. Ele existe, mas **não** serve para consulta automática: exige login gov.br
  do próprio beneficiário e a ANS não compartilha com terceiros. O que dá: o paciente emite o
  comprovante, informa o código de controle, e a equipe valida esse código no site da ANS. Fluxo
  humano, nunca automático.

- **A plataforma não processa pagamento** — particular é pago no local; por convênio, a consulta
  já sai marcada sem cobrança na plataforma. Gateway traria status de pagamento, webhook,
  estorno e reembolso: uma frente inteira fora da tese do trabalho.

- **Convênio é aceito pelo MÉDICO, não por endereço** — decisão do Murilo em 18/09/2026, contra
  a recomendação técnica. Simplifica o banco (uma tabela `convenio_medico` em vez de vínculo
  com o local). *Consequência aceita:* o paciente pode marcar num endereço onde, na vida real,
  o plano não vale. *Mitigação obrigatória:* aviso fixo na confirmação de consulta por convênio
  — "Confirme na recepção se o seu plano é aceito neste endereço." Ver AGENTS.md §6.

- **Preço mora em `precos` (vínculo + especialidade)** — porque o médico pode ter duas
  especialidades e a clínica cobra por tabela de preços, então cardiologia e clínica geral não
  custam o mesmo. Quem edita: se o local pertence a uma clínica, a clínica; se é consultório
  próprio, o médico. Descartado: preço solto no médico (ignora a tabela da clínica) e preço no
  vínculo sem especialidade (não resolve médico com duas áreas).

- **Médico autônomo e clínica coexistem** — a clínica se cadastra e cadastra os médicos dela;
  o médico autônomo também pode se cadastrar sozinho com consultório próprio. Quando a clínica
  cria a conta de um médico, o sistema gera senha temporária e **obriga a troca no primeiro
  login** — a clínica nunca fica sabendo a senha definitiva do profissional.

- **Sem upload de laudo ou diagnóstico** — decisão do Murilo em 18/09/2026, revertendo o pedido
  inicial. A acessibilidade é declarada em texto pelo paciente, com consentimento registrado.
  Guardar imagem de laudo faria do projeto um depositário de dado sensível de saúde, com
  obrigações de segurança que um TCC não cumpre. Vira argumento de minimização de dados no texto.

- **Cancelamento abaixo de 24h é permitido e registrado, não bloqueado** — bloquear não faz a
  pessoa comparecer, faz ela faltar; falta perde o horário, cancelamento devolve.

- **Comentário de avaliação é privado; só a nota é pública** — decisão do Murilo.

- **SUS e Teleconsulta fora do escopo** — decisão do Murilo em 18/09/2026.

## Erros do protótipo antigo que não podem se repetir

Levantados na leitura do repositório em 18/09/2026. Não são para consertar lá — são para não
renascerem no projeto novo.

- **`js/validacoes.js` aceitava senha só de 8 ou 9 caracteres** (`< 8` rejeita, `> 9` rejeita).
  Senha de 10 caracteres era barrada sem explicação. Regra correta no AGENTS.md §6.
- **Validação só no JavaScript.** `php/cadastropaciente.php` não conferia tamanho de senha,
  formato de e-mail nem CPF — quem desabilitasse o JS ou fizesse POST direto passava. No
  Laravel: FormRequest sempre, JS só como conforto.
- **Login sem regeneração de ID de sessão** — brecha de session fixation.
- **Login sem limite de tentativas** — força bruta livre.
- **`die("CPF já cadastrado")`** — permite descobrir quais CPFs e e-mails existem no sistema.
  Mensagem genérica e, se possível, tratamento pelo erro de UNIQUE do banco.
- **`echo "<script>alert(...)"` como navegação** — backend cuspindo HTML. No Laravel, redirect
  com flash message.
- **`css/home.css` estava com 0 byte no repositório**, enquanto uma segunda cópia em
  `paginas/home/home/css/home.css` tinha o conteúdo. Dois arquivos com o mesmo nome e um vazio
  commitado. Sintoma da estrutura de pastas duplicada.
- **`README.md` com uma linha.** No projeto novo, README com: o que é, como rodar, quem fez.

## Becos sem saída (já tentei, não funcionou)

- **Validar CRM programaticamente de graça** → não existe caminho. API oficial do CFM é paga
  (R$ 772/ano), exige CNPJ e representante legal no SEI, até 10 dias úteis. Não procure "API
  gratuita do CFM"; não faça scraping.
- **Validar carteirinha por algoritmo** → não existe. Cartão de crédito tem Luhn; carteirinha de
  plano não tem dígito verificador nacional — o padrão TISS deixa o formato a cargo de cada
  operadora.
- **Consultar a carteirinha de um paciente pelo COMPROVA da ANS** → não dá. Exige conta gov.br
  (Bronze, Prata ou Ouro) **do próprio beneficiário**, é site e não API, e a ANS declara que não
  compartilha os dados com outras instituições. O que existe é o caminho inverso: o beneficiário
  emite o comprovante e um terceiro confere a autenticidade informando os 8 primeiros dígitos do
  código de controle e a data de emissão — num formulário do site da ANS, manualmente.
- **Checar horário livre com SELECT antes do INSERT** (o que o protótipo antigo fazia) →
  condição de corrida: duas requisições simultâneas passam as duas. Use a coluna virtual +
  índice único abaixo. Não remova.

## Modelo de dados acordado

Fechado em 18/09/2026. A fonte da verdade passa a ser `database/migrations/` assim que existirem.

**Identidade:** `users` (nome, email, senha, `tipo` ∈ paciente/medico/clinica/admin, telefone,
`status` ∈ ativo/inativo/bloqueado) · `pacientes` (cpf, data_nascimento, sexo) ·
`medicos` (cpf, crm, uf, `status_verificacao` ∈ pendente/verificado/rejeitado, verificado_por,
verificado_em, bio, telefone_profissional, anos_atuacao, foto, media_avaliacoes,
total_avaliacoes, `senha_temporaria` bool) · `clinicas` (cnpj, razao_social, nome_fantasia,
descricao, telefone, logo) · `paciente_acessibilidade` (possui_deficiencia, descricao,
consentimento_em) — **tabela separada de propósito**, sem upload de arquivo.

**Locais e agenda:** `locais` (`clinica_id` **ou** `medico_id`, exatamente um; nome, tipo,
endereço, telefone) · `horarios_funcionamento` (local_id, dia_semana, abre, fecha) ·
**`vinculos`** (medico_id, local_id, ativo — UNIQUE medico+local) ·
**`precos`** (vinculo_id, especialidade_id, valor — UNIQUE vinculo+especialidade) ·
`disponibilidades` (**vinculo_id**, dia_semana, hora_inicio, hora_fim, duracao_minutos) ·
`bloqueios` (medico_id, inicio, fim, motivo).

**Especialidades:** `especialidades` (nome, slug, icone, destaque bool) ·
`medico_especialidade` (N:N).

**Convênios:** `operadoras_ans` (importada do CSV da ANS) · `convenios` (→ operadora_ans_id) ·
`planos` (→ convenio_id, fictícios) · **`convenio_medico`** (medico_id, convenio_id) ·
`paciente_planos` (plano_id, numero_carteirinha, validade, `codigo_comprova_ans`, `status`
∈ pendente/ativa/recusada, motivo_recusa).

**Consultas:** `consultas` (paciente_id, medico_id, vinculo_id, especialidade_id,
`data_consulta`, horario, `forma_pagamento` ∈ particular/convenio, paciente_plano_id, valor,
`status` ∈ agendada/realizada/cancelada/nao_compareceu, `origem` ∈ medico/clinica, cancelada_por,
cancelada_em, motivo) · `avaliacoes` (**consulta_id UNIQUE**, estrelas 1–5, comentario) ·
`notificacoes_enviadas` (consulta_id, tipo, enviada_em, sucesso — **UNIQUE consulta_id+tipo**).

**Dois índices que não podem sumir:**

```sql
-- em consultas: impede duplo agendamento sob concorrência,
-- e libera o horário quando a consulta é cancelada (vira NULL)
ALTER TABLE consultas
  ADD COLUMN horario_ativo TIME
    GENERATED ALWAYS AS (CASE WHEN status <> 'cancelada' THEN horario ELSE NULL END) VIRTUAL,
  ADD UNIQUE KEY uq_consulta_horario_ativo (medico_id, data_consulta, horario_ativo);
```

O Blueprint do Laravel não gera coluna virtual com `CASE` — vai como `DB::statement()` dentro da
migration. O outro é `UNIQUE (consulta_id, tipo)` em `notificacoes_enviadas`.

## O que falta fazer

### Bloqueadores — travam todas as outras pessoas (semana 1)

- [ ] `composer create-project laravel/laravel facilmed` e **registrar a versão no AGENTS.md §3**
- [ ] `composer require laravel/breeze --dev` + `php artisan breeze:install blade` + `npm install`
- [ ] Copiar as 21 migrations e os 19 models de `_novo_laravel/` e rodar `php artisan migrate`
- [ ] Repositório Git para o projeto Laravel, com todo mundo clonando
- [ ] **Definir as clínicas fictícias** (nome, cidade, especialidades, tabela de preços) — sem
      isso não há seeder, e sem seeder ninguém consegue testar tela nenhuma
- [ ] Seeders + comando de importação do CSV da ANS
- [ ] Middleware de papel e middleware de conta bloqueada
- [ ] **Redesenhar os menus laterais** conforme o escopo real — enquanto não for feito, o front
      não tem navegação confiável para construir

### Núcleo do sistema (semanas 2 e 3)

- [ ] FormRequests de cadastro (paciente, médico, clínica) com as regras do AGENTS.md §6
- [ ] Policies: `MedicoPolicy`, `AvaliacaoPolicy`, `PacienteAcessibilidadePolicy`, `ConsultaPolicy`
- [ ] Perfil do médico: especialidades, vínculos, preços, disponibilidade, bloqueios
- [ ] Perfil da clínica: unidades, horários, médicos vinculados (com senha temporária)
- [ ] Admin: aprovação de CRM, conferência de carteirinha, bloqueio de conta
- [ ] Busca com filtros (especialidade, cidade, particular/convênio)
- [ ] Cálculo de horários livres: disponibilidade − consultas − bloqueios
- [ ] Agendamento direto e por especialidade (alocação com o nome do médico antes de confirmar)
- [ ] Minhas consultas: cancelar, remarcar, marcar cancelamento tardio

### Fechamento funcional (semana 4)

- [ ] Três e-mails (confirmação, 24h antes, no dia) + comando + scheduler + `notificacoes_enviadas`
- [ ] Avaliações com comentário privado
- [ ] Dashboards: paciente, médico, clínica, admin

### Integração (semana 5 — não construir nada aqui)

- [ ] Testes do fluxo crítico (agendar, conflito de horário, cancelar, avaliar)
- [ ] README de verdade
- [ ] Documentação do TCC fechada
- [ ] Ensaio da apresentação com o roteiro do AGENTS.md §5

### Lacunas conhecidas entre o design e o banco

- [ ] **Gráfico "Acessos ao sistema"** no painel admin exige uma tabela de log de acesso que
      **não existe** nas migrations. Decidir: criar `logs_acesso` ou tirar o gráfico.
- [ ] **Status "Em espera"** aparece nos mockups e não existe no enum de `consultas`. Decidir se
      existe um passo de confirmação pela clínica.
- [ ] **"Média de espera: 12 min"** exige registrar check-in e início do atendimento. Não está
      no modelo. Decidir se entra.

## Riscos e pontos de atenção

- Não alterar credenciais reais nem editar `.env`.
- Não executar comandos destrutivos sem aprovação — inclusive `migrate:fresh` no banco de outra
  pessoa do grupo.
- ⚠ **E-mail é o único side-effect.** Desenvolver com `MAIL_MAILER=log`. Depois de qualquer
  teste de envio real, limpar as linhas de teste em `notificacoes_enviadas`, senão o lembrete
  verdadeiro daquela consulta nunca sai (o UNIQUE bloqueia). Rito: AGENTS.md §7.1.
- ⚠ **O PDF de design mostra funcionalidades fora do escopo** — Exames, Receitas, Atestados,
  Prontuários, Relatórios e um card "Status da sua saúde: Estável". Nada disso tem suporte no
  modelo de dados, e o card de status viola o AGENTS.md §6. **Não construa essas telas.** Tratar
  o PDF como referência de **estilo visual**, não de funcionalidade.
- ⚠ **Backend com uma pessoa é o caminho crítico.** Ver "Divisão do grupo".
- ⚠ **Migration já aplicada por outra pessoa não se edita** — cria-se uma nova. Editar uma que
  já rodou quebra o banco dos outros integrantes silenciosamente.
- Branch por frente. Ninguém commita na `main`.

## Onde está o detalhe (não duplicar aqui)

- **Regras, convenções, comandos e ritos:** `AGENTS.md`
- **Contexto de negócio, público, tom e identidade visual:** `PERFIL.md`
- **Telas e identidade visual (estilo, não escopo):** `docs/Descricao_Visual_FacilMed.pdf`
- **Protótipo anterior em PHP puro:** pasta original do FacilMed — **somente leitura**
- **Histórico de frentes já fechadas:** `docs/handoff-arquivo.md` (ainda não existe)

## Prompt para o próximo agente

```text
Leia AGENTS.md, CLAUDE.md, AI_HANDOFF.md e PERFIL.md, e rode `git log --oneline -10` e `git status`.
Continue de onde o último agente parou.

Antes de editar qualquer arquivo:
1. Resuma o estado atual.
2. Liste os arquivos relevantes.
3. Mostre um plano curto.
4. Aponte os riscos.
5. Aguarde minha aprovação.

Não edite .env, não altere credenciais e não execute comandos destrutivos.
Não construa nada que o AGENTS.md §2 exclua do escopo, mesmo que apareça no PDF de design.
```

---

> **Ao trabalhar:** atualize este handoff a cada checkpoint (intenção, decisão, próximo passo)
> e feche com **commit** — não stash. Mantenha enxuto.
