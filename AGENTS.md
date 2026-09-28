# AGENTS.md — FacilMed

> Regras para **qualquer agente de IA** que trabalhar neste projeto (Claude Code, Codex, Cursor…).
> Fica na raiz porque as ferramentas procuram este arquivo aqui.
>
> **Todo o resto — como rodar, contas de teste, pastas, arquitetura, estado atual, contrato das
> telas — está no `README.md`.** Leia o README (principalmente §4, §5 e §6) antes de editar.
> Se algo aqui conflitar com outro arquivo, **este vence**.
>
> Última revisão: 24/09/2026.

## 1. Escopo — o que este projeto NÃO é

Ampliar o escopo por conta própria é errado, mesmo que a funcionalidade apareça num mockup.

- **Não é prontuário eletrônico**: nada de evolução clínica, diagnóstico, receita, atestado, exame.
- **Não é telemedicina**, **não processa pagamento**, **não integra com o SUS**.
- **Não armazena documento médico** (nenhum upload de laudo). Acessibilidade é texto declarado pelo paciente.
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
  (89 testes, banco `facilmed_testes` — nunca o `facilmed`).

## 3. Regras invioláveis de negócio e de dados

Estas são as regras que, se quebradas, fazem o sistema mentir para quem usa. Toda regra abaixo
precisa estar refletida numa constraint de banco, numa Policy ou num FormRequest — não só na
intenção de quem escreveu a tela.

**Verificação e honestidade**

- **Nunca** exibir em busca, listagem ou perfil público um médico cujo `status_verificacao`
  não seja `verificado`.
- **BASES SIMULADAS (decisão do grupo, 24/09/2026 — substitui a conferência humana de 18/09).**
  CRM, CNPJ e carteirinha são conferidos **automaticamente, na hora do cadastro**, nas tabelas
  `base_crms`, `base_cnpjs` e `base_carteirinhas` (`App\Services\BaseSimulada`). Bateu →
  aprovado (médico `verificado`, carteirinha `ativa`). Não bateu → recusado com o motivo.
- **Nunca** afirmar, em tela, e-mail ou texto, que algo foi "validado junto ao CFM", "à Receita"
  ou "à operadora". O texto correto é **"conferido na base simulada do FacilMed"**. Nenhuma
  integração real existe (API do CFM é paga; COMPROVA/TISS não permitem consulta por terceiro).
- **Nunca** escrever nas bases simuladas por tela ou controller. Só o `BaseSimuladaSeeder`
  preenche — senão qualquer um "validaria" o próprio dado.
- **Convênios e planos são 100% fictícios** (decisão do grupo em 24/09/2026, substituindo a
  regra anterior de "operadora real da ANS + plano fictício"). `operadora_ans_id` passou a ser
  opcional. **Nunca** usar nome, CNPJ ou logo de operadora real num convênio, e **sempre**
  deixar claro na tela e no texto do TCC que a plataforma não tem contrato com nenhuma operadora.
- **Nunca** apagar convênio ou plano: desativar (`ativo = false`). Apagar leva em cascata os
  planos e as carteirinhas dos pacientes.

**Nada de conteúdo clínico**

- **Nunca** exibir, calcular ou gerar avaliação clínica sobre o paciente — incluindo rótulos
  como "status de saúde", "estável", "risco", alerta de saúde ou qualquer sugestão de
  diagnóstico. Um sistema de agendamento não tem dado nem competência para isso, e afirmar
  isso na tela é a falha mais grave que este projeto pode cometer.
- **Nunca** implementar receita, atestado, prontuário ou resultado de exame. Ver §2.

**Agendamento**

- **Nunca** remover a coluna virtual `horario_ativo` nem o índice
  `UNIQUE (medico_id, data_consulta, horario_ativo)` da tabela `consultas`. É o que impede
  duas pessoas marcarem o mesmo horário em requisições simultâneas. Checagem em PHP antes do
  INSERT **não** substitui isso.
