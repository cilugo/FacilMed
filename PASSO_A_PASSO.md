# Passo a passo — montar o FacilMed em Laravel

> Faça isto uma vez, numa máquina. Depois todo mundo clona do Git.
> Tempo estimado: **1 a 2 horas**, quase tudo esperando download.

## Antes de começar

### Se o seu XAMPP for antigo (leia isto primeiro)

O XAMPP que estava na máquina do grupo trazia **PHP 8.0.7, de 2021** — não
serve para Laravel nenhum atual. Confira o seu:

```cmd
C:\xampp\php\php.exe -v
```

Se der menos de 8.2, baixe o **XAMPP 8.2.12 (PHP 8.2.12)** em
https://www.apachefriends.org e **instale numa pasta separada**, tipo
`C:\xampp82` — não por cima do antigo, para não perder os bancos nem o
projeto em PHP puro. Depois aponte o `Path` do Windows para
`C:\xampp82\php`, feche o terminal e abra outro.

Essa é a versão mais nova que o XAMPP tem (ele parou em novembro de 2023),
e é por isso que usamos Laravel 12 e não 13.

### Conferência

Abra o XAMPP e ligue o **MySQL** (o Apache não é necessário — o
`php artisan serve` levanta o próprio servidor). No terminal:

```bash
php -v          # precisa ser 8.2.x
composer -V
node -v
git --version
```

Se `composer` ou `node` não responderem, instale antes de seguir —
o resto não funciona sem eles.

> Dois XAMPP instalados brigam pela porta 3306: ligue o MySQL de **um**
> painel por vez.

---

## 1. Criar o projeto

```bash
cd C:\Users\MuriloMartinsSouza\Downloads\FacilMed
composer create-project laravel/laravel:^12.0 facilmed
cd facilmed
php artisan --version
```

> **Por que `^12.0` e não a mais nova?** O Laravel 13 exige PHP 8.3, e o
> XAMPP parou no PHP 8.2.12 — não existe XAMPP com 8.3. O Laravel 12 roda
> em 8.2 e tem suporte de segurança até fevereiro de 2027, bem depois da
> entrega. **Se rodar `composer create-project laravel/laravel` sem o
> `:^12.0`, ele baixa o 13 e falha** com erro de versão de PHP.

**Anote a versão que apareceu** e escreva no `AGENTS.md` §3.

