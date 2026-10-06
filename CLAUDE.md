# CLAUDE.md — PointMed (ex-FacilMed)

As regras deste projeto estão no **[`AGENTS.md`](./AGENTS.md)** e a documentação completa no
**[`README.md`](./README.md)**. Leia os dois antes de qualquer coisa — este arquivo só reforça
pontos do Claude.

- Comece pelo protocolo do AGENTS.md §6: `git log`, `git status`, README §6, plano, **aguarde aprovação**.
- O projeto roda **na máquina dos alunos**, em XAMPP. Comandos `php artisan` e `composer`
  precisam rodar lá, com o MySQL do XAMPP ligado.
- São 4+ pessoas no mesmo repositório. Antes de criar migration, confira se já não existe uma
  para o mesmo assunto.
- O grupo é iniciante em Laravel: **explique a escolha** quando usar recurso menos óbvio
  (Policy, `DB::afterCommit`, coluna virtual, relacionamento com pivot). Código que ninguém do
  grupo sabe defender na banca é passivo.
- Não crie arquivos .md novos na raiz: atualize a seção certa do README.
- Antes de afirmar que falta uma ferramenta/conector, confira (`/mcp`).
