# AGENTS.md — PointMed

> Regras para **qualquer agente de IA** que trabalhar neste projeto (Claude Code, Codex, Cursor…).
> Fica na raiz porque as ferramentas procuram este arquivo aqui.
>
> **Todo o resto — como rodar, contas de teste, pastas, arquitetura, estado atual, contrato das
> telas — está no `README.md`.** Leia o README (principalmente §4, §5 e §6) antes de editar.
> Se algo aqui conflitar com outro arquivo, **este vence**.
>
> Última revisão: 05/10/2026 (notas do Lucas): o projeto se chama **PointMed** (era FacilMed);
> quem busca clínicas é o **usuário** (não "paciente"); a faixa de preço é escolhida pela clínica em
> cada unidade; o comentário das avaliações é público. Antes, em 01/10/2026: o projeto deixou de
> agendar consultas e o médico deixou de ter conta.
>
> **Nome PointMed (05/10/2026).** Todo texto de tela, e-mail, logo e documento diz **PointMed**. Os
> nomes internos ficam de propósito: pasta `FacilMed`, URL `localhost/FacilMed`, banco `facilmed`,
> e-mails `@facilmed.test` e comandos `facilmed:` — trocar quebraria a máquina de todo o grupo.
>
> **"Usuário" × "users" (05/10/2026).** `users` é a CONTA de acesso de todo mundo (usuário, clínica,
> admin). `usuarios` (model `Usuario`) é o PERFIL de quem busca clínicas. Em tela e código novo,
> nunca "paciente". Os nomes antigos de índice (ex.: `uq_avaliacao_paciente_local`) ficaram.

## 1. Escopo — o que este projeto NÃO é

Ampliar o escopo por conta própria é errado, mesmo que a funcionalidade apareça num mockup.

- **Não é prontuário eletrônico**: nada de evolução clínica, diagnóstico, receita, atestado, exame.
- **Não é telemedicina**, **não processa pagamento**, **não integra com o SUS**.
- **Não agenda consultas** (desde 01/10/2026). Mostra locais, médicos, convênios, faixa de preço e
  avaliações. Agenda, horários do médico e lembretes por e-mail **não** voltam sem decisão do grupo.
- **Não armazena documento médico** (nenhum upload de laudo). A acessibilidade do usuário saiu em
  01/10/2026 junto com as consultas (sem finalidade, a LGPD manda não guardar). O único upload é a
  **foto de perfil** (imagem JPG/PNG/WEBP até 2 MB, por `App\Support\FotoDePerfil`), guardada
  **no banco** (tabela `fotos`, 07/10/2026) porque o Render apaga arquivo enviado a cada deploy.
- **Não emite parecer clínico** de nenhum tipo (ver §3).
- **Não é produto em produção**: TCC, roda localmente, dados fictícios. Entrega 20/10/2026.

## 2. Stack

- **Laravel 12** (não o 13: exige PHP 8.3 e o XAMPP parou no 8.2.12) · **PHP 8.2** · **MariaDB** do XAMPP.
- Front: **Blade + CSS próprio (`public/css/`) + Alpine.js local**. **Sem Vite, sem Node/npm.**
  Sem SPA, React, Vue ou Livewire.
- O repositório **é** o projeto completo. `index.php` + `.htaccess` da raiz fazem rodar em
  `http://localhost/FacilMed`. Fuso `America/Sao_Paulo`.
- **Não instalar dependência nova** (Composer ou npm) sem registrar a decisão no README §6 e ter aprovação.
- "Estado bom": `php artisan migrate:fresh --seed` roda limpo **e** `php artisan test` passa
  (116 testes, banco `facilmed_testes` — nunca o `facilmed`; os de foto precisam da extensão GD
  ligada no `php.ini` do XAMPP).

## 3. Regras invioláveis de negócio e de dados

Estas são as regras que, se quebradas, fazem o sistema mentir para quem usa. Toda regra abaixo
precisa estar refletida numa constraint de banco, numa Policy ou num FormRequest — não só na
intenção de quem escreveu a tela.

**Verificação e honestidade**

- **Nunca** exibir em busca, listagem ou perfil público um médico cujo `status_verificacao`
  não seja `verificado`.
- **Médico não tem conta (01/10/2026).** É um PERFIL cadastrado e mantido pela clínica/hospital
  onde atende. `users.tipo` só aceita `usuario`, `clinica` e `admin` (o ENUM garante). Quem edita
  o perfil: clínica com vínculo ativo com ele (`MedicoPolicy`). Nome, CRM e UF não mudam por tela
  (foram conferidos na base simulada). **Nunca** recriar login, senha ou área de médico.
- **BASES SIMULADAS (decisão do grupo, 24/09/2026 — substitui a conferência humana de 18/09).**
  CRM, CNPJ e carteirinha são conferidos **automaticamente, na hora do cadastro**, nas tabelas
  `base_crms`, `base_cnpjs` e `base_carteirinhas` (`App\Services\BaseSimulada`). Bateu →
  aprovado (médico `verificado`, carteirinha `ativa`). Não bateu → recusado com o motivo.
  O admin acompanha o CNPJ das clínicas em **Verificar CNPJ** (só leitura, situação atual na base).
