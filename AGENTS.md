# AGENTS.md — FacilMed

> **Fonte canônica de contexto e regras para QUALQUER agente de IA** que trabalhar neste
> projeto. `CLAUDE.md` apenas aponta para este arquivo. Se algo aqui conflitar com outro
> arquivo, **este vence**.
>
> Última revisão: **18/09/2026**

---

## 1. Objetivo do projeto

FacilMed é uma plataforma web de agendamento de consultas médicas. O paciente encontra médico
ou clínica por especialidade, vê preço e convênios aceitos, e marca a consulta sozinho — pelo
plano de saúde dele ou como particular. Médicos e clínicas se cadastram, publicam sua agenda e
recebem os agendamentos.

É o TCC de um grupo de 4+ alunos, com entrega em **20/10/2026**.

## 2. Escopo — o que este projeto NÃO é

Delimitação rígida. Agente que ampliar o escopo por conta própria está errado, mesmo que a
funcionalidade pareça óbvia ou apareça em algum mockup.

- **Não é prontuário eletrônico.** Não guarda evolução clínica, anamnese, diagnóstico, receita,
  atestado nem resultado de exame. Prontuário eletrônico é sistema regulado pelo CFM e está
  fora do escopo, sem exceção.
- **Não é telemedicina.** Não há videochamada, sala virtual nem consulta on-line. `Teleconsulta`
  foi explicitamente cortada do escopo em 18/09/2026.
- **Não processa pagamento.** A plataforma exibe e registra o valor; o pagamento acontece
  presencialmente. Não há gateway, cobrança, estorno nem conciliação.
- **Não integra com o SUS.** Cortado do escopo em 18/09/2026.
- **Não emite parecer clínico de nenhum tipo.** Ver §6.
- **Não armazena documento médico.** Nenhum upload de laudo, diagnóstico, exame ou receita.
  Decidido em 18/09/2026: a acessibilidade é declarada em texto pelo próprio paciente, e nada
  mais. Guardar imagem de laudo transformaria o projeto em depositário de dado sensível de
  saúde, com obrigações de segurança que um TCC não tem como cumprir.
- **Não é produto em produção.** É trabalho acadêmico, roda localmente, com dados fictícios.

## 3. Stack e integrações

> ⚠ = sistema/serviço com **side-effect**: altera estado **fora** do controle de versão.
> O Git não versiona nem reverte essas mudanças.

- **Linguagem / runtime:** **PHP 8.2** (via XAMPP 8.2.12). Confira com `php -v`.
  > ⚠ O XAMPP que estava na máquina do grupo trazia **PHP 8.0.7** (junho de 2021) — fora de
  > suporte e incompatível com qualquer Laravel atual. Todo integrante precisa atualizar.
- **Framework: Laravel 12**, não o 13. Decidido em 18/09/2026 por uma restrição concreta:
  o Laravel 13 exige PHP 8.3, e **o XAMPP parou no PHP 8.2.12** (novembro de 2023) — não
  existe XAMPP com 8.3+. O Laravel 12 roda em PHP 8.2 e tem correções de segurança até
  **24/02/2027**, bem depois da entrega de 20/10/2026. A alternativa (PHP avulso instalado
  à mão) foi descartada: seis pessoas configurando `php.ini` manualmente gera mais problema
  do que resolve.
  ```bash
  composer create-project laravel/laravel:^12.0 facilmed
  ```
  Depois de instalar, rode `php artisan --version` e **registre o número exato aqui**.
- **Banco: MariaDB** (o que vem no XAMPP), não MySQL. ⚠ Isso importa para a coluna virtual
  de `consultas`: se o índice único falhar ao ser criado, troque `VIRTUAL` por `PERSISTENT`
  na migration — e **nunca** remova o índice para "resolver".
- **Frontend:** Blade + Tailwind CSS + Alpine.js. Sem SPA, sem React, sem Vue, sem Livewire.
- **Banco:** MySQL (via XAMPP)
- **Onde roda:** localmente, XAMPP na máquina de cada integrante. Não há deploy.
- **Serviços externos:**
  - **SMTP (envio de e-mail)** ⚠ — dispara e-mail real para fora. Ver §7.1.
  - **Dados abertos da ANS** — arquivo CSV de operadoras ativas, baixado e importado. É a fonte
    da verdade sobre quais operadoras de plano de saúde existem.
  - **Portal do CFM** — consultado **manualmente por uma pessoa**, nunca por código. Ver §6.

### Dependências proibidas sem aprovação

