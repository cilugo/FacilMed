# FacilMed — back-end, pacote de 19/09/2026

19 arquivos. Todos vão **dentro** de um projeto Laravel 12 já criado, mantendo os caminhos
deste zip. Nenhum deles foi executado ainda — o ambiente não estava montado quando foram
escritos.

## Instalação, na ordem

```bash
# 1. copiar os arquivos por cima do projeto (mantendo os caminhos)

# 2. registrar o seeder novo em database/seeders/DatabaseSeeder.php:
#    $this->call(FeriadoSeeder::class);

# 3. rodar
php artisan migrate
php artisan db:seed --class=FeriadoSeeder
```

**Atenção:** `app/Http/Controllers/Controller.php` **substitui** o que vem no Laravel limpo.
Os outros 18 são arquivos novos.

---

## O que tem aqui

### 1. Fundação de autorização — 8 arquivos

| Arquivo | O que faz |
|---|---|
| `app/Http/Controllers/Controller.php` | Adiciona `AuthorizesRequests` e `ValidatesRequests` |
| `app/Policies/ConsultaPolicy.php` | view, cancelar, atender, avaliar |
| `app/Policies/VinculoPolicy.php` | quem mexe no "médico atende aqui" |
| `app/Policies/LocalPolicy.php` | dados, horários e preços do endereço |
| `app/Policies/DisponibilidadePolicy.php` | a agenda é do médico |
| `app/Policies/BloqueioPolicy.php` | férias e ausências |
| `app/Policies/PacientePlanoPolicy.php` | carteirinha |
| `app/Policies/PacienteAcessibilidadePolicy.php` | a mais restritiva do sistema |

**O bug que isso conserta:** nove controllers chamam `$this->authorize(...)` — 14 chamadas.
Do Laravel 11 em diante o `Controller` base vem vazio, sem o trait. Sem isso, todas estouram
com `Call to undefined method` no primeiro clique.

Não precisa registrar Policy em lugar nenhum: o Laravel 12 descobre pela convenção de nome.

### 2. Validação dos cadastros — 6 arquivos

| Arquivo | O que faz |
|---|---|
| `app/Rules/SenhaPadrao.php` | **a política de senha, num lugar só** |
| `app/Rules/Cpf.php` | dígito verificador de verdade |
| `app/Rules/Cnpj.php` | idem, para clínica |
| `app/Http/Requests/CadastroPacienteRequest.php` | + acessibilidade opcional com consentimento |
| `app/Http/Requests/CadastroMedicoRequest.php` | médico autônomo |
| `app/Http/Requests/CadastroClinicaRequest.php` | empresa + primeira unidade |

Para ligar no controller, troca o tipo na assinatura:

```php
public function salvarPaciente(CadastroPacienteRequest $request)
{
    $dados = $request->validated();   // só o que passou na validação
}
```

### 3. Núcleo do agendamento — 5 arquivos

| Arquivo | O que faz |
|---|---|
| `app/Services/CalculadoraDeHorarios.php` | **o coração do sistema** |
| `config/agendamento.php` | antecedência e janela, em números |
| `app/Models/Feriado.php` | |
| `database/migrations/…_create_feriados_table.php` | tabela 22 + pivot `feriado_local` |
| `database/seeders/FeriadoSeeder.php` | nacionais calculados, não digitados |

---

## Decisões que viraram código

| Decisão | Quem decidiu | Onde está |
|---|---|---|
| Senha mínimo 8, sem teto | Sidney, 19/09 | `SenhaPadrao.php` |
| Antecedência mínima 24h | Sidney, 19/09 | `config/agendamento.php` |
| Janela de 180 dias | Sidney, 19/09 | `config/agendamento.php` |
| Feriado nacional automático | Sidney, 19/09 | `abrangencia = 'nacional'` |
| Municipal por opt-in do **local** | ajuste meu sobre a decisão dele | pivot `feriado_local` |
| Admin não lê dado de acessibilidade | eu, reversível | `PacienteAcessibilidadePolicy` |
| Médico e clínica também cancelam | eu, reversível | `ConsultaPolicy::cancelar` |

As três últimas são discutíveis e estão comentadas no código com o motivo.

---

## Três coisas para defender na banca

**A calculadora é chamada pela tela E pela gravação.** Era o bug do protótipo antigo:
`horariosdisponiveis.php` e `agendarconsulta.php` tinham cada um sua regra e divergiam — dava
para marcar 08:07 numa grade de 30 minutos editando o formulário.

**A calculadora NÃO protege contra duplo agendamento.** Entre ela dizer "livre" e o INSERT
acontecer, outra pessoa pode gravar. Quem protege é o índice único no banco. Saber a diferença
entre as duas camadas é o que separa "funciona" de "foi pensado".

**A busca de consultas é por médico, não por vínculo.** O médico é uma pessoa só: ocupado às
10h na clínica A é ocupado às 10h na clínica B.

---

## Como testar quando rodar

```bash
php artisan tinker
```

```php
// autorização
$p = App\Models\User::where('email','ana@facilmed.test')->first();
$c = App\Models\Consulta::first();
$p->can('view', $c);        // true se for dela
$p->can('atender', $c);     // false

// CPF
Validator::make(['cpf'=>'111.111.111-11'], ['cpf'=>[new App\Rules\Cpf]])->fails();  // true

// horários
$calc = new App\Services\CalculadoraDeHorarios;
$v = App\Models\Vinculo::where('ativo',true)->first();
$calc->paraData($v, now()->addDays(3));                       // lista cheia
$calc->paraData($v, Carbon\Carbon::parse('2026-12-25'));      // vazio (Natal)
$calc->paraData($v, now()->addDays(200));                     // vazio (fora da janela)
```

**Armadilha:** testar com `hoje` ou `amanhã de manhã` devolve lista vazia. Não quebrou — é a
antecedência de 24h. Testa com `addDays(3)`.

**O teste que mais importa:** cria uma consulta num horário da lista e roda de novo — ele tem
que sumir. Cancela e roda de novo — tem que voltar.

---

## O que ainda falta

- `AgendamentoController`: `horariosDisponiveis`, `confirmar`, `salvar`, `porEspecialidade`
- `App\Services\AlocadorDeMedico`
- Controllers de preço (médico e clínica), especialidades, unidades
- Factories + teste automatizado da calculadora
- Telas de feriado: admin cadastra, local escolhe quais segue
- As 40 views
