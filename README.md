# FacilMed

Sistema web de agendamento de consultas médicas. Projeto acadêmico, feito em
PHP + MySQL, sem framework.

Três perfis de usuário: **paciente** (agenda e cancela consultas),
**médico** (define horários de atendimento e gerencia a agenda) e
**admin** (aprova médicos e mantém especialidades, locais, convênios e planos).

---

## Como rodar (XAMPP)

1. Copie a pasta `FacilMed` para dentro de `htdocs/` do XAMPP.
2. Ligue **Apache** e **MySQL** no painel do XAMPP.
3. Abra o phpMyAdmin (`http://localhost/phpmyadmin`) e importe o arquivo
   `banco/bancofacilmed.sql`. Ele cria o banco `facilmed`, todas as tabelas e
   os dados de exemplo.
4. Acesse `http://localhost/FacilMed/paginas/home/home/home.html`.

Não precisa configurar mais nada: por padrão o sistema usa a conexão típica do
XAMPP (`localhost`, usuário `root`, sem senha).

### Já tem o banco de uma versão anterior?

Se o banco `facilmed` já existia antes, não importe o arquivo inteiro — ele
recriaria tudo. Rode só as linhas da seção **MIGRAÇÃO** no final do
`bancofacilmed.sql` (estão comentadas com `--`; tire o comentário das que
ainda não aplicou).

---

## Contas de teste

Criadas pelo próprio `bancofacilmed.sql`:

| Perfil   | E-mail                    | Senha       |
|----------|---------------------------|-------------|
| Admin    | `admin@facilmed.com`      | `admin123`  |
| Paciente | `marcelo@gmail.com`       | `alca12`    |
| Médica   | `ana.cardio@facilmed.com` | `medico123` |

⚠️ São contas de desenvolvimento. Apague ou troque as senhas antes de colocar
o sistema em qualquer lugar público.

---

## Configuração opcional (`.env`)

Copie `.env.example` para `.env` na raiz do projeto. O `.env` **não** vai para
o Git (está no `.gitignore`).

- **Banco de dados** — só preencha `DB_HOST`, `DB_USER`, `DB_PASS` e `DB_NAME`
  se for publicar fora do XAMPP.
- **SMTP** — se preencher, a recuperação de senha envia o código por e-mail de
  verdade. Se deixar em branco, o sistema entra em modo de desenvolvimento e
  mostra o código na própria tela, para dar pra testar sem servidor de e-mail.

---

## Estrutura das pastas

```
banco/      script SQL do banco (schema + dados de exemplo)
css/        folhas de estilo
imgs/       imagens
js/         JavaScript das telas (calendário, máscaras, validações)
paginas/    telas do paciente, do admin e as páginas públicas
  admin/    CRUD de especialidades, locais, convênios e planos
  home/     página inicial do site
php/        ações e regras de negócio (login, cadastros, agendamento...)
  lib/      código compartilhado (sessão, helpers, envio de e-mail)
  medico/   painel do médico
```

## Arquivos que valem conhecer

| Arquivo | Para que serve |
|---|---|
| `php/conexao.php` | conexão com o banco |
| `php/verificarsessao.php` | exige usuário logado; `exigirPerfil()` e `exigirCsrf()` |
| `php/lib/sessao.php` | abre a sessão com cookie protegido e tempo de inatividade |
| `php/lib/helpers.php` | `horariosLivres()`, validação de cadastro, caminhos de redirect |
| `php/agendarconsulta.php` | grava a consulta (valida data, horário, médico e valor) |
| `php/statusconsulta.php` | cancelar consulta / marcar como realizada |
| `php/statusmedico.php` | admin aprova ou suspende médico |

---

## Fluxo do sistema

1. O paciente se cadastra em `paginas/cadastropaciente.html`.
2. O médico se cadastra em `paginas/cadastromedico.php` e fica **pendente**.
3. O **admin** aprova o médico em `paginas/listarmedicos.php`.
   *Enquanto estiver pendente, o médico não aparece para agendamento.*
4. O médico cadastra o horário de trabalho em `php/medico/disponibilidade.php`.
5. O paciente escolhe médico, dia e horário em `paginas/agendamento.php`.
   Os horários livres são calculados a partir da disponibilidade do médico,
   menos o que já está ocupado.
6. O paciente acompanha e cancela em `paginas/historico.php`; o médico marca
   como realizada em `php/medico/agenda.php`.
