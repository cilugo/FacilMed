# FacilMed — pacote montado, 19/09/2026

Tudo que existe do projeto, num lugar só: os arquivos do grupo (18/09) **mais** o back-end
escrito depois, já encaixados nos caminhos certos do Laravel.

**130 arquivos.**

## Isto ainda não é um projeto Laravel que roda

Não tem `composer.json` nem `vendor/`. É a camada que vai **dentro** de um Laravel 12 recém
criado. O caminho é:

```bash
composer create-project laravel/laravel:^12.0 facilmed
cd facilmed
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install && npm run dev
# copiar app/, config/, database/, public/, resources/, routes/ deste pacote por cima
```

Depois, dois ajustes no projeto que o Laravel criou:

1. **`bootstrap/app.php`** — registrar os aliases dos middlewares:
   ```php
   $middleware->alias([
       'ativa' => \App\Http\Middleware\GarantirContaAtiva::class,
       'tipo'  => \App\Http\Middleware\GarantirTipoUsuario::class,
   ]);
   ```
2. **`.env`** — `DB_DATABASE=facilmed`, `DB_USERNAME=root`, `DB_PASSWORD=` (vazio)

Aí:

```bash
php artisan migrate
php artisan db:seed
```

Passo a passo completo do ambiente: `PASSO_A_PASSO.md`.

---

## O que é novo neste pacote (não estava no de 18/09)

| Caminho | O que é |
|---|---|
| `app/Http/Controllers/Controller.php` | **substitui** o do Laravel limpo — sem ele, 14 chamadas de `authorize()` estouram |
| `app/Policies/` (7 arquivos) | quem pode fazer o quê |
| `app/Rules/` (3 arquivos) | senha, CPF e CNPJ |
| `app/Http/Requests/` (3 arquivos) | validação dos três cadastros |
| `app/Services/CalculadoraDeHorarios.php` | o núcleo do agendamento |
| `app/Models/Feriado.php` | |
| `config/agendamento.php` | antecedência (24h) e janela (180 dias) |
| `database/migrations/…_create_feriados_table.php` | tabela 22 + pivot |
| `database/seeders/FeriadoSeeder.php` | nacionais calculados a partir da Páscoa |
| `public/imgs/marca/` | as 4 logos novas |

Também editei `database/seeders/DatabaseSeeder.php` para chamar o `FeriadoSeeder` na posição
certa — antes do `ConsultaSeeder`, para as consultas de demonstração não caírem em feriado.

**Nada do que o grupo entregou em 18/09 foi alterado.** Conferi: `routes/web.php`,
`AgendamentoController`, `MedicoSeeder` e `config/navegacao.php` estão byte a byte iguais.

O detalhe do back-end está em `LEIA-ME_BACKEND.md`.

---

## Sobre as logos

As 4 novas estão em `public/imgs/marca/`. As antigas continuam em `public/imgs/`.

**Paleta real, extraída dos arquivos:** `#183E9F` (marinho), `#1C9CE5` (azul-claro),
`#4A9BDA` (o coração). O `RELATORIO_COMPLETO.md` manda usar `#1e3a8a`/`#3b82f6` — parecido,
mas não é o mesmo. Vale corrigir lá.

**Os 4 PNGs são RGB sem transparência.** Na sidebar marinho vai aparecer um retângulo branco
em volta da logo. Precisa reexportar com canal alfa, ou em SVG.

---

## Documentação que veio junto

| Arquivo | O que é |
|---|---|
| `AGENTS.md` | fonte canônica — vence em caso de conflito |
| `RELATORIO_COMPLETO.md` | o projeto inteiro e o porquê de cada decisão |
| `AI_HANDOFF.md` | estado e próximo passo |
| `PERFIL.md` | negócio, público, tom |
| `PASSO_A_PASSO.md` | montar o ambiente |
| `COMO_USAR_ESTES_ARQUIVOS.md` | onde cada arquivo vai |
| `_referencia/projeto-antigo/` | protótipo em PHP puro — **somente leitura** |

O `AI_HANDOFF.md` está desatualizado: ele diz que seeders, controllers e rotas não foram
escritos, mas eles existem neste pacote. Vale corrigir antes de passar pra próxima pessoa.

---

## O que falta (em ordem de quem destrava quem)

1. **Ambiente montado e `migrate` rodando** — nada avança de verdade antes disso
2. `AgendamentoController`: `horariosDisponiveis`, `confirmar`, `salvar`, `porEspecialidade`
3. `App\Services\AlocadorDeMedico`
4. Controllers de preço, especialidades, unidades (25 métodos vazios no total)
5. Factories + teste automatizado da `CalculadoraDeHorarios`
6. Telas de feriado: admin cadastra, local escolhe quais segue
7. **As 40 views** — só a sidebar existe, e ela depende de um `<x-icone>` que não foi feito
8. E-mails e scheduler
