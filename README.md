# FacilMed — documentação do projeto

> **Este é o documento único do projeto.** Tudo o que estava espalhado em 14 arquivos
> (PASSO_A_PASSO, RELATORIO_COMPLETO, PERFIL, AI_HANDOFF, MUDANCAS, REVISAO, LEIA-ME…) foi
> juntado aqui em 24/09/2026. As versões antigas continuam no histórico do Git.
>
> Na raiz ficam só mais dois textos, que as IAs leem sozinhas: **`AGENTS.md`** (regras para
> qualquer IA) e **`CLAUDE.md`** (aponta para o AGENTS.md).

TCC do curso Técnico em Desenvolvimento de Sistemas — Etec Profª Ilza Nascimento Pintus.
Entrega: **20/10/2026**.

## Sumário

1. [O que é o FacilMed](#1-o-que-é-o-facilmed)
2. [Como rodar](#2-como-rodar) · [2.7 No servidor](#27-no-servidor-render--aiven--site-no-ar)
3. [Contas e dados de teste](#3-contas-e-dados-de-teste)
4. [Organização das pastas](#4-organização-das-pastas)
5. [Como o sistema funciona](#5-como-o-sistema-funciona)
6. [Estado atual e próximos passos](#6-estado-atual-e-próximos-passos)
7. [Contrato das telas que faltam](#7-contrato-das-telas-que-faltam)
8. [Identidade visual e tom de voz](#8-identidade-visual-e-tom-de-voz)
9. [Decisões e o porquê](#9-decisões-e-o-porquê)
10. [Equipe, cronograma e regras de trabalho](#10-equipe-cronograma-e-regras-de-trabalho)
11. [Histórico de correções](#11-histórico-de-correções)
12. [Protótipo antigo](#12-protótipo-antigo)

---

## 1. O que é o FacilMed

Plataforma web de agendamento de consultas médicas. A pessoa busca por especialidade, vê quem
atende perto dela, **quanto custa**, quais convênios o profissional aceita e quais horários estão
livres — e marca sozinha, sem telefonar para clínica nenhuma. Médicos e clínicas se cadastram,
publicam a agenda e recebem os agendamentos.

**O diferencial** é dar ao paciente, antes de marcar, o que hoje ele só descobre no telefone:
preço da consulta particular, convênio aceito e a avaliação de quem já foi.

| Quem usa | O que faz |
|---|---|
| **Paciente** | busca, agenda, remarca, cancela, cadastra carteirinha, avalia |
| **Médico** | perfil, especialidades, onde atende, preços, horários, ausências, agenda |
| **Clínica / hospital** | unidades, médicos, tabela de preços, convênios, agenda |
| **Admin** | convênios, especialidades, contas, acompanhamento da plataforma |

**Tudo é fictício:** médicos, clínicas, convênios e as "bases oficiais" de CRM, CNPJ e
carteirinha são tabelas do próprio banco (**bases simuladas**). Nada é consultado no CFM, na
Receita ou em operadora de verdade — e nenhuma tela diz que é.

**O que o FacilMed NÃO é** (vale para qualquer pessoa ou IA):
- não é prontuário eletrônico — nada de diagnóstico, receita, atestado, exame;
- não é telemedicina; não processa pagamento (só mostra o valor); não integra com o SUS;
- não guarda documento médico (nenhum upload de laudo);
- não dá nenhum tipo de parecer sobre a saúde de ninguém.

---

## 2. Como rodar

**Laravel 12 + MariaDB do XAMPP.** Telas em Blade com CSS próprio e Alpine.js. **Não precisa de Node/npm.**

### 2.1 Programas (uma vez por computador)

| Programa | Versão | Para quê |
|---|---|---|
| XAMPP | **8.2.12** (PHP 8.2) | PHP + banco MariaDB + Apache |
| Composer | 2.x | baixa o Laravel — https://getcomposer.org/Composer-Setup.exe |
| GitHub Desktop | qualquer | baixar e enviar o código |

Confira no terminal: `php -v` (tem que ser 8.2.x) e `composer -V`. Se o PHP for mais antigo,
instale o XAMPP 8.2.12 e coloque `C:\xampp\php` no `Path` do Windows.

**Libere duas extensões do PHP:** abra `C:\xampp\php\php.ini`, procure as linhas abaixo e
**apague o `;`** do começo (se já não tiver). Salve.

```
extension=zip
extension=fileinfo
```

### 2.2 Banco de dados

1. No painel do XAMPP, ligue o **MySQL**.
2. Em http://localhost/phpmyadmin → **Novo** → nome **`facilmed`**, cotejamento
   **`utf8mb4_unicode_ci`** → **Criar**.

### 2.3 Instalar (uma vez)

No GitHub Desktop: **Repository → Open in Command Prompt**. Depois:

```bash
composer run setup
```

Instala as dependências, cria o `.env`, gera a chave e monta o banco com os dados de teste. No
fim deve aparecer "22 consultas de demonstracao criadas".

### 2.4 Abrir o sistema — dois jeitos

**A) Pelo XAMPP, sem terminal (o `index.php` da raiz):** a pasta do projeto dentro de
`C:\xampp\htdocs\` com o nome **`FacilMed`**, **Apache** e **MySQL** ligados →
**http://localhost/FacilMed**

**B) Pelo terminal:** `composer run dev` → **http://127.0.0.1:8000** (para parar: `Ctrl + C`).

### 2.5 Dia a dia

| Quero... | Comando |
|---|---|
| baixar o que o grupo mudou | **Pull** no GitHub Desktop, depois `composer install` e `php artisan migrate` |
| zerar o MEU banco e recriar os dados de teste | `composer run banco-do-zero` (apaga tudo do `facilmed` da sua máquina) |
| rodar os testes automáticos | `php artisan test` (usa o banco `facilmed_testes`, criado sozinho) |
| mandar os lembretes de 24h | `php artisan schedule:work` (deixa rodando) ou `php artisan facilmed:enviar-lembretes` |
| ver erro detalhado | final de `storage/logs/laravel.log` |
| ver os e-mails "enviados" | também em `storage/logs/laravel.log` (em desenvolvimento nenhum e-mail sai de verdade) |

### 2.6 Se algo falhar

| Sintoma | O que fazer |
|---|---|
| `The zip extension and unzip/7z commands are both missing` | faltou `extension=zip` no `php.ini` |
| erro de `fileinfo` / `Class "finfo" not found` | faltou `extension=fileinfo` |
| `Your PHP version (8.0...) does not satisfy` | PHP antigo; veja 2.1 |
| `Unknown database 'facilmed'` | faltou criar o banco (2.2) |
| `SQLSTATE[HY000] [2002]` | o MySQL do XAMPP está desligado |
| `No application encryption key` | `php artisan key:generate` |
| página em branco / erro 500 | última linha de `storage/logs/laravel.log` |
| `migrate:rollback` ou `migrate:refresh` falha em `convenios_e_planos_ficticios` ("Data truncated for column 'operadora_ans_id'") | é de propósito: convênio fictício não tem operadora e o `down()` não apaga dado. O banco fica pela metade: rode `composer run banco-do-zero` (nunca use rollback/refresh) |
| `migrate` falha na coluna virtual de `consultas` | troque `VIRTUAL` por `PERSISTENT` na migration. **Não remova o índice único** |
| mudei algo em `config/` e não fez efeito | `php artisan optimize:clear` |
| `localhost/FacilMed` dá "Not Found" do Apache | em `C:\xampp\apache\conf\httpd.conf`, `LoadModule rewrite_module` sem `#` na frente; reinicie o Apache |
| `localhost/FacilMed` abre sem estilo | a pasta não se chama `FacilMed` ou não está direto no `htdocs`: use o jeito B |

Não vão para o Git (e está certo): `vendor/`, `.env`, `storage/logs/`. Quem rodar o
`composer install` primeiro pode commitar o **`composer.lock`**, para todos terem as mesmas versões.

### 2.7 No servidor (Render + Aiven) — site no ar

O sistema fica publicado no **Render** (site) com o banco **MySQL no Aiven**. Os dois são grátis e
nenhum pede cartão. O Render está ligado a este repositório: **cada push na `main` atualiza o site
sozinho** em uns 5 minutos. Se o build der erro, a versão anterior continua no ar.

Arquivos do deploy (não mexem no XAMPP): `Dockerfile`, `docker/entrypoint.sh`, `render.yaml`,
`.dockerignore`. A cada vez que o servidor liga, o `entrypoint.sh`:
1. roda `php artisan migrate --force` (só aplica migration nova; não apaga nada);
2. se o banco estiver **vazio**, roda os seeders (as contas da §3 passam a existir no site);
3. faz o cache de config, rotas e telas.

**Por que MySQL no Aiven e não o Postgres do Render?** O projeto usa SQL do MySQL (`DAYOFWEEK`,
`HOUR`, coluna virtual `horario_ativo`) e o Postgres grátis do Render é apagado depois de 30 dias.

**Montar do zero (só uma vez):**
1. **Aiven** — em aiven.io, crie conta → *Create service* → **MySQL** → plano **Free**. Quando ficar
   *Running*, na página do serviço copie o **Service URI** (`mysql://avnadmin:...`) e, em
   *CA certificate*, clique em **Show** e copie o texto inteiro (de `-----BEGIN` até `END-----`).
2. **Render** — em render.com, entre com o GitHub → **New → Blueprint** → escolha o repositório
   `cilugo/FacilMed` (se não aparecer, clique em *Configure GitHub* e libere o acesso). O Render lê o
   `render.yaml` e pede 3 valores:
   - `APP_KEY`: a chave do Laravel (`base64:...`). Gerar: `php artisan key:generate --show`.
   - `DB_URL`: o *Service URI* do Aiven.
   - `MYSQL_CA_CERT`: o texto do certificado do Aiven.
3. **Apply**. O primeiro build leva uns 5–10 minutos. O endereço aparece no topo
   (`https://facilmed-xxxx.onrender.com`). Aba **Logs** mostra os erros, se houver.

**Limites do plano grátis (bom saber antes da banca):**
- O site **dorme depois de 15 minutos** sem visita; a primeira visita depois disso leva ~1 minuto.
  Abra o site uns 2 minutos antes de apresentar.
- O Aiven pode desligar o banco grátis depois de muito tempo **sem nenhum uso** (avisa por e-mail
  antes). Basta religar no painel.
- E-mail continua em `MAIL_MAILER=log` (não sai de verdade) e o agendador dos lembretes não roda.
- **Nunca** coloque senha do banco ou `APP_KEY` em arquivo do Git: elas ficam só no painel do Render.

**Apagar tudo e recriar os dados de demonstração no servidor:** no Render, aba **Shell**:
`php artisan migrate:fresh --seed --force` (⚠ apaga o que foi cadastrado no site).

---

## 3. Contas e dados de teste

**Senha de todas as contas: `facilmed2026`**

| Tipo | E-mail | Observação |
|---|---|---|
| Admin | `admin@facilmed.test` | |
| Paciente | `ana@facilmed.test` | CPF 802.301.401-30 · tem carteirinha SpSaúde Família ativa |
| Paciente | `marcos@facilmed.test` | CPF 802.301.402-11 · sem carteirinha |
| Médica | `helena@facilmed.test` | CRM 112233/SP · Cardiologia + Clínica Geral · Vida Plena (manhã) e Santa Clara (tarde) |
| Médico | `rafael@facilmed.test` | CRM 223344/SP · Dermatologia + Clínica Geral · SpSaúde (manhã) e São Lucas (tarde) |
| Médica | `camila@facilmed.test` | CRM 334455/SP · Pediatria · Aurora (manhã) e Esperança (tarde) |
| Clínica | `contato@clinicaspsaude.test` · `contato@vidaplena.test` · `contato@aurora.test` | São José dos Campos |
| Hospital | `contato@santaclara.test` (Taubaté) · `contato@saolucasdovale.test` (Jacareí) · `contato@hospitalesperanca.test` (Caçapava) | |

Convênios fictícios: **SpSaúde**, **Horizonte Med**, **Bem Viver Saúde** (3 planos cada) e
**Vale Saúde** (desativado, para demonstrar). Todos os dados estão em
`database/seeders/DadosFicticios.php`.

### Para testar as bases simuladas

**Cadastro de médico pela clínica** (entre como clínica → **Meus médicos → Cadastrar médico**).
Desde 29/09 o médico não se cadastra sozinho: `/cadastro/medico` só volta para a escolha.

| CRM / UF | Resultado |
|---|---|
| 445566 / SP, nome **Paulo Yamada** | ✅ aceito — entra verificado, com senha provisória (aparece uma vez em "Tabela de preços") |
| 445566 / SP, outro nome | ❌ em nome de outra pessoa (desde 28/09 o nome é conferido; "Dr./Dra.", acento e maiúscula não contam) |
| 998877 / SP | ❌ cassado |
| 556677 / RJ | ❌ suspenso |
| 123456 / SP | ❌ não existe na base |
| 112233 / SP | já tem conta (Dra. Helena): só ganha o vínculo com a unidade; se já atende nela, ❌ |

**Cadastro de clínica/hospital** (`/cadastro/clinica`)

| CNPJ | Resultado |
|---|---|
| 43.300.001/0001-64 | ✅ aceito |
| 43.300.002/0001-09 | ❌ baixado |
| 41.100.001/0001-95 | ❌ já cadastrado |
| 11.111.111/1111-11 | ❌ dígito verificador inválido |

**Carteirinha** (logado como paciente → "Meu plano")

| Paciente | Plano | Número | Resultado |
|---|---|---|---|
| Marcos | Bem Viver Individual | 300000000002 | ✅ aceita |
| Marcos | Horizonte Essencial | 200000000003 | ❌ vencida |
| Ana | Horizonte Empresarial | 200000000006 | ✅ aceita |
| Ana | Horizonte Família | 200000000004 | ❌ cancelada |
| Ana ou Marcos | SpSaúde Individual | 100000000005 | ❌ de outra pessoa |
| Marcos | SpSaúde Família | 100000000001 | ❌ é da Ana |

---

## 4. Organização das pastas

```
FacilMed/
├── README.md              ← este documento
├── AGENTS.md, CLAUDE.md   ← regras para IAs (ficam na raiz para elas acharem)
├── index.php, .htaccess   ← fazem http://localhost/FacilMed abrir o sistema pelo XAMPP
├── composer.json          ← dependências e os atalhos "composer run setup/dev/banco-do-zero"
│
├── app/
│   ├── Http/Controllers/  ← um controller por tela: Paciente/, Medico/, Clinica/, Admin/, Auth/
│   ├── Http/Requests/     ← validação dos formulários (Medico/, Clinica/, Admin/)
│   ├── Http/Middleware/   ← tipo de conta, conta ativa, troca de senha temporária
│   ├── Models/            ← tabelas e relacionamentos
│   ├── Policies/          ← quem pode ver/mexer em quê
│   ├── Rules/             ← CPF, CNPJ, senha, CRM/CNPJ na base simulada
│   ├── Services/          ← regras grandes: horários livres, alocação de médico,
│   │                         bases simuladas, estatísticas, e-mails (Notificador)
│   ├── Mail/              ← e-mail dos avisos de consulta
│   ├── Console/Commands/  ← comando dos lembretes
│   └── Support/           ← formatação (datas, CPF, telefone), lista de UFs
├── database/
│   ├── migrations/        ← estrutura do banco (fonte da verdade)
│   └── seeders/           ← dados fictícios (DadosFicticios.php)
├── resources/views/       ← telas (Blade)
│   ├── layouts/           ← site (público), auth (login), cadastro (cadastros), painel
│   ├── home, busca/, publico/, cadastro/, auth/, agendamento/
│   ├── paciente/, medico/, clinica/, admin/
│   └── emails/            ← texto dos e-mails
├── public/                ← o que o navegador baixa: css/, javas/, imgs/
├── routes/                ← web.php (páginas), auth.php (login), console.php (agendador)
├── lang/pt_BR/            ← mensagens em português
├── config/                ← configurações (agendamento.php, navegacao.php = menus)
├── tests/Feature/         ← 132 testes automáticos
├── storage/               ← logs e cache (gerado)
├── design/                ← prints e protótipos de tela (referência visual)
└── prototipo-antigo/      ← versão antiga em PHP puro (não usada pelo sistema)
```

---

## 5. Como o sistema funciona

### 5.1 A tabela central: `vinculos`

Um **vínculo** liga um médico a um local de atendimento. Tudo que depende de "onde o médico
atende" pendura nele:

```
medico ──┐
         ├── vinculo ──┬── precos (por especialidade)
local ───┘             ├── disponibilidades (blocos de horário da semana)
                       └── consultas
```

Sem isso o sistema não saberia em qual endereço o médico está às 14h de terça — era o furo do
banco antigo.

### 5.2 As tabelas, por grupo

- **Contas:** `users` (tipo e status), `pacientes`, `medicos`, `clinicas`,
  `paciente_acessibilidade` (separada de propósito: dado sensível, só com consentimento).
- **Locais e agenda:** `locais` (de uma clínica **ou** de um médico — o banco garante um dono
  só), `horarios_funcionamento`, `vinculos`, `precos`, `disponibilidades`, `bloqueios` (ausências),
  `feriados`.
- **Especialidades:** `especialidades`, `medico_especialidade` (um médico pode ter várias).
- **Convênios:** `convenios`, `planos`, `convenio_medico` (o convênio é aceito pelo MÉDICO),
  `paciente_planos` (carteirinhas).
- **Consultas:** `consultas`, `avaliacoes`, `notificacoes_enviadas` (e-mails).
- **Bases simuladas:** `base_crms`, `base_cnpjs`, `base_carteirinhas` — fazem o papel do CFM,
  da Receita e das operadoras. **Só o seeder escreve nelas.**

### 5.3 Horário livre é calculado, nunca guardado

`App\Services\CalculadoraDeHorarios`:

```
blocos de disponibilidades daquele dia da semana, fatiados pela duração da consulta
  DENTRO do horário de funcionamento do local
  MENOS consultas não canceladas do médico (em QUALQUER lugar)
  MENOS ausências (bloqueios) e feriados
  A PARTIR de 24h de antecedência, até 180 dias
```

A mesma função mostra os horários na tela e confere na hora de gravar. E o banco tem a última
palavra: o índice único `(medico_id, data_consulta, horario_ativo)` impede duas pessoas no mesmo
horário mesmo se clicarem juntas.

### 5.4 Regras que o sistema garante

- Médico só aparece na busca com CRM **verificado** e conta ativa.
- CRM, CNPJ e carteirinha são conferidos **na hora do cadastro**, na base simulada.
- Preço **nunca** vem do formulário: é calculado no servidor.
- Por convênio, só se o médico aceita aquele convênio; sempre com o aviso
  "Confirme na recepção se o seu plano é aceito neste endereço".
- Cancelar é sempre permitido; com menos de 24h fica marcado como tardio.
- **Nenhuma consulta é cancelada em silêncio:** ausência do médico, desvincular médico e
  bloquear conta pedem confirmação explícita.
- Comentário de avaliação é privado (só médico, clínica e admin veem). A nota é pública.
- Acessibilidade do paciente só aparece para o **médico daquela consulta**, e só enquanto ela está
  agendada (`ConsultaPolicy::verAcessibilidade`). A clínica não vê.
- CRM é conferido **junto com o nome**. Médico rejeitado pelo admin só volta pelo "Desfazer rejeição".
- Busca e perfil público só mostram lugar onde dá para agendar (`Vinculo::agendaveis()`, a mesma
  regra do `recebeAgendamento()`) e só preço ativo de especialidade ativa (`precosOferecidos()`).
- Conta bloqueada é deslogada na próxima página. Médico cadastrado pela clínica troca a senha
  temporária antes de usar o sistema.
- Várias regras também estão travadas **no próprio banco** (preço negativo, horário que termina
  antes de começar, nota fora de 1 a 5, local com dois donos).

### 5.5 E-mails

Tudo passa por `App\Services\Notificador`, que **grava em `notificacoes_enviadas` antes de
enviar** — o `UNIQUE (consulta_id, tipo)` garante que o mesmo aviso nunca sai duas vezes.

| Quando | Para quem |
|---|---|
| agendou | paciente (confirmação) |
| paciente cancelou | médico |
| médico/clínica/admin cancelou | paciente (com o motivo) |
| remarcou | médico (o horário antigo ficou livre) |
| 24h antes | paciente (lembrete — `php artisan schedule:work`) |

Em desenvolvimento (`MAIL_MAILER=log`) nada sai da máquina. **Enviar para caixa real só com
aprovação do grupo, e só para e-mail de integrante.**

### 5.6 Testes automáticos

`php artisan test` → **132 testes** em `tests/Feature/`: cadastros, carteirinhas, agendamento,
médico, clínica, admin, segurança, e-mails, travas do banco e as telas. Rodam no banco
`facilmed_testes` (criado sozinho), **nunca** no `facilmed`. Toda mudança de back-end vem com teste.

---

## 6. Estado atual e próximos passos

> Esta seção é a "passagem de bastão" entre quem trabalha no projeto (pessoas e IAs).
> **Atualize ao terminar cada etapa.**

**Atualizado em 29/09/2026.**

**29/09 — site no ar (Render + Aiven):** `Dockerfile`, `docker/entrypoint.sh`, `render.yaml` e
`.dockerignore` para publicar pelo Render (passo a passo na §2.7). Única mudança no código:
`trustProxies(at: '*')` no `bootstrap/app.php`, para o Laravel gerar links `https` atrás do proxy
do Render (no XAMPP não muda nada). Conferido com MySQL 8 nas mesmas travas do Aiven (chave primária
obrigatória e conexão segura): 28 migrations e seed limpos, 2º boot não duplica dados, login dos 4
tipos de conta e todas as abas principais respondendo 200 com links em https.
**Atenção, testes no MySQL 8** (no MariaDB do XAMPP passam): `BancoTest::checks_do_banco` espera o
código `23000`, mas o MySQL 8 devolve `HY000` para CHECK violado (a trava funciona, só muda o
código); `MedicoTest::especialidades_com_principal...` depende da hora em que roda (a regra
"consulta futura marcada" barra a troca). Já falhavam antes do deploy.

**29/09 — cadastro (branch `front/cadastro`, feita em cima da `revisao/28-09`):**
- **Conferência antes de mexer:** subi a `revisao/28-09` com MariaDB e abri 209 páginas no navegador
  (todas as abas dos 4 painéis e os links de dentro delas, no computador e no celular). Nenhum erro
  500, e os cadastros funcionaram.
- **Médico não se cadastra mais sozinho** (plano do app de 28/09; decisão do Sidney em 29/09: fica
  paciente e clínica, como no protótipo da Mari). Ele entra por **Clínica → Meus médicos →
  Cadastrar médico**, que já conferia o CRM na base simulada. `/cadastro/medico` volta para `/cadastro`.
  Os testes de CRM recusado passaram a rodar por esse caminho.
- **Visual da Mari no cadastro** (protótipo `cadpac`, commit "Cadastros"): escolha, paciente e
  clínica em tela dividida, cartões com ícone. Arquivos: `layouts/cadastro.blade.php`,
  `public/css/cadastro.css`, `public/javas/cadastro.js` (máscaras e "mostrar senha"; validar continua
  no FormRequest), `cadastro/_campo`, `_icone`, `_avisos` e o componente `<x-cadastro.card>`
  (`components/cadastro/card.blade.php` — componente anônimo do Blade: o arquivo vira a tag).
  Diferenças do protótipo: logo oficial no lugar do `logo.svg`; ficam CPF, acessibilidade com
  consentimento e a primeira unidade da clínica; saem CNES, cargo e usuário (não existem no banco; o
  login é pelo e-mail). O `layouts/publico` ficou sem uso e saiu.
- **Corrigido:** pelo XAMPP (`/FacilMed`), `/register` e `/cadastro/medico` mandavam para
  `http://localhost/cadastro` (404) — §11, item 40.
- Conferido: **132 testes** passando, `migrate:fresh --seed` limpo e a varredura no navegador de novo
  (213 páginas, 0 erro), incluindo o caminho inteiro: clínica nova cadastra o médico → ele entra com
  a senha provisória → é obrigado a trocar → usa a agenda.

**Junção de 25/09/2026:** a home que o grupo fez no protótipo depois da organização (slider do início com 9 fotos, fotos dos médicos, "Hospitais e Clínicas" e "Sobre") foi trazida para `resources/views/home.blade.php`, `public/css/home.css` e `public/javas/home.js`; fotos em `public/imgs/` (sliderinicio, medicos, sliderhospcli — estas comprimidas de 10,5 MB para 1 MB). Os 3 médicos fictícios ganharam foto (`foto` em `DadosFicticios::MEDICOS`). O `prototipo-antigo/` também foi atualizado com a versão do GitHub (a home agora é `paginas/index.html`). **27/09:** entrou o commit `c9faffe` (mudanças na home: slider em loop contínuo, menu que marca o item clicado, cores em azul-escuro, novo texto do topo, "Sobre nós" e nova foto `medico5`), no protótipo e na home em Laravel.

**Pronto e testado:**
- Estrutura: o repositório é o projeto Laravel completo; roda pelo XAMPP (`/FacilMed`) ou `composer run dev`.
- Banco: 28 migrations, seed completo, travas e índices.
- Back-end: **todas** as ações de paciente, médico, clínica e admin (nenhum TODO sobrando).
- Telas: site público (home, busca, perfis), login e cadastros, agendamento completo, todas as
  telas do paciente, dashboards dos 4 tipos de conta, convênios (admin e clínica).
- **Telas do médico (28/09):** agenda, meus horários, ausências, onde atendo, preços, avaliações
  e perfil (com o fluxo da senha provisória).
- **Telas da clínica (28/09):** agenda (só leitura), meus médicos (com desvínculo confirmado),
  cadastrar médico, unidades (com edição do horário de funcionamento), tabela de preços (mostra a
  senha provisória do médico novo uma única vez), avaliações e perfil.
- **Telas do admin (28/09):** usuários (bloquear com motivo / desbloquear), verificar CRM
  (histórico + rejeitar; aprovar confere na base simulada), carteirinhas, clínicas e hospitais,
  especialidades (criar/editar/destaque/desativar) e consultas (filtros, sem observações).
- **Com isso, as 20 telas da seção 7 estão prontas.** Todo item de menu leva a uma tela.
- **Revisão geral (28/09):** 8 problemas achados e corrigidos, com teste (ver §11, itens 19–26).
- **Revisão geral, 2ª rodada (28/09):** mais 3 corrigidos, com teste no `RevisaoTest` (§11, itens
  27–29). O `AlocadorDeMedico` agora usa a mesma regra `Vinculo::ofereceEspecialidade()`; o
  duplo envio da avaliação responde "já estava registrada"; a tela da consulta por convênio
  repete o aviso da recepção. *(Conferida na 3ª rodada: os testes passam.)*
- **Revisão geral, 3ª rodada (28/09):** mais 10 problemas corrigidos, com teste no
  `RevisaoRodada3Test` (§11, itens 30–39). Rodado de verdade (PHP + MariaDB e pelo Apache, como no
  XAMPP): `migrate:fresh --seed` limpo e **128 testes passando**. Decisões tomadas nesta rodada
  (dá para reverter se o grupo quiser): a **acessibilidade só aparece para o médico** da consulta e só
  enquanto ela está agendada (a clínica não vê — é o que o AGENTS §3 e o consentimento do cadastro
  dizem); a **base simulada confere o nome junto com o CRM**.
- E-mails e lembretes. **132 testes automáticos**, incluindo a `VarreduraTest`, que abre todas as
  páginas com as 5 visões (visitante, paciente, médico, clínica, admin) e falha se alguma der erro 500.

**Telas internas — o que vale saber (28/09):**
- Classes novas no fim de `public/css/crud.css` (filtros, agenda, semana, opções, preços,
  avaliações, paginação). Só usam as variáveis `--fm-*` do `painel.css`.
- Paginação: `{{ $lista->links('painel.parciais.paginacao') }}`. A padrão do Laravel usa classes
  do Tailwind, que o projeto não tem — use essa nas telas de admin/clínica também.
- **Preço digitado** passa por `App\Support\Dinheiro::lerDigitado()`: aceita `250,00`, `1.250,00` e
  `150.00` (até 28/09 o ponto era apagado e `150.00` virava 15000). Na tela, continue mostrando com vírgula.
- Senha provisória: o `ExigirTrocaDeSenha` bloqueia todas as outras rotas, então o perfil mostra
  **só** o formulário de senha até ela ser trocada.
- Partes reaproveitáveis em `resources/views/painel/parciais/`: `senha` (trocar senha),
  `horarios-funcionamento` (horários por dia) e `paginacao`.
- Numa tela com vários formulários iguais (ex.: horário de cada unidade), cada um manda um campo
  escondido `_form` para o `old()` e os erros voltarem só no formulário certo.
- `TelasMedicoTest`, `TelasClinicaTest` e `TelasAdminTest` abrem as telas com a view real.
- **"Pode receber agendamento?"** agora é UMA regra: `Vinculo::recebeAgendamento()` (vínculo e
  local ativos, médico verificado e com conta ativa, clínica com conta ativa). A
  `CalculadoraDeHorarios`, a tela de horário e a gravação usam a mesma. Especialidade oferecida
  num lugar: `Vinculo::ofereceEspecialidade()` (preço ATIVO + especialidade ativa).
- Nos testes, campo fora do `$fillable` dá erro em vez de sumir em silêncio
  (`preventSilentlyDiscardingAttributes`, em `tests/TestCase.php`). Campo que não deve vir de
  formulário (ex.: `motivo_bloqueio`) é gravado com `forceFill()`.

**Próximos passos sugeridos:**
1. Pull Request da `revisao/28-09` para a `main` e, depois, da `front/cadastro` (que está em cima dela).
   *(As branches `front/telas-*` e `claude/...` já estão na `main` e podem ser apagadas.)*
2. Cada um: **Pull**, `composer install`, `composer run banco-do-zero` e `php artisan test`.
3. Ensaio da apresentação seguindo as contas do §3 (16–20/10 é só integração e teste).

**Para o grupo olhar (não mexi porque é código de outra pessoa — README §10, regra 6):**
- *(28/09, 3ª rodada)* **Paciente pode marcar duas consultas no mesmo horário** com médicos
  diferentes, e os dados de demonstração já vêm assim (a Ana aparece com duas consultas no mesmo dia e
  hora em "Minhas consultas"). Sugestão: recusar na confirmação ("você já tem consulta nesse horário") e
  espalhar os horários no `ConsultaSeeder`. É regra nova de agendamento: precisa do OK do grupo.
- *(28/09)* "Cadastros" e "Create composer.lock" foram commitados **direto na `main`** (regra 1 do §10).
- A vitrine "Hospitais e Clínicas" da home é uma lista fixa no Blade. O "Hospital Vale Sereno"
  não existe no sistema, e os endereços das outras três (Santa Clara, Vida Plena, Aurora) são
  diferentes dos cadastrados no banco. Na banca, procurar a clínica e achar outro endereço pega
  mal. Sugestão: montar a lista a partir das clínicas do banco (o `HomeController` pode mandar).
- *(28/09, 2ª rodada)* Contadores do topo da home: "médicos verificados" é o único texto público
  sem o "conferido na base simulada" (sugestão: "médicos com CRM conferido"; a troca por
  "verificados pela equipe", sugerida no PR #1, ficou desatualizada em 24/09). E "clínicas e
  hospitais" conta todos os locais ativos, incluindo consultório próprio de médico e unidade de
  clínica bloqueada.
- O protótipo estático do admin (`facilmed_admin_telas.zip`, 26/09) tem "Resultados de exames",
  status "Em acompanhamento" e idade de paciente — fora do escopo (AGENTS.md §1 e §3). As telas
  do admin feitas aqui seguem o contrato da seção 7.3.

**Plano do app (PDF de 28/09) — o que já foi decidido (Sidney, 29/09):** cadastro aberto só para
paciente e clínica (feito); **o agendamento continua** — a tela "médicos disponíveis" leva aos
horários; **a avaliação continua por consulta realizada**, com comentário privado (AGENTS §3), e a
nota do local e a do médico saem dessas avaliações. **Ainda por fazer/decidir:** busca por
distância (locais sem latitude/longitude hoje; sugestão: localização do navegador + tabela de
cidades com coordenadas no seeder, sem serviço externo); página do local; exclusão de conta
(LGPD); CNES fica de fora.

**Pendente de decisão do grupo (28/09):** o PDF "Dashboard da Clínica" tira do menu a tabela
de preços e o perfil, e pede documentação com upload, resultados de exames, status "remarcada",
convênio vencido/renovado e unidade "em implantação". Nada disso existe no banco, e exames/upload
de documento são fora do escopo (AGENTS.md §1). Até o grupo decidir, as telas da clínica seguem
o contrato da seção 7 (a tabela de preços e o perfil foram mantidos).

**Não reabrir sem motivo** (decisões já testadas):
- `Consulta::cancelar()` é o ÚNICO jeito de cancelar — ele dispara o e-mail certo.
- Não criar índice novo começando por `consultas.vinculo_id`: o MariaDB passa a usá-lo na chave
  estrangeira e o `rollback` quebra.
- Não mexer no `.htaccess` da raiz sem testar que `http://localhost/FacilMed/.env` dá **403**.
- Fuso horário é `America/Sao_Paulo` (`config/app.php`); com UTC, "agora" ficava 3h adiantado.

---

## 7. Contrato das telas que faltam

> **28/09/2026: as 20 telas abaixo foram feitas.** O contrato continua valendo como documentação do
> que cada controller manda para a view — se mudar um nome, mude aqui e no `TelasInternasTest`.

Para cada tela: o arquivo a criar, as variáveis que o controller **já manda** e os formulários
(rota + campos). `tests/Feature/TelasInternasTest.php` confere que os controllers entregam
essas variáveis — se mudar um nome, mude aqui e no teste.

**Como fazer:** copie o esqueleto de uma tela que já existe — `paciente/perfil.blade.php`
(formulários) ou `clinica/convenios.blade.php` (listas). Layout `layouts.painel`, classes `fm-*`
de `public/css/painel.css` e `crud.css`. `session('sucesso')` e `session('erro')` aparecem
sozinhas no layout; erro de campo com `@error('campo')`. Todo formulário tem `@csrf`
(PUT/DELETE: `@method(...)`). Nada de regra de negócio na view.

### 7.1 Médico (menus em `config/navegacao.php`)

#### `medico/agenda.blade.php` — GET `/medico/agenda?data=AAAA-MM-DD&vinculo=id`
| Variável | O quê |
|---|---|
| `$data` | Carbon do dia mostrado |
| `$anterior`, `$seguinte` | `'AAAA-MM-DD'` para os botões ‹ › |
| `$vinculos` | lugares onde atende (`->local->nome`) — filtro |
| `$consultas` | do dia, por horário. Cada uma: `horario`, `status`, `paciente->user->name`, `paciente->acessibilidade?->descricao` (só dentro de `@can('verAcessibilidade', $c)`), `especialidade->nome`, `vinculo->local->nome`, `forma_pagamento`, `pacientePlano?->plano->convenio->nome`, `observacoes`, `inicio` |
| `$resumo` | `['agendadas','realizadas','canceladas','faltas']` (números) |

Ações por consulta (botões só quando fizer sentido: realizada/falta depois do horário;
cancelar antes):
- POST `route('medico.agenda.realizada', $c)` — sem campos
- POST `route('medico.agenda.falta', $c)` — sem campos
- POST `route('medico.agenda.cancelar', $c)` — `motivo` (**obrigatório**, vai no e-mail ao paciente)

Para esconder botão: `$c->podeSerCancelada()` e `! $c->inicio->isFuture()`.

#### `medico/disponibilidade.blade.php` — GET `/medico/horarios` ("Meus horários")
| Variável | O quê |
|---|---|
| `$vinculos` | cada um com `->local` e `->disponibilidades` (`dia_semana`, `hora_inicio`, `hora_fim`, `duracao_consulta_minutos`) |
| `$dias` | `['domingo','segunda',...,'sabado']` |
| `$duracoes` | `[15, 20, 30, 40, 45, 60]` |

- POST `route('medico.disponibilidade.salvar')` — `vinculo_id`, `dia_semana`, `hora_inicio` (HH:MM),
  `hora_fim`, `duracao_consulta_minutos`. O back recusa: fora do funcionamento do local, choque com
  outro bloco (inclusive em outro lugar), bloco menor que uma consulta.
- DELETE `route('medico.disponibilidade.remover', $bloco)`. A mensagem avisa se sobraram consultas marcadas.
- **Deixe claro na tela:** almoço = dois blocos no mesmo dia (08–12 e 14–18).

#### `medico/bloqueios.blade.php` — GET `/medico/ausencias`
| Variável | O quê |
|---|---|
| `$bloqueios` | `inicio`, `fim` (Carbon), `motivo`, `vinculo?->local->nome` (nulo = todos os lugares) |
| `$vinculos` | para o select "onde" |

- POST `route('medico.bloqueios.salvar')` — `inicio`, `fim` (`datetime-local`), `vinculo_id` (vazio = todos),
  `motivo`, `cancelar_consultas` (checkbox). **Sem o checkbox, consultas no período NÃO são canceladas** —
  a resposta volta com `session('erro')` explicando e `session('conflitos')` (ids).
- DELETE `route('medico.bloqueios.remover', $b)`

#### `medico/locais.blade.php` — GET `/medico/locais` ("Onde eu atendo")
| Variável | O quê |
|---|---|
| `$vinculos` | `->local` (`nome`, `tipo`, `endereco_completo`, `clinica?->nome_fantasia`), `->precos`, `aceita_particular`, `aceita_convenio` |

- POST `route('medico.locais.salvar')` — consultório próprio: `nome`, `cep`, `endereco`, `numero`,
  `complemento`, `bairro`, `cidade`, `uf`, `telefone`, `aceita_convenio`,
  `horarios[segunda][abre]`/`[fecha]`... (vazio = seg–sex 08–18). Redireciona para Preços.
- Unidade de clínica: só leitura (quem vincula é a clínica).

#### `medico/precos.blade.php` — GET `/medico/precos`
| Variável | O quê |
|---|---|
| `$vinculos` | `->local` (`clinica_id` nulo = consultório próprio = editável), `->precos` (`especialidade->nome`, `valor`, `ativo`) |
| `$especialidades` | as do médico |

- POST `route('medico.precos.salvar')` — `vinculo_id`, `especialidade_id`, `valor` (aceita "250,00"), `ativo`.
  Só no consultório próprio; em unidade de clínica dá 403 (mostre o valor só para leitura).

#### `medico/avaliacoes.blade.php` — GET `/medico/avaliacoes`
| Variável | O quê |
|---|---|
| `$medico` | `media_avaliacoes`, `total_avaliacoes` |
| `$avaliacoes` | paginado: `estrelas`, `comentario` (visível aqui), `created_at`, `paciente->user->name`, `consulta->especialidade->nome` — use `{{ $avaliacoes->links() }}` |

#### `medico/perfil.blade.php` — GET `/medico/perfil`
| Variável | O quê |
|---|---|
| `$medico` | `crm`, `uf`, `bio`, `anos_atuacao`, `telefone_profissional`, `senha_temporaria`, `especialidades` (com `pivot->principal`), `convenios` |
| `$especialidades`, `$convenios` | listas ativas para os checkboxes |

- PUT `route('medico.perfil.atualizar')` — `name`, `crm`, `uf`, `bio`, `anos_atuacao`, `telefone_profissional`.
  Trocar CRM/UF passa de novo pela base simulada.
- PUT `route('medico.perfil.especialidades')` — `especialidades[]`, `principal`. Recusa tirar especialidade com consulta futura.
- PUT `route('medico.perfil.convenios')` — `convenios[]`.
- PUT `route('password.update')` — `current_password`, `password`, `password_confirmation` (erros no bag `updatePassword`,
  sucesso em `session('status') === 'password-updated'`). Copie o bloco de `paciente/perfil.blade.php`.
- **Senha temporária:** se `$medico->senha_temporaria`, mostre o formulário de senha em destaque — o
  middleware `ExigirTrocaDeSenha` só deixa ele usar esta tela até trocar.


### 7.2 Clínica

#### `clinica/agenda.blade.php` — GET `/clinica/agenda?data=&local=&medico=&especialidade=`
| Variável | O quê |
|---|---|
| `$data`, `$anterior`, `$seguinte` | como na agenda do médico |
| `$consultas` | como na do médico + `medico->user->name` — **sem** acessibilidade (só o médico lê) |
| `$filtros` | os filtros usados (para manter os selects) |
| `$unidades`, `$medicos`, `$especialidades` | opções dos selects |
| `$resumo` | `['agendadas','realizadas','canceladas','faltas']` |

#### `clinica/medicos.blade.php` — GET `/clinica/medicos`
| Variável | O quê |
|---|---|
| `$vinculos` | ativos: `medico->user->name`, `medico->crm/uf`, `medico->especialidades`, `local->nome`, `precos`, `aceita_convenio` |
| `$unidades` | unidades ativas |

- DELETE `route('clinica.medicos.desvincular', $vinculo)` — `cancelar_consultas` (checkbox). Sem ele, com consulta
  futura, volta `session('erro')`.
- Link para `route('clinica.medicos.novo')`.

#### `clinica/medicos-form.blade.php` — GET `/clinica/medicos/novo`
| Variável | O quê |
|---|---|
| `$especialidades`, `$unidades` | para os campos |

- POST `route('clinica.medicos.salvar')` — `crm`, `uf`, `local_id`, `aceita_particular`, `aceita_convenio`
  e, **só se o médico ainda não tiver conta**: `name`, `email`, `cpf`, `especialidades[]`.
  Médico que já existe (mesmo CRM/UF) só ganha o vínculo.
- Depois de salvar vai para Preços com `session('senha_temporaria')` (se foi conta nova) — **mostre em
  destaque, uma vez só**, para a clínica repassar ao médico.

#### `clinica/unidades.blade.php` — GET `/clinica/unidades`
| Variável | O quê |
|---|---|
| `$locais` | `nome`, `tipo`, `endereco_completo`, `ativo`, `vinculos_count`, `horarios` (`dia_semana`, `abre`, `fecha`) |
| `$dias` | dias da semana |

- POST `route('clinica.unidades.salvar')` — `nome`, `tipo` (clinica/hospital), `cep`, `endereco`, `numero`,
  `complemento`, `bairro`, `cidade`, `uf`, `telefone`, `horarios[dia][abre|fecha]`.
- PUT `route('clinica.unidades.horarios', $local)` — `horarios[dia][abre|fecha]` (dia vazio = fechado).

#### `clinica/precos.blade.php` — GET `/clinica/precos` (grade)
| Variável | O quê |
|---|---|
| `$vinculos` | `medico->user->name`, `medico->especialidades`, `local->nome`, `precos` (`especialidade_id`, `valor`, `ativo`) |

- POST `route('clinica.precos.salvar')` — **a grade inteira**: `precos[VINCULO_ID][ESPECIALIDADE_ID] = "250,00"`.
  Campo vazio = aquela especialidade deixa de ser oferecida naquele lugar. Erros por célula:
  `@error('precos.' . $v->id . '.' . $esp->id)`.

#### `clinica/avaliacoes.blade.php` — GET `/clinica/avaliacoes`
| Variável | O quê |
|---|---|
| `$media`, `$total` | da clínica |
| `$porMedico` | `medico->user->name`, `media`, `total` |
| `$avaliacoes` | paginado, **com** `comentario`, `medico->user->name`, `consulta->vinculo->local->nome` |

#### `clinica/perfil.blade.php` — GET `/clinica/perfil`
| Variável | O quê |
|---|---|
| `$clinica` | `cnpj` (só leitura — use `App\Support\Documento::cnpj()`), `razao_social` (só leitura), `nome_fantasia`, `descricao`, `telefone` |

- PUT `route('clinica.perfil.atualizar')` — `name` (responsável), `nome_fantasia`, `descricao`, `telefone`.
- Senha: bloco de `paciente/perfil.blade.php`.


### 7.3 Admin

#### `admin/usuarios.blade.php` — GET `/admin/usuarios?tipo=&status=&busca=`
| Variável | O quê |
|---|---|
| `$usuarios` | paginado: `name`, `email`, `tipo`, `status`, `motivo_bloqueio`, `bloqueado_em` |

- POST `route('admin.usuarios.bloquear', $u)` — `motivo` (mín. 10), `cancelar_consultas` (checkbox; com
  consulta futura é obrigatório). Não aparece para admin.
- POST `route('admin.usuarios.desbloquear', $u)` — só para `status = bloqueado`.

#### `admin/verificacoes.blade.php` — GET `/admin/verificacoes`
Desde 24/09 o CRM é conferido na base simulada no cadastro: a fila costuma estar vazia. Tela vira
histórico + "rejeitar" (tirar da plataforma).

| Variável | O quê |
|---|---|
| `$pendentes`, `$recentes` | médicos: `user->name`, `crm`, `uf`, `status_verificacao`, `verificado_em`, `motivo_rejeicao` |

- POST `route('admin.verificacoes.aprovar', $m)`; POST `route('admin.verificacoes.rejeitar', $m)` — `motivo` (mín. 10).
  Rejeitar cancela as consultas futuras dele. Texto da tela: "conferido na base simulada", **nunca** "validado no CFM".

#### `admin/carteirinhas.blade.php` — GET `/admin/carteirinhas?status=`
| Variável | O quê |
|---|---|
| `$pendentes` | (normalmente vazia) |
| `$recentes` | paginado: `paciente->user->name`, `plano->convenio->nome`, `plano->nome`, `numero_carteirinha`, `status`, `validade` |

#### `admin/clinicas.blade.php` — GET `/admin/clinicas?busca=`
| Variável | O quê |
|---|---|
| `$clinicas` | paginado: `nome_fantasia`, `razao_social`, `cnpj`, `user->status`, `locais_count`, `locais` |

#### `admin/especialidades.blade.php` — GET `/admin/especialidades`
| Variável | O quê |
|---|---|
| `$especialidades` | `nome`, `slug`, `icone`, `destaque`, `ativo`, `medicos_count` |

- POST `route('admin.especialidades.salvar')` — `nome`, `icone`, `destaque`.
- PUT `route('admin.especialidades.atualizar', $esp)` — `nome`, `icone`, `destaque`, `ativo`. (A URL usa o
  **slug**: `/admin/especialidades/cardiologia`.) Recusa desativar com consulta futura.

#### `admin/consultas.blade.php` — GET `/admin/consultas?status=&medico=&de=&ate=`
| Variável | O quê |
|---|---|
| `$consultas` | paginado: data, horário, status, `paciente->user->name`, `medico->user->name`, `especialidade->nome`, `vinculo->local->nome`, `valor` |
| `$porStatus` | `['agendada' => n, ...]` com os filtros aplicados |
| `$filtros` | filtros usados |

**Não mostrar** observações nem acessibilidade aqui.

---

## 8. Identidade visual e tom de voz

**Marca:** mão envolvendo um coração, em dois azuis. Slogan: *"Sua saúde, conectada."*
Arquivos em `public/imgs/marca/` (`logo-horizontal.png` no cabeçalho, `logocomslogan.png` no
login, `simbolo.png`/`logosemslogan.png` no rodapé e favicon).

**Cores:** marinho `#183E9F` (menu, títulos), azul-claro `#1C9CE5` (destaque), azul do coração
`#4A9BDA`. Cards brancos arredondados, fundo claro. Verde, roxo e rosa **só** para status —
nunca como cor de marca.

**Onde está o visual:**
- Site público → `public/css/home.css` (a home que o grupo fez) + `site.css`
- Login → `public/css/login.css` (o login que o grupo fez)
- Cadastro → `public/css/cadastro.css` + `public/javas/cadastro.js` (o cadastro que a Mari fez, 29/09)
- Painéis → `public/css/painel.css` + `crud.css` + `agendamento.css`
- Menus de cada tipo de conta → `config/navegacao.php`. **Item de menu sem tela é link morto:**
  exames, receitas, atestados, prontuário, "resumo da saúde", financeiro e relatórios foram
  tirados dos mockups de propósito (fora do escopo).

**Tom de voz:** direto, transparente, acolhedor sem ser íntimo, calmo. Segunda pessoa, frase
curta: "Sua consulta está agendada para quinta, 14h." — não "Informamos que o seu agendamento
foi processado com sucesso".

**Evitar:** qualquer palavra que sugira leitura clínica ("estável", "risco", "alerta de saúde");
"validado" (o certo é **"conferido na base simulada do FacilMed"**); urgência fabricada
("últimas vagas"); jargão de sistema; emoji em e-mail.

---

## 9. Decisões e o porquê

| Decisão | Por quê |
|---|---|
| Refazer em **Laravel**, em vez de evoluir o PHP puro | o protótipo tinha furos de segurança (sessão, senha, preço vindo do formulário) e nenhuma estrutura para crescer |
| **Laravel 12**, não 13 | o 13 exige PHP 8.3, e o XAMPP parou no 8.2.12 |
| **Blade + CSS próprio + Alpine**, sem Node/npm (24/09) | as telas nunca usaram Tailwind de fato; tirar o build tirou um programa da máquina de cada um |
| Repositório = **projeto completo** + `index.php` na raiz (24/09) | antes cada um montava o Laravel e copiava pastas; agora é `composer run setup` |
| **Bases simuladas** para CRM, CNPJ e carteirinha (24/09) | a API do CFM é paga (R$ 772/ano, exige CNPJ); carteirinha não tem padrão nacional nem API; a conferência humana travava a demonstração |
| Convênios e planos **100% fictícios** (24/09) | não usar nome de operadora real sem contrato |
| Convênio aceito pelo **médico**, não pelo endereço | simplifica o cadastro; o aviso "confirme na recepção" cobre a exceção |
| Preço por **vínculo + especialidade** | o mesmo médico cobra diferente por especialidade e por endereço |
| Cancelamento com menos de 24h **permitido e marcado** | bloquear só transforma cancelamento em falta, e falta perde o horário |
| Comentário de avaliação **privado** | reduz risco jurídico de comentário público sobre profissional de saúde |
| Acessibilidade em **texto**, sem upload de laudo | guardar laudo tornaria o projeto depositário de dado sensível de saúde |
| Sem pagamento, SUS e teleconsulta | fora do que um TCC consegue fazer direito até 20/10 |
| Médico **não se cadastra sozinho**: entra pela clínica (29/09) | plano do app de 28/09; a clínica já cadastrava o médico conferindo o CRM na base simulada, então nada de regra mudou — só sumiu um caminho a mais |

**Becos sem saída (não repita):** validar CRM de graça por código; validar carteirinha por
algoritmo; consultar o COMPROVA da ANS; checar horário livre só com SELECT antes do INSERT
(duas pessoas passam juntas — use o índice único); XAMPP com PHP 8.3 (não existe).

---

## 10. Equipe, cronograma e regras de trabalho

| Pessoa | Frente |
|---|---|
| **Sidney** | back-end: serviços de horário e alocação, agendamento, Policies, FormRequests, e-mails |
| **Cipriano** | front-end: layout, componentes e telas (seção 7) |
| **Mariana** | documentação + área administrativa |
| **Nicolle** | documentação + qualidade: textos da interface, roteiro de teste, dados de demonstração |
| **Murilo** | fundação + revisão de Pull Request antes do merge |

| Período | Foco |
|---|---|
| 18–24/09 | fundação: projeto rodando, banco populado ✅ |
| 25/09–01/10 | cadastros e perfis ✅ (back-end) · telas internas |
| 02–08/10 | núcleo: busca, horários, agendar, cancelar, remarcar ✅ · telas internas |
| 09–15/10 | fechamento: e-mails ✅, avaliações, dashboards |
| 16–20/10 | integração, testes, documentação, ensaio — **não construir nada novo** |

**Regras para todos:**
1. **Branch por frente. Ninguém commita direto na `main`.**
2. **Migration já aplicada não se edita** — cria-se uma nova.
3. Commits pequenos, uma intenção por commit.
4. **Atualize a seção 6 deste README** a cada etapa concluída.
5. Regra de negócio mora em Policy, FormRequest, Model ou constraint — nunca em `if` na Blade.
6. Não reescreva o código dos outros depois que está na `main`: comente no PR.
7. Faça o **caminho feliz inteiro** antes de caprichar em qualquer tela.

**Riscos:** o back-end concentrado numa pessoa (mitigação: código comentado explicando o porquê,
testes automáticos); a curva de Laravel do grupo (leiam os comentários); o design promete mais
que o escopo (use `config/navegacao.php`); a banca perguntar da "verificação de CRM" (resposta
honesta: base simulada, e por que a API oficial ficou de fora).

---

## 11. Histórico de correções

Em 24/09/2026 o código **rodou pela primeira vez** (antes só tinha sido escrito). Foram achados
e corrigidos 18 problemas — todos com teste automático hoje:

| # | O que acontecia |
|---|---|
| 1 | Todas as telas do médico e da clínica davam 404 (a rota do perfil público "engolia" `/medico/agenda`) |
| 2 | Nenhum paciente conseguia se cadastrar sem marcar o consentimento de acessibilidade |
| 3 | Cadastro de médico com CRM válido dava erro 500 com "anos de atuação" em branco |
| 4 | Cadastro de clínica recusava CEP digitado com hífen |
| 5 | Por convênio dava para marcar especialidade que o médico não atende |
| 6 | Médico podia marcar como "realizada" uma consulta que ainda não aconteceu |
| 7 | Motivo de cancelamento longo derrubava a página |
| 8 | Mensagens de erro em inglês |
| 9 | Fuso horário em UTC ("agora" 3h adiantado) |
| 10 | Todo cancelamento era marcado como tardio (sinal do `diffInHours` no Carbon 3) |
| 11 | Conta bloqueada continuava navegando |
| 12 | Protótipo antigo e Laravel usavam o mesmo banco e as tabelas colidiam |
| 13 | `/register` do Breeze criava conta sem tipo |
| 14 | Acessibilidade podia ser salva sem descrição |
| 15 | Mensagens sem acento |
| 16 | Médico não conseguia cancelar consulta futura |
| 17 | Troca de senha aceitava mais de 72 caracteres |
| 18 | Os testes quebravam com o `APP_URL` do XAMPP |
| 19 | *(28/09)* Médico com a **conta bloqueada** continuava com horários livres e recebia consulta |
| 20 | *(28/09)* Por convênio dava para marcar especialidade cujo preço estava **desativado** naquele lugar |
| 21 | *(28/09)* O **motivo do bloqueio**, quem bloqueou e quando **nunca eram gravados** (campos fora do `$fillable`, descartados em silêncio) |
| 22 | *(28/09)* "Aprovar" CRM no admin não conferia a base simulada — um CRM cassado ganhava a etiqueta "conferido" |
| 23 | *(28/09)* Página pública da clínica listava médico bloqueado e especialidade sem preço (o paciente caía em "sem vaga") |
| 24 | *(28/09)* Data inválida na URL (agendar pela clínica, filtro de consultas do admin) dava erro 500 |
| 25 | *(28/09)* Especialidade com nome que gera o mesmo endereço de outra dava erro 500 |
| 26 | *(28/09)* Mensagens de erro sem acento nos cadastros e no agendamento; filtro das consultas do paciente sumia ao trocar de página |
| 27 | *(28/09, 2ª rodada)* Agendar **pela clínica** mostrava horários de especialidade **desativada** pelo admin; o paciente só era barrado na confirmação |
| 28 | *(28/09, 2ª rodada)* Duplo clique em "Enviar avaliação" dava **erro 500** (o UNIQUE recusava o segundo envio, com a avaliação já salva) |
| 29 | *(28/09, 2ª rodada)* A tela da consulta por convênio, que abre logo depois de agendar, não repetia o aviso "Confirme na recepção..." |
| 30 | *(28/09, 3ª rodada)* Preço digitado com ponto (`150.00`) era salvo como **R$ 15.000,00** (preço do médico e grade da clínica) |
| 31 | *(28/09, 3ª rodada)* A **acessibilidade** do paciente aparecia na agenda da clínica e, na do médico, até em consulta cancelada ou realizada |
| 32 | *(28/09, 3ª rodada)* Dava para se cadastrar com o **CRM de outra pessoa** (a base simulada não conferia o nome) |
| 33 | *(28/09, 3ª rodada)* Médico **rejeitado** pelo admin voltava a "verificado" trocando o CRM no perfil e reaparecia na busca |
| 34 | *(28/09, 3ª rodada)* Perfil público mostrava "Agendar aqui" em **clínica bloqueada** (caía em 404) e preço de especialidade desativada |
| 35 | *(28/09, 3ª rodada)* A busca mostrava médico **sem lugar para agendar** (ou sem preço da especialidade buscada); filtro em lista na URL dava erro 500 |
| 36 | *(28/09, 3ª rodada)* Ausência registrada de novo para cancelar as consultas (como a mensagem manda) ficava **duplicada** |
| 37 | *(28/09, 3ª rodada)* "Aprovar" carteirinha no admin não conferia a base simulada |
| 38 | *(28/09, 3ª rodada)* **Caixas de marcar quebradas** no cadastro de médico (só aparecia "C", "D"...), no de paciente e no de médico pela clínica (marcada parecia desmarcada) |
| 39 | *(28/09, 3ª rodada)* No celular, as abas de "Minhas consultas" passavam da largura da tela; comentários antigos no código contradiziam o AGENTS (senha "6 a 10", convênio "não pode ser inventado") |
| 40 | *(29/09)* Pelo XAMPP (`/FacilMed`), `/register` e `/cadastro/medico` redirecionavam para `http://localhost/cadastro` (404): o `Route::redirect` perde a subpasta. Agora usam `redirect()->route()` |

---

## 12. Protótipo antigo

`prototipo-antigo/` guarda a primeira versão, em PHP puro, sem framework: `php/`, `paginas/`,
`css/`, `js/`, `imgs/`, `banco/`, e `referencia-original/` (os arquivos do protótipo que
inspiraram o modelo novo). **O sistema em Laravel não usa nada dali** — o visual da home e do
login já foi trazido para `resources/views/` e `public/css/`. Não evoluir; serve para consulta
e para o capítulo do TCC sobre a evolução do projeto.

Para abrir: importe `prototipo-antigo/banco/bancofacilmed.sql` no phpMyAdmin (cria o banco
**separado** `facilmed_prototipo`) e acesse
`http://localhost/FacilMed/prototipo-antigo/paginas/index.html`.
