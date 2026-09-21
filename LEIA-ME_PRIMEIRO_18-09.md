# FacilMed — pacote de 18/09/2026

## Por onde começar

1. **`RELATORIO_COMPLETO.md`** — leia este primeiro. Explica o projeto inteiro, as decisões
   tomadas e o porquê de cada uma, o que falta, e o que cada pessoa do grupo faz.
2. **`PASSO_A_PASSO.md`** — como montar o projeto Laravel na sua máquina.
3. **`AGENTS.md`** — as regras que ninguém pode quebrar. Em caso de conflito entre
   documentos, este vence.

## O que tem aqui

Esta pasta **não é** um projeto Laravel pronto para rodar. São os arquivos que vão **dentro**
de um projeto Laravel, depois que você criar um com:

```bash
composer create-project laravel/laravel:^12.0 facilmed
```

A estrutura de pastas daqui espelha a do Laravel, então é só copiar por cima:

```
app/Models/            → 19 models Eloquent
app/Http/Controllers/  → 34 controllers
app/Http/Middleware/   → 2 middlewares
app/Console/Commands/  → importação das operadoras da ANS
database/migrations/   → 21 migrations
database/seeders/      → 8 seeders (clínicas, médicos e pacientes fictícios)
config/navegacao.php   → os menus de cada papel
routes/web.php         → todas as rotas
resources/views/components/sidebar.blade.php
public/imgs/           → as logos
```

## `_referencia/projeto-antigo/`

Alguns arquivos do protótipo em PHP puro, guardados como **referência histórica**. Não são
usados pelo projeto novo e não devem ser editados.

O protótipo completo está no repositório do grupo:
https://github.com/cilugo/FacilMed.git

## O que dá para fazer sem instalar nada

Se você está fora de casa e sem o ambiente montado:

- Ler o `RELATORIO_COMPLETO.md` inteiro
- Escrever a documentação do TCC a partir das seções 1, 2, 4 e 5 dele
- Revisar os comentários das migrations e dos models (cada decisão está explicada)
- Decidir os itens em aberto da seção 11
- Desenhar as telas que faltam, conforme `config/navegacao.php`