Não instalar pacote Composer ou npm que não esteja nesta lista sem registrar a decisão no
`AI_HANDOFF.md` e obter aprovação. Cada dependência nova é uma coisa a mais para o grupo
aprender, configurar em 4 máquinas e defender na banca.

## 4. Fonte da verdade por assunto

| Assunto | Fonte da verdade | Onde fica |
|---|---|---|
| Estrutura do banco | migrations do Laravel | `database/migrations/` |
| Operadoras de plano de saúde existentes | CSV de dados abertos da ANS | `database/data/operadoras_ans.csv` + tabela `operadoras_ans` |
| Se um CRM é válido | portal do CFM, conferido por pessoa | registrado em `medicos.status_verificacao` |
| Se uma carteirinha é válida | comprovante COMPROVA emitido pelo paciente, conferido por pessoa | registrado em `paciente_planos.status` |
| Horário livre de um médico | `disponibilidades` menos `consultas` menos `bloqueios` | calculado, nunca armazenado |
| Preço da consulta particular | `precos` (vínculo + especialidade) | tabela `precos` |
| Identidade visual | `Descricao_Visual_FacilMed.pdf` | `docs/` |
| Estado do projeto e decisões recentes | `AI_HANDOFF.md` | raiz do projeto |
| Contexto de negócio, público e tom | `PERFIL.md` | raiz do projeto |

> Qualquer dado citado dentro de um documento é **snapshot datado**. Conferir na fonte antes de usar.

## 5. Como rodar e validar ("estado bom")

```bash
# instalar:
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed

# rodar localmente (dois terminais):
php artisan serve
npm run dev

# terceiro terminal, só quando for testar e-mail:
php artisan schedule:work

# o que define "verde":
php artisan migrate:fresh --seed   # roda do zero sem erro
php artisan test                   # suíte passa
npm run build                      # compila sem erro
```

> ⚠ **Até que os testes existam, `php artisan test` não é critério válido.** Enquanto a suíte
> estiver vazia, "verde" significa: `migrate:fresh --seed` roda limpo E o roteiro de conferência
> manual abaixo passa. Não reporte sucesso baseado num `php artisan test` que não testa nada.

### Roteiro de conferência manual (enquanto não há testes)

1. Cadastrar paciente, médico e clínica — os três entram e fazem login.
2. Médico pendente **não** aparece na busca. Admin aprova. Agora aparece.
3. Agendar consulta particular com médico; o horário some da lista de disponíveis.
4. Tentar agendar o mesmo horário de novo — precisa ser recusado.
5. Cancelar; o horário volta a aparecer.
6. Agendar pela clínica escolhendo só a especialidade; o sistema aloca um médico e mostra o nome
   antes de confirmar.
7. Marcar consulta como realizada; o paciente consegue avaliar. Consulta cancelada ou
   `nao_compareceu` **não** deixa avaliar.
8. Conferir que o comentário da avaliação não aparece em nenhuma tela pública.

**Publicação:** não há. O projeto roda localmente e é apresentado ao vivo na banca.

## 6. Regras de negócio / de dados (invioláveis)

Estas são as regras que, se quebradas, fazem o sistema mentir para quem usa. Toda regra abaixo
precisa estar refletida numa constraint de banco, numa Policy ou num FormRequest — não só na
intenção de quem escreveu a tela.

**Verificação e honestidade**

- **Nunca** exibir em busca, listagem ou perfil público um médico cujo `status_verificacao`
  não seja `verificado`.
- **Nunca** afirmar, em tela, e-mail ou texto, que um CRM foi "validado junto ao CFM". A
  verificação é **manual, feita por uma pessoa**, e a interface só pode dizer "CRM verificado
  pela equipe FacilMed". A API oficial do CFM é paga e exige CNPJ — não está integrada.
- **Nunca** afirmar que uma carteirinha foi "validada junto à operadora". Não existe integração
  TISS neste projeto e o COMPROVA da ANS exige login gov.br do próprio beneficiário — não há
  API nem consulta por terceiro. A carteirinha entra como `pendente` e vira `ativa` por
  conferência humana do comprovante que o paciente apresenta.
- **Nunca** criar registro em `convenios` sem `operadora_ans_id` apontando para uma operadora
  real importada da ANS. **Os planos são fictícios; as operadoras são reais.** Essa distinção
  precisa estar clara na tela e no texto do TCC.

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

## 7. Regras de edição