- **Nunca** afirmar, em tela, e-mail ou texto, que algo foi "validado junto ao CFM", "à Receita"
  ou "à operadora". O texto correto é **"conferido na base simulada do PointMed"**. Nenhuma
  integração real existe (API do CFM é paga; COMPROVA/TISS não permitem consulta por terceiro).
- **Nunca** escrever nas bases simuladas por tela ou controller. Só o `BaseSimuladaSeeder`
  preenche — senão qualquer um "validaria" o próprio dado.
- **Convênios e planos são 100% fictícios** (decisão do grupo em 24/09/2026, substituindo a
  regra anterior de "operadora real da ANS + plano fictício"). `operadora_ans_id` passou a ser
  opcional. **Nunca** usar nome, CNPJ ou logo de operadora real num convênio, e **sempre**
  deixar claro na tela e no texto do TCC que a plataforma não tem contrato com nenhuma operadora.
- **Nunca** apagar convênio ou plano: desativar (`ativo = false`). Apagar leva em cascata os
  planos e as carteirinhas dos usuários.

**Nada de conteúdo clínico**

- **Nunca** exibir, calcular ou gerar avaliação clínica sobre o usuário — incluindo rótulos
  como "status de saúde", "estável", "risco", alerta de saúde ou qualquer sugestão de
  diagnóstico. Um guia de clínicas não tem dado nem competência para isso, e afirmar
  isso na tela é a falha mais grave que este projeto pode cometer.
- **Nunca** implementar receita, atestado, prontuário ou resultado de exame. Ver §2.

**Preço e convênio**

- **Nunca** mostrar valor de consulta em reais (decisão do grupo, 01/10/2026). A **Tabela de preços
  saiu em 05/10/2026**: a **clínica escolhe a faixa** ($ a $$$$) **de cada unidade** em Clínica →
  Unidades (`locais.faixa_preco`, 1 a 4 ou vazio; CHECK `chk_local_faixa`; Policy `update` do local).
  O que cada faixa significa (ex.: $$ = R$ 200 a R$ 350) e o símbolo vêm **só** de
  `App\Support\FaixaDePreco`; para mudar, mude `FaixaDePreco::LIMITES` — nunca uma tela.
- A especialidade oferecida numa unidade = especialidade **ativa** de um médico com vínculo ativo
  ali (`Vinculo::scopeOferece`). Não existe mais "médico sem preço" escondido da busca.
- O convênio é aceito **pelo médico** (tabela `convenio_medico`), decidido em 18/09/2026 — e não
  por endereço; em cada unidade a clínica liga/desliga `vinculos.aceita_convenio`. Como na vida real
  o médico pode não aceitar o plano num dos endereços, **toda tela que mostra convênio aceito exibe
  o aviso** "Confirme na recepção se o seu plano é aceito neste endereço." Obrigatório, não decorativo.

**Avaliação (01/10/2026)**

- O usuário logado avalia o **local** ou o **médico** direto, sem consulta. **Uma** avaliação por
  usuário em cada local e em cada médico — avaliar de novo **edita**. Garantido no banco
  (`uq_avaliacao_paciente_local`, `uq_avaliacao_paciente_medico`, `chk_avaliacao_um_alvo`,
  `chk_avaliacao_estrelas`). Clínica e admin não avaliam.
- A média fica em `media_avaliacoes`/`total_avaliacoes` do local e do médico, recalculada **só**
  pelo model `Avaliacao` (eventos `saved`/`deleted`). Nunca gravar média à mão.

**Privacidade**

- **Comentário de avaliação é PÚBLICO (decisão do grupo, 05/10/2026).** Nota e comentário aparecem
  nas páginas do local e do médico (`publico/parciais/avaliacoes`, as 10 mais recentes). O autor
  aparece **só** como "Ana L." (`Formatador::nomeCurto`), **sem** foto, nome completo ou e-mail —
  nunca expor esses três em tela pública. A clínica avaliada (`Avaliacao::daClinica`) e o admin
  continuam vendo as avaliações nos painéis deles.
- **Nunca** salvar a localização do usuário (banco, sessão, log). Ela vai só na URL da busca de
  locais, arredondada, e serve só para ordenar por distância (29/09/2026).
- **Nunca** apagar usuário com `delete()` nem fazer um segundo caminho de exclusão de conta: a
  exclusão pedida pelo usuário (LGPD, 30/09/2026) é **só** por `Usuario::excluirConta()`, que
  anonimiza (a nota fica na média, o dado pessoal, o comentário e a foto somem). Conta excluída (`excluida_em` preenchido) nunca
  volta a `ativo` — o CHECK `chk_users_excluida_inativa` garante no banco. Dado pessoal novo ligado ao
  usuário (coluna ou tabela) precisa entrar no `excluirConta()`, com teste.
- **Nunca** apresentar a distância como exata: a coordenada do local é aproximada (bairro ou centro
  da cidade) e a conta é em linha reta. O texto da tela diz "aproximada, em linha reta".

**E-mail**

