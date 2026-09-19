# Como montar o projeto Laravel do FacilMed

> Entrega de 18/09/2026. Contém a **camada de dados completa**: 21 migrations e 19 models.
> Controllers, Requests, Policies, views e seeders vêm nas próximas entregas.

## 1. Criar o projeto (uma pessoa faz, uma vez)

```bash
cd C:\Users\MuriloMartinsSouza\Downloads\FacilMed
composer create-project laravel/laravel facilmed
cd facilmed
php artisan --version
```

**Anote a versão que apareceu e escreva no `AGENTS.md` §3.** Está marcada como `A DEFINIR`
exatamente para isso — nenhum agente de IA deve supor a versão.

## 2. Instalar autenticação e Tailwind

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install
```

O Breeze já traz Tailwind configurado e as telas de login, registro e recuperação de senha.
Não escreva autenticação à mão — o login do protótipo antigo tinha três furos de segurança
(sem regeneração de sessão, sem limite de tentativas, validação só no JavaScript) que o
Breeze não tem.

## 3. Copiar os arquivos desta pasta

```
migrations-laravel/*.php   →   facilmed/database/migrations/
laravel-app/Models/*.php   →   facilmed/app/Models/
```

O `User.php` **substitui** o que o Laravel criou. Os outros 18 são novos.

Apague a migration `..._create_users_table.php`? **Não.** Ela cria a tabela; a nossa
`000100_alter_users_table_facilmed.php` só acrescenta as colunas do FacilMed. As duas
convivem, nessa ordem.

## 4. Banco

Crie o banco vazio no phpMyAdmin do XAMPP (`facilmed`, utf8mb4) e ajuste o `.env`:

```
DB_DATABASE=facilmed
DB_USERNAME=root
DB_PASSWORD=
```

E, enquanto estiver desenvolvendo:

```
MAIL_MAILER=log
```

Assim o e-mail cai em `storage/logs/laravel.log` em vez de sair de verdade.

```bash
php artisan migrate
```

## 5. Conferir que funcionou

```bash
php artisan migrate:status     # 21 linhas + as do Laravel, todas "Ran"
php artisan tinker
>>> \App\Models\Medico::count()   // deve responder 0, sem erro
```

Se `migrate` falhar no `locais`, sua versão do MySQL é anterior à 8.0.16 e não suporta
`CHECK`. Nesse caso, comente o `DB::statement` daquela migration e garanta a regra "um dono
por local" na validação — anotando isso no `AI_HANDOFF.md`.

---

## O que estes arquivos resolvem

| Problema do banco antigo | Como ficou |
|---|---|
| Médico sem vínculo com local | Tabela `vinculos` — medico + local |
| Médico sem vínculo com convênio | Tabela `convenio_medico` |
| Uma especialidade por médico | `medico_especialidade` (N:N) |
| Preço sem lugar para morar | `precos` (vínculo + especialidade) |
| Disponibilidade sem saber o endereço | `disponibilidades.vinculo_id` |
| Sem férias nem bloqueio | Tabela `bloqueios` |
| Sem avaliações | `avaliacoes`, com comentário privado |
| Sem clínica | `clinicas` + tipo de usuário |
| Convênio digitado à mão | `operadoras_ans`, importada da ANS |
| Paciente sem carteirinha | `paciente_planos` |
| Sem acessibilidade | `paciente_acessibilidade`, em tabela separada |
| E-mail podendo sair duplicado | `notificacoes_enviadas` com UNIQUE |
| Falta poder marcar ausência | `status = 'nao_compareceu'` |
| Bloqueio de conta sem rastro | `motivo_bloqueio`, `bloqueado_por`, `bloqueado_em` |

## O que foi transplantado do banco antigo

A coluna virtual `horario_ativo` com `UNIQUE (medico_id, data_consulta, horario_ativo)`,
na migration de `consultas`. É o que impede duas pessoas marcarem o mesmo horário quando as
duas requisições chegam juntas — um `SELECT` de checagem antes do `INSERT` deixa as duas
passarem. Quem escreveu isso no projeto antigo acertou; não remova.

## Onde as regras difíceis vão morar

Três coisas **não** estão nas migrations porque são autorização, não estrutura. Elas
precisam virar Policies:

1. **Médico não verificado não aparece na busca** — já existe o scope `Medico::visivel()`.
   Use em toda consulta que alimenta tela de paciente.
2. **Comentário de avaliação é privado** — o campo está em `$hidden`, mas quem decide quem
   lê é a `AvaliacaoPolicy`.
3. **Acessibilidade só para o profissional da consulta** — `PacienteAcessibilidadePolicy`,
   verificando se existe consulta entre aquele paciente e aquele médico.

## Próxima entrega

Seeders com as clínicas fictícias, comando de importação da ANS, Policies, FormRequests,
controllers e views. Para os seeders eu preciso saber quais clínicas fictícias vão existir,
com nome, cidade e tabela de preços — está pendente no `PERFIL.md` §13.