## 2. Autenticação e Tailwind

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install
```

O Breeze traz Tailwind, Alpine.js e as telas de login, registro e
recuperação de senha prontas.

**Não escreva autenticação à mão.** O login do protótipo antigo tinha
três furos que o Breeze não tem: sem regeneração de ID de sessão, sem
limite de tentativas, e validação existindo só no JavaScript.

## 3. Copiar os arquivos que já estão prontos

De `_novo_laravel/` para dentro de `facilmed/`:

| De | Para |
|---|---|
| `database/migrations/*.php` | `database/migrations/` |
| `app/Models/*.php` | `app/Models/` |
| `database/seeders/*.php` | `database/seeders/` |
| `app/Console/Commands/*.php` | `app/Console/Commands/` |
| `app/Http/Middleware/*.php` | `app/Http/Middleware/` |
| `config/navegacao.php` | `config/` |
| `resources/views/components/sidebar.blade.php` | `resources/views/components/` |

`User.php` e `DatabaseSeeder.php` **substituem** os que o Laravel criou.
Os outros são novos.

Copie também as logos do projeto antigo:

```
FacilMed-main/imgs/*.png  →  facilmed/public/imgs/
```

## 4. Registrar os middlewares

Em `bootstrap/app.php`, dentro de `->withMiddleware(...)`:

```php
$middleware->alias([
    'tipo'  => \App\Http\Middleware\GarantirTipoUsuario::class,
    'ativa' => \App\Http\Middleware\GarantirContaAtiva::class,
]);

// Toda rota autenticada passa pela checagem de conta bloqueada.
$middleware->appendToGroup('web', \App\Http\Middleware\GarantirContaAtiva::class);
```

Uso nas rotas:

```php
Route::middleware(['auth', 'tipo:medico'])->group(function () { ... });
Route::middleware(['auth', 'tipo:clinica,admin'])->group(function () { ... });
```

> `tipo:` responde **"que tipo de usuário é"**. Não responde **"é dono
> disto"** — essa é a Policy. Faltar a segunda é o furo clássico: o
> médico A abrindo a agenda do médico B.

## 5. Banco de dados

No phpMyAdmin (`http://localhost/phpmyadmin`), crie um banco vazio
chamado `facilmed` com cotejamento `utf8mb4_unicode_ci`.

No `.env`:

```
DB_DATABASE=facilmed
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=log
```

`MAIL_MAILER=log` faz o e-mail cair em `storage/logs/laravel.log` em vez
de sair de verdade. Mantenha assim durante todo o desenvolvimento.

```bash
php artisan migrate
```

## 6. Operadoras da ANS

Baixe o CSV de **"Operadoras de planos de saúde ativas"** no portal de
dados abertos do governo (dados.gov.br, busque por *operadoras ativas
ANS*) e salve em:

```
facilmed/database/data/operadoras_ans.csv
```

```bash
php artisan facilmed:importar-operadoras --limite=200
```

O `--limite` existe porque a lista completa tem centenas de operadoras e
vocês só precisam de algumas para a demonstração.

> **Se não conseguir baixar agora, siga assim mesmo.** O sistema inteiro
> funciona sem isso — só o agendamento por convênio fica sem dado, e o
> seeder avisa na tela em vez de quebrar.

## 7. Popular o banco

```bash
php artisan db:seed
```

Cria: 1 admin, 3 clínicas fictícias com 4 unidades, 6 médicos, 4
pacientes, e consultas de demonstração no passado e no futuro.

**Senha de todos os usuários de teste: `facilmed2026`**

| Papel | E-mail |
|---|---|
| Admin | `admin@facilmed.test` |
| Clínica | `contato@vidaplena.test` |
| Médico | `helena@facilmed.test` |
| Médico pendente | `andre@facilmed.test` |
| Paciente | `ana@facilmed.test` |

## 8. Rodar

Dois terminais abertos:

```bash
php artisan serve      # terminal 1
npm run dev            # terminal 2
```

Abra `http://localhost:8000`.

## 9. Git — antes de qualquer pessoa começar a codar

```bash
cd facilmed
git init
git add .
git commit -m "chore: projeto Laravel com modelo de dados do FacilMed"
git branch -M main
git remote add origin https://github.com/cilugo/FacilMed.git
git push -u origin main
```

> Se o repositório já tem o projeto antigo em PHP puro, **não sobrescreva
> o `main`**. Suba numa branch: `git checkout -b laravel` e
> `git push -u origin laravel`. O código antigo continua lá como
> histórico, que é o que o `AGENTS.md` §8 manda.

Depois disso, cada pessoa clona e trabalha na **branch da frente dela**.
Ninguém commita direto na branch principal.

---

## Conferir que deu certo

```bash
php artisan migrate:status    # todas "Ran"
php artisan tinker
>>> \App\Models\Medico::visivel()->count()   // 5 (o pendente não conta)
>>> \App\Models\Medico::count()              // 6
>>> \App\Models\Consulta::count()            // por volta de 30
```

Se `Medico::visivel()` devolver 6, a regra "médico não verificado não
aparece na busca" não está funcionando — e essa é uma das regras
invioláveis do `AGENTS.md` §6.

## Se algo falhar

**`migrate` falha em `locais`** — seu MySQL é anterior à 8.0.16 e não
aceita `CHECK`. Comente o `DB::statement` no fim daquela migration, rode
de novo, e garanta a regra "um dono por local" na validação. Anote no
`AI_HANDOFF.md` que foi feito assim.

**`migrate` falha em `consultas`** — a coluna virtual usa sintaxe do
MySQL 5.7+. Se o XAMPP estiver com MariaDB, troque `VIRTUAL` por
`PERSISTENT`. **Não remova o índice único** — ele é a única coisa que
impede duas pessoas marcarem o mesmo horário.

**`db:seed` reclama de operadora** — é esperado se você pulou o passo 6.
Siga normalmente.

**`npm run dev` falha** — apague `node_modules` e rode `npm install` de
novo.

---

## O que ainda falta depois disto

Rotas, controllers, FormRequests, Policies, views Blade, os e-mails e o
scheduler. Com o passo a passo acima concluído, essas frentes podem ser
tocadas **em paralelo** — é exatamente o que destrava o Cipriano e o
Sidney ao mesmo tempo.