- O PointMed não envia e-mail próprio desde 01/10/2026 (os lembretes de consulta saíram). Só o
  Breeze manda o e-mail de troca de senha — no Render, pela API do Brevo (`App\Mail\BrevoTransport`,
  `MAIL_MAILER=brevo` + `BREVO_API_KEY`); sem a chave, cai no log.
- **Nunca** usar e-mail real de pessoa real nos seeders ou em teste.

**Contas e validação de entrada**

- **Nunca** deixar entrar uma conta com `status` diferente de `ativo`. Conta `bloqueada` é
  barrada no middleware de autenticação, não escondida na interface — e a mensagem diz que a
  conta está bloqueada, sem detalhar o motivo.
- **Nunca** aceitar cadastro incompleto. Toda validação mora em FormRequest, nunca só no
  JavaScript: `required` do HTML e máscara de campo são conforto visual, não validação.
- **Senha: mínimo 8 caracteres, máximo 72.** O máximo de 72 é o limite técnico do bcrypt, não
  uma escolha de produto. **Não** existe limite de 10 caracteres: limitar o tamanho máximo de
  uma senha a um número baixo enfraquece a segurança sem ganho nenhum, e é o tipo de detalhe que
  um avaliador atento cobra. Não exigir composição obrigatória (um maiúsculo, um símbolo): as
  recomendações atuais de segurança desaconselham essas regras, porque produzem senhas piores e
  mais previsíveis. Comprimento é o que importa.
- **Nunca** deixar o login sem `throttle`. Sem limite de tentativas, o formulário aceita força
  bruta. *(O protótipo antigo não tinha.)*
- **Nunca** manter o mesmo ID de sessão depois de um login bem-sucedido. O Laravel regenera
  sozinho quando se usa `Auth::login()` com o fluxo padrão — não contorne isso com sessão
  manual. *(O protótipo antigo criava a sessão sem regenerar o ID: brecha de session fixation.)*

## 4. Regras de edição

- **Plano antes de editar** (ver §6). Commits pequenos, uma intenção por commit.
- **Branch por frente de trabalho. Ninguém commita direto na `main`.**
- **Migration já aplicada não se edita** — cria-se uma nova.
- Regra de negócio mora em Policy, FormRequest, Model ou constraint — **não** em `if` na Blade.
- **Toda mudança de back-end vem com teste** em `tests/Feature/`. Mudou regra e nenhum teste quebrou? Falta teste.
- Faixa de preço **só** por `App\Support\FaixaDePreco`; foto **só** por `App\Support\FotoDePerfil`.
- **Não criar arquivo .md novo na raiz.** Documentação vai no `README.md` (seção certa).
- Não mexer no `.htaccess` da raiz sem testar que `/FacilMed/.env` continua dando **403**.
- Não alterar `.env` nem credenciais. `vendor/` é gerado. `prototipo-antigo/` é só leitura.

### 4.1 Rito para mudanças com side-effect ⚠ (e-mail)

O side-effect deste projeto é o e-mail de troca de senha (Breeze; no Render, pelo Brevo). As fotos
ficam no banco desde 07/10/2026 e, nos testes, somem com a transação do `RefreshDatabase`.

1. Desenvolver com `MAIL_MAILER=log` no `.env`. O e-mail cai em `storage/logs/laravel.log`.
   Envio para caixa real **só com aprovação explícita**, e só para e-mail de integrante do grupo.
2. Nos testes, o Brevo é sempre `Http::fake()` — nenhum teste fala com a API de verdade.
3. Registrar no README §6 o que foi disparado.

## 5. Restrições de segurança

- Nunca executar comando destrutivo — apagar em massa, `migrate:fresh` no banco de outra
  pessoa, reescrever histórico remoto, resetar branch — **sem aprovação explícita**.
- Nunca usar nem expor credencial real em log, código, commit ou handoff.
- Senha sempre via `Hash::make` / `bcrypt`. Nunca em texto puro, nem em seeder de demonstração
  (no seeder, use senha fictícia óbvia e documente qual é).
- Toda query com input do usuário passa por Eloquent ou query bindings. Nunca concatenar string
  em SQL.
- Toda rota autenticada protegida por middleware **e** por Policy. Middleware diz "está logado";
  Policy diz "é dono disso". Faltar a segunda é o furo clássico: a clínica A editando o médico
  que só atende na clínica B, ou o usuário A apagando a avaliação do usuário B.
- ⚠ **Um agente por vez** ao tocar o banco compartilhado ou disparar e-mail. Branch isola código,
  não isola banco nem caixa de entrada.

## 6. Protocolo de passagem de bastão

**Ao começar:** `git log --oneline -10` e `git status` → ler README §6 (estado atual) e este
arquivo → listar os arquivos da tarefa → plano curto → riscos (§3 e §4.1) → **aguardar aprovação**.

**Ao terminar:** atualizar o **README §6** (o que foi feito, decisões, becos sem saída, próximo
passo) → `migrate:fresh --seed` + `php artisan test` passando (ou registrar o que quebrou e por
quê) → commit. Nunca trocar de agente com mudança não commitada e §6 desatualizada.
