<?php

use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\BuscaController;
use App\Http\Controllers\CadastroController;
use App\Http\Controllers\Clinica;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Medico;
use App\Http\Controllers\Paciente;
use App\Http\Controllers\PerfilPublicoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas do FacilMed
|--------------------------------------------------------------------------
|
| Os NOMES das rotas aqui são contrato com config/navegacao.php. Se você
| renomear uma rota, o item some da sidebar silenciosamente (ela usa
| Route::has() para não quebrar a página). Renomeou? Ajuste os dois.
|
| Dois middlewares em cima de 'auth':
|   'ativa'  — conta bloqueada não entra (já está no grupo 'web')
|   'tipo:x' — só aquele tipo de usuário acessa
|
| ATENÇÃO: 'tipo' responde "que tipo de usuário é", NÃO "é dono disto".
| A segunda pergunta é da Policy, dentro do controller. Rota sem as duas
| é o furo clássico: o médico A abrindo a agenda do médico B.
|
*/

// ---------------------------------------------------------------------
// Público
// ---------------------------------------------------------------------

Route::get('/', [HomeController::class, 'index'])->name('home');

// Busca de médicos e clínicas. Só mostra médico verificado — o
// controller usa o scope Medico::visivel().
Route::get('/buscar', [BuscaController::class, 'index'])->name('busca.index');

// Perfis públicos
// whereNumber: sem ele, /medico/{medico} captura /medico/agenda, /medico/perfil etc.
// (declarado antes do grupo do painel) e todas as telas do médico e da clínica davam 404.
Route::get('/medico/{medico}', [PerfilPublicoController::class, 'medico'])
    ->whereNumber('medico')->name('publico.medico');
Route::get('/clinica/{clinica}', [PerfilPublicoController::class, 'clinica'])
    ->whereNumber('clinica')->name('publico.clinica');

// Cadastro de paciente e de clínica/hospital. O Breeze cuida de login, logout e senha.
// 29/09/2026: o médico não se cadastra mais sozinho - entra pela clínica
// (Meus médicos → Cadastrar médico), que confere o CRM na base simulada.
Route::middleware('guest')->group(function () {
    Route::get('/cadastro', [CadastroController::class, 'escolher'])->name('cadastro.escolher');

    Route::get('/cadastro/paciente', [CadastroController::class, 'formPaciente'])->name('cadastro.paciente');
    Route::post('/cadastro/paciente', [CadastroController::class, 'salvarPaciente']);

    Route::get('/cadastro/clinica', [CadastroController::class, 'formClinica'])->name('cadastro.clinica');
    Route::post('/cadastro/clinica', [CadastroController::class, 'salvarClinica']);
});

// Endereço antigo do cadastro de médico (link salvo, protótipo): volta para a escolha.
Route::redirect('/cadastro/medico', '/cadastro');

// ---------------------------------------------------------------------
// Agendamento (paciente logado)
// ---------------------------------------------------------------------

Route::middleware(['auth', 'tipo:paciente'])->group(function () {

    // Passo 1: escolher médico OU clínica + especialidade
    Route::get('/agendar/{vinculo}', [AgendamentoController::class, 'escolherHorario'])
        ->name('agendamento.horario');

    // Alocação pela clínica: o sistema escolhe o médico disponível e
    // MOSTRA O NOME antes da confirmação (decisão de 18/09/2026).
    Route::get('/agendar/clinica/{clinica}/{especialidade}', [AgendamentoController::class, 'porEspecialidade'])
        ->name('agendamento.especialidade');

    // Endpoint que a tela consulta ao trocar de dia.
    // Horário livre = disponibilidade − consultas − bloqueios.
    Route::get('/agendar/{vinculo}/horarios', [AgendamentoController::class, 'horariosDisponiveis'])
        ->name('agendamento.horarios-disponiveis');

    Route::get('/agendar/{vinculo}/confirmar', [AgendamentoController::class, 'confirmar'])
        ->name('agendamento.confirmar');

    // AVISO OBRIGATÓRIO na tela de confirmação por convênio:
    // "Confirme na recepção se o seu plano é aceito neste endereço."
    // (AGENTS.md §6 — consequência de o convênio ser vinculado ao
    // médico e não ao endereço.)
    Route::post('/agendar', [AgendamentoController::class, 'salvar'])->name('agendamento.salvar');
});