- **Plano antes de editar** (ver Protocolo de Handoff, §10).
- Commits pequenos e atômicos. Uma intenção por commit.
- **Branch por frente de trabalho. Ninguém commita direto na `main`.** Com 4+ pessoas no mesmo
  Laravel, isso não é preciosismo: é a diferença entre integrar e passar a última semana
  resolvendo conflito.
- Migration já aplicada por outra pessoa **não se edita** — cria-se uma nova. Editar uma
  migration que já rodou na máquina de outro integrante quebra o banco dele silenciosamente.
- Não introduzir dependência, serviço ou integração nova sem registrar a decisão no handoff (§3).
- Manter o "estado bom" (§5). Se quebrar algo de propósito, deixar explícito no handoff.
- Regra de negócio mora em Policy, FormRequest, Model ou constraint — **não** em `if` dentro
  de Blade. Tela não é lugar de regra.

### 7.1 Rito para mudanças com side-effect ⚠ (envio de e-mail)

O único side-effect deste projeto é o disparo de e-mail. Ele sai da máquina e chega na caixa
de alguém; o Git não desfaz isso.

1. Desenvolver com `MAIL_MAILER=log` no `.env`. O e-mail cai em `storage/logs/laravel.log`.
2. Para conferir o visual, usar `php artisan tinker` com Mailtrap ou o `mail:preview` — nunca
   disparando para endereço real.
3. Envio para caixa real **só com aprovação explícita**, e só para e-mail de integrante do grupo.
4. Depois de qualquer teste de envio, conferir `notificacoes_enviadas` e **limpar as linhas de
   teste**, senão o lembrete de verdade daquela consulta nunca sai (o UNIQUE bloqueia).
5. Registrar no `AI_HANDOFF.md` o que foi disparado.

## 8. Arquivos e áreas que NÃO devem ser alterados

- `.env` e qualquer arquivo com credencial real. **Nunca commitar segredo.** Mudança de
  configuração vai no `.env.example`, sem valor real.
- `database/migrations/` já aplicadas — criar nova migration em vez de editar (§7).
- `public/build/`, `vendor/`, `node_modules/` — artefatos gerados. Editar a fonte.
- `Descricao_Visual_FacilMed.pdf` — material de referência do design; não é gerado pelo código.
- A pasta do projeto antigo em PHP puro (`FacilMed/` original) — **somente leitura, referência
  histórica**. O projeto novo não depende dela e nada deve ser editado lá.

## 9. Restrições de segurança

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

## 10. Protocolo de Handoff (revezamento entre IAs)

**Antes de editar qualquer arquivo, numa sessão nova:**

1. Resumir o estado atual (`git log --oneline -10` e `git status`).
2. Ler `AI_HANDOFF.md` e este arquivo.
3. Listar os arquivos relevantes para a tarefa.
4. Apresentar um plano curto.
5. Apontar os riscos — especialmente §6 e §7.1.
6. **Aguardar aprovação.**

**Antes de encerrar a sessão ou trocar de ferramenta:**

1. Atualizar `AI_HANDOFF.md`: intenção, decisões, becos sem saída, próximo passo.
2. Checkpoint com **commit**:
   ```bash
   git add -A && git commit -m "wip: checkpoint before switching agent"
   ```
   Prefira commit a `git stash` — stash é local e invisível para o próximo agente.
3. Garantir que o "estado bom" (§5) ainda passa — ou registrar o que está quebrado e por quê.

> Regra de ouro: nunca troque de agente com working tree suja e handoff desatualizado.

## 11. Mapa de pastas

- **Raiz:** `AGENTS.md`, `CLAUDE.md`, `AI_HANDOFF.md`, `PERFIL.md`, `README.md`
- **`app/Models/`** — fonte viva. Models Eloquent e relacionamentos.
- **`app/Policies/`** — fonte viva. **Onde moram as regras de visibilidade da §6.**
- **`app/Http/Requests/`** — fonte viva. Validação de entrada.
- **`app/Mail/`** + **`app/Console/Commands/`** — fonte viva. E-mails e o comando do scheduler.
- **`database/migrations/`** — fonte viva e **fonte da verdade do schema**.
- **`database/seeders/`** — fonte viva. Dados fictícios de demonstração.
- **`database/data/`** — CSV da ANS. Dado externo, não editar à mão.
- **`resources/views/`** — fonte viva. Blade.
- **`docs/`** — material de referência: PDF de design, modelo de dados, documentação do TCC.
- **`public/build/`, `vendor/`, `node_modules/`** — **gerados**. Nunca editar.