- **Nunca** oferecer horário que caia dentro de um registro de `bloqueios` do médico.
- **Nunca** permitir agendamento por convênio que o médico não aceite, nem em local ao qual ele
  não esteja vinculado. O convênio é aceito **pelo médico** (tabela `convenio_medico`), decidido
  em 18/09/2026 — e não por endereço. Consequência a mitigar: como o mesmo médico pode, na vida
  real, não aceitar o plano em um dos endereços, **toda tela de confirmação de consulta por
  convênio exibe o aviso** "Confirme na recepção se o seu plano é aceito neste endereço."
  Esse aviso é obrigatório, não decorativo.
- Cancelamento é **sempre permitido**. Abaixo de 24h ele é registrado como cancelamento tardio,
  mas nunca bloqueado — bloquear só transforma cancelamento em falta.

**Privacidade**

- **Nunca** expor o comentário de uma avaliação para o paciente, para outros pacientes ou em
  qualquer tela pública. Comentário é visível apenas para a clínica/médico avaliado e admin.
  Nota em estrelas é pública.
- **Nunca** ler ou exibir `paciente_acessibilidade` fora do contexto de uma consulta agendada
  com aquele profissional. É dado sensível de saúde (LGPD art. 11). Não entra em listagem,
  busca, exportação nem log.
- **Nunca** permitir avaliação de consulta cujo `status` não seja `realizada`, nem por quem não
  é o paciente daquela consulta.

**E-mail**

- **Nunca** enviar e-mail sem gravar em `notificacoes_enviadas`. O `UNIQUE (consulta_id, tipo)`
  é o que impede o mesmo lembrete sair 24 vezes quando o scheduler roda de hora em hora.
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
- Cancelar consulta **só** por `Consulta::cancelar()`; e-mail **só** por `App\Services\Notificador`.
- **Não criar arquivo .md novo na raiz.** Documentação vai no `README.md` (seção certa).
- Não mexer no `.htaccess` da raiz sem testar que `/FacilMed/.env` continua dando **403**.
- Não alterar `.env` nem credenciais. `vendor/` é gerado. `prototipo-antigo/` é só leitura.

### 4.1 Rito para mudanças com side-effect ⚠ (envio de e-mail)

O único side-effect deste projeto é o disparo de e-mail. Ele sai da máquina e chega na caixa
de alguém; o Git não desfaz isso.

1. Desenvolver com `MAIL_MAILER=log` no `.env`. O e-mail cai em `storage/logs/laravel.log`.
2. Para conferir o visual, usar `php artisan tinker` com Mailtrap ou o `mail:preview` — nunca
   disparando para endereço real.
3. Envio para caixa real **só com aprovação explícita**, e só para e-mail de integrante do grupo.
4. Depois de qualquer teste de envio, conferir `notificacoes_enviadas` e **limpar as linhas de
   teste**, senão o lembrete de verdade daquela consulta nunca sai (o UNIQUE bloqueia).
5. Registrar no README §6 o que foi disparado.

## 5. Restrições de segurança

- Nunca executar comando destrutivo — apagar em massa, `migrate:fresh` no banco de outra
  pessoa, reescrever histórico remoto, resetar branch — **sem aprovação explícita**.
- Nunca usar nem expor credencial real em log, código, commit ou handoff.
- Senha sempre via `Hash::make` / `bcrypt`. Nunca em texto puro, nem em seeder de demonstração
  (no seeder, use senha fictícia óbvia e documente qual é).
- Toda query com input do usuário passa por Eloquent ou query bindings. Nunca concatenar string
  em SQL.
- Toda rota autenticada protegida por middleware **e** por Policy. Middleware diz "está logado";
  Policy diz "é dono disso". Faltar a segunda é o furo clássico: paciente A abrindo
  `/consultas/{id}` do paciente B.
- ⚠ **Um agente por vez** ao tocar o banco compartilhado ou disparar e-mail. Branch isola código,
  não isola banco nem caixa de entrada.

## 6. Protocolo de passagem de bastão

**Ao começar:** `git log --oneline -10` e `git status` → ler README §6 (estado atual) e este
arquivo → listar os arquivos da tarefa → plano curto → riscos (§3 e §4.1) → **aguardar aprovação**.

**Ao terminar:** atualizar o **README §6** (o que foi feito, decisões, becos sem saída, próximo
passo) → `migrate:fresh --seed` + `php artisan test` passando (ou registrar o que quebrou e por
quê) → commit. Nunca trocar de agente com mudança não commitada e §6 desatualizada.