// ---------------------------------------------------------------------
// Área do paciente
// ---------------------------------------------------------------------

Route::middleware(['auth', 'tipo:paciente'])->prefix('paciente')->name('paciente.')->group(function () {

    Route::get('/', [Paciente\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/consultas', [Paciente\ConsultaController::class, 'index'])->name('consultas');
    Route::get('/consultas/{consulta}', [Paciente\ConsultaController::class, 'show'])->name('consultas.show');

    // Cancelar é SEMPRE permitido. Abaixo de 24h vira cancelamento
    // tardio e fica registrado — bloquear não faz a pessoa comparecer,
    // faz ela faltar, e falta perde o horário.
    Route::post('/consultas/{consulta}/cancelar', [Paciente\ConsultaController::class, 'cancelar'])
        ->name('consultas.cancelar');

    Route::get('/consultas/{consulta}/remarcar', [Paciente\ConsultaController::class, 'formRemarcar'])
        ->name('consultas.remarcar');
    Route::post('/consultas/{consulta}/remarcar', [Paciente\ConsultaController::class, 'remarcar']);

    // Só consulta com status 'realizada' pode ser avaliada. O comentário
    // é privado: paciente e público veem só a nota em estrelas.
    Route::get('/consultas/{consulta}/avaliar', [Paciente\AvaliacaoController::class, 'form'])
        ->name('consultas.avaliar');
    Route::post('/consultas/{consulta}/avaliar', [Paciente\AvaliacaoController::class, 'salvar']);

    // Carteirinha. Conferida NA HORA na base simulada (base_carteirinhas,
    // decisão de 24/09): bateu -> 'ativa'; não bateu -> erro com o motivo.
    Route::get('/planos', [Paciente\PlanoController::class, 'index'])->name('planos');
    Route::post('/planos', [Paciente\PlanoController::class, 'salvar'])->name('planos.salvar');
    Route::delete('/planos/{pacientePlano}', [Paciente\PlanoController::class, 'remover'])->name('planos.remover');

    Route::get('/perfil', [Paciente\PerfilController::class, 'edit'])->name('perfil');
    Route::put('/perfil', [Paciente\PerfilController::class, 'update'])->name('perfil.atualizar');

    // DADO SENSÍVEL DE SAÚDE (LGPD art. 11). Opcional, com consentimento
    // explícito. Sem upload de arquivo — só texto.
    Route::put('/perfil/acessibilidade', [Paciente\PerfilController::class, 'salvarAcessibilidade'])
        ->name('perfil.acessibilidade');
});

// ---------------------------------------------------------------------
// Área do médico
// ---------------------------------------------------------------------

Route::middleware(['auth', 'tipo:medico'])->prefix('medico')->name('medico.')->group(function () {

    Route::get('/', [Medico\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/agenda', [Medico\AgendaController::class, 'index'])->name('agenda');

    // Números das consultas do PRÓPRIO médico (7/30/90 dias). Mesma tela
    // da clínica; o serviço já nasce preso ao médico logado.
    Route::get('/consultas', [Medico\ConsultaController::class, 'index'])->name('consultas');

    Route::post('/agenda/{consulta}/realizada', [Medico\AgendaController::class, 'marcarRealizada'])
        ->name('agenda.realizada');
    // Sem este status, quem faltou consegue avaliar e as métricas erram.
    Route::post('/agenda/{consulta}/falta', [Medico\AgendaController::class, 'marcarFalta'])
        ->name('agenda.falta');
    Route::post('/agenda/{consulta}/cancelar', [Medico\AgendaController::class, 'cancelar'])
        ->name('agenda.cancelar');

    // Blocos recorrentes por vínculo. Almoço = dois blocos no mesmo dia.
    Route::get('/horarios', [Medico\DisponibilidadeController::class, 'index'])->name('disponibilidade');
    Route::post('/horarios', [Medico\DisponibilidadeController::class, 'salvar'])->name('disponibilidade.salvar');
    Route::delete('/horarios/{disponibilidade}', [Medico\DisponibilidadeController::class, 'remover'])
        ->name('disponibilidade.remover');

    Route::get('/ausencias', [Medico\BloqueioController::class, 'index'])->name('bloqueios');
    Route::post('/ausencias', [Medico\BloqueioController::class, 'salvar'])->name('bloqueios.salvar');
    Route::delete('/ausencias/{bloqueio}', [Medico\BloqueioController::class, 'remover'])->name('bloqueios.remover');

    // Vínculos: onde o médico atende. Médico autônomo cria consultório
    // próprio aqui; em clínica, quem vincula é a clínica.
    Route::get('/locais', [Medico\LocalController::class, 'index'])->name('locais');
    Route::post('/locais', [Medico\LocalController::class, 'salvar'])->name('locais.salvar');

    // Só edita preço de local que é consultório próprio dele.
    // Em clínica, quem define é a clínica. Regra na Policy.
    Route::get('/precos', [Medico\PrecoController::class, 'index'])->name('precos');
    Route::post('/precos', [Medico\PrecoController::class, 'salvar'])->name('precos.salvar');

    // Aqui o médico VÊ o comentário — é a única tela onde ele aparece,
    // junto da área da clínica e do admin.
    Route::get('/avaliacoes', [Medico\AvaliacaoController::class, 'index'])->name('avaliacoes');

    Route::get('/perfil', [Medico\PerfilController::class, 'edit'])->name('perfil');
    Route::put('/perfil', [Medico\PerfilController::class, 'update'])->name('perfil.atualizar');
    Route::put('/perfil/especialidades', [Medico\PerfilController::class, 'salvarEspecialidades'])
        ->name('perfil.especialidades');
    Route::put('/perfil/convenios', [Medico\PerfilController::class, 'salvarConvenios'])
        ->name('perfil.convenios');
});

// ---------------------------------------------------------------------
// Área da clínica
// ---------------------------------------------------------------------

Route::middleware(['auth', 'tipo:clinica'])->prefix('clinica')->name('clinica.')->group(function () {

    Route::get('/', [Clinica\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/agenda', [Clinica\AgendaController::class, 'index'])->name('agenda');

    // Números das consultas de TODAS as unidades da clínica (7/30/90 dias).
    Route::get('/consultas', [Clinica\ConsultaController::class, 'index'])->name('consultas');

    // Ao cadastrar médico, o sistema gera senha temporária e força a
    // troca no primeiro login — a clínica nunca sabe a senha final.
    Route::get('/medicos', [Clinica\MedicoController::class, 'index'])->name('medicos');
    Route::get('/medicos/novo', [Clinica\MedicoController::class, 'form'])->name('medicos.novo');
    Route::post('/medicos', [Clinica\MedicoController::class, 'salvar'])->name('medicos.salvar');
    Route::delete('/medicos/{vinculo}', [Clinica\MedicoController::class, 'desvincular'])
        ->name('medicos.desvincular');

    Route::get('/unidades', [Clinica\UnidadeController::class, 'index'])->name('unidades');
    Route::post('/unidades', [Clinica\UnidadeController::class, 'salvar'])->name('unidades.salvar');
    Route::put('/unidades/{local}/horarios', [Clinica\UnidadeController::class, 'salvarHorarios'])
        ->name('unidades.horarios');

    // Preço por vínculo + especialidade: o mesmo médico pode ter
    // valores diferentes em cada unidade e em cada especialidade.
    Route::get('/precos', [Clinica\PrecoController::class, 'index'])->name('precos');
    Route::post('/precos', [Clinica\PrecoController::class, 'salvar'])->name('precos.salvar');

    // Cobertura de convênios da clínica. O convênio é aceito pelo MÉDICO;
    // aqui a clínica só liga/desliga "aceita convênio" por médico e unidade.
    Route::get('/convenios', [Clinica\ConvenioController::class, 'index'])->name('convenios');
    Route::post('/convenios', [Clinica\ConvenioController::class, 'salvar'])->name('convenios.salvar');

    Route::get('/avaliacoes', [Clinica\AvaliacaoController::class, 'index'])->name('avaliacoes');

    Route::get('/perfil', [Clinica\PerfilController::class, 'edit'])->name('perfil');
    Route::put('/perfil', [Clinica\PerfilController::class, 'update'])->name('perfil.atualizar');
});

// ---------------------------------------------------------------------
// Administração
// ---------------------------------------------------------------------

Route::middleware(['auth', 'tipo:admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/usuarios', [Admin\UsuarioController::class, 'index'])->name('usuarios');
    // Bloqueio exige motivo — bloqueio sem registro de quem e por quê
    // é ingovernável.
    Route::post('/usuarios/{user}/bloquear', [Admin\UsuarioController::class, 'bloquear'])
        ->name('usuarios.bloquear');
    Route::post('/usuarios/{user}/desbloquear', [Admin\UsuarioController::class, 'desbloquear'])
        ->name('usuarios.desbloquear');

    // Verificação de CRM. Desde 24/09 o CRM é conferido NA HORA DO CADASTRO
    // na base simulada (base_crms); esta tela vira histórico + "rejeitar"
    // (tirar da plataforma). Texto da tela: "conferido na base simulada do
    // FacilMed" — nunca "validado junto ao CFM".
    Route::get('/verificacoes', [Admin\VerificacaoController::class, 'index'])->name('verificacoes');
    Route::post('/verificacoes/{medico}/aprovar', [Admin\VerificacaoController::class, 'aprovar'])
        ->name('verificacoes.aprovar');
    Route::post('/verificacoes/{medico}/rejeitar', [Admin\VerificacaoController::class, 'rejeitar'])
        ->name('verificacoes.rejeitar');

    // Carteirinhas. Desde 24/09 são conferidas NA HORA na base simulada
    // (base_carteirinhas); a tela vira consulta. Aprovar/recusar só para
    // alguma que tenha ficado pendente.
    Route::get('/carteirinhas', [Admin\CarteirinhaController::class, 'index'])->name('carteirinhas');
    Route::post('/carteirinhas/{pacientePlano}/aprovar', [Admin\CarteirinhaController::class, 'aprovar'])
        ->name('carteirinhas.aprovar');
    Route::post('/carteirinhas/{pacientePlano}/recusar', [Admin\CarteirinhaController::class, 'recusar'])
        ->name('carteirinhas.recusar');

    Route::get('/clinicas', [Admin\ClinicaController::class, 'index'])->name('clinicas');

    Route::get('/especialidades', [Admin\EspecialidadeController::class, 'index'])->name('especialidades');
    Route::post('/especialidades', [Admin\EspecialidadeController::class, 'salvar'])->name('especialidades.salvar');
    Route::put('/especialidades/{especialidade}', [Admin\EspecialidadeController::class, 'atualizar'])
        ->name('especialidades.atualizar');

    // Convênios e planos FICTÍCIOS (decisão do grupo, 24/09) — não
    // dependem mais da ANS. Nada se apaga: desativar esconde do
    // agendamento e preserva carteirinhas e histórico.
    Route::get('/convenios', [Admin\ConvenioController::class, 'index'])->name('convenios');
    Route::post('/convenios', [Admin\ConvenioController::class, 'salvar'])->name('convenios.salvar');
    Route::put('/convenios/{convenio}', [Admin\ConvenioController::class, 'atualizar'])
        ->whereNumber('convenio')->name('convenios.atualizar');
    Route::patch('/convenios/{convenio}/status', [Admin\ConvenioController::class, 'alternar'])
        ->whereNumber('convenio')->name('convenios.status');

    Route::post('/convenios/{convenio}/planos', [Admin\ConvenioController::class, 'salvarPlano'])
        ->whereNumber('convenio')->name('convenios.planos');
    Route::put('/convenios/planos/{plano}', [Admin\ConvenioController::class, 'atualizarPlano'])
        ->whereNumber('plano')->name('convenios.planos.atualizar');
    Route::patch('/convenios/planos/{plano}/status', [Admin\ConvenioController::class, 'alternarPlano'])
        ->whereNumber('plano')->name('convenios.planos.status');

    Route::get('/consultas', [Admin\ConsultaController::class, 'index'])->name('consultas');
});

// ---------------------------------------------------------------------
// /dashboard — adicionado em 24/09.
// O Breeze, depois do login, manda para route('dashboard'). Como cada
// tipo de usuário tem o próprio painel (paciente.dashboard, ...), sem
// esta rota o login dava erro "Route [dashboard] not defined".
// ---------------------------------------------------------------------
Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->tipo . '.dashboard');
})->middleware('auth')->name('dashboard');

require __DIR__ . '/auth.php';
