<?php
require_once(__DIR__ . "/conexao.php");
require_once(__DIR__ . "/verificarsessao.php");
require_once(__DIR__ . "/lib/helpers.php");

// Recebe dados
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    die("Acesso inválido.");
}

// Somente pacientes agendam consulta para si mesmos
exigirPerfil("paciente");

// Agendar é uma ação que grava no banco, então precisa do token de segurança
// (todas as outras ações de gravação do sistema já exigiam; esta não).
exigirCsrf();

$paciente_usuario_id = (int) $idUsuario;
$medico_id        = (int) ($_POST['medico_id'] ?? 0);
$local_id         = !empty($_POST['local_id']) ? (int) $_POST['local_id'] : null;
$data_consulta    = trim($_POST['data_consulta'] ?? '');
$horario          = trim($_POST['horario'] ?? '');
$tipo_consulta    = trim($_POST['tipo_consulta'] ?? '');
$tipo_atendimento = trim($_POST['tipo_atendimento'] ?? '');
$convenio_id      = !empty($_POST['convenio_id']) ? (int) $_POST['convenio_id'] : null;
$plano_id         = !empty($_POST['plano_id']) ? (int) $_POST['plano_id'] : null;
$observacoes      = trim($_POST['observacoes'] ?? '');

if($medico_id <= 0 || $data_consulta === '' || $horario === '' || $tipo_consulta === ''){
    die("Preencha todos os campos obrigatórios.");
}

if(!in_array($tipo_atendimento, ['SUS', 'convenio', 'particular'], true)){
    die("Forma de atendimento inválida.");
}

if(!in_array($tipo_consulta, ['Presencial', 'Teleconsulta'], true)){
    die("Tipo de consulta inválido.");
}

// ==========================================
// DATA E HORÁRIO
// ==========================================

// Aceita só o formato AAAA-MM-DD e uma data que realmente existe
// (strtotime sozinho aceita coisas como "2026-02-31" e "amanhã").
$dataObj = DateTime::createFromFormat('Y-m-d', $data_consulta);
if(!$dataObj || $dataObj->format('Y-m-d') !== $data_consulta){
    die("Data inválida.");
}

// Consulta no passado não faz sentido. Antes só o formato era conferido,
// então um POST direto marcava consulta para o ano passado.
if($data_consulta < date('Y-m-d')){
    die("Não é possível agendar em uma data que já passou.");
}

$horario = normalizarHorario($horario);
if($horario === null){
    die("Horário inválido.");
}

// ==========================================
// MÉDICO PRECISA ESTAR ATIVO
// ==========================================
// A tela só lista médicos 'ativo', mas um POST forjado podia marcar consulta
// com médico ainda pendente de aprovação ou já suspenso.

$sqlMedico = $conexao->prepare(
    "SELECT valor_consulta FROM medicos WHERE id = ? AND status_profissional = 'ativo'"
);
$sqlMedico->bind_param("i", $medico_id);
$sqlMedico->execute();
$sqlMedico->bind_result($valorParticularMedico);
if(!$sqlMedico->fetch()){
    die("Médico indisponível para agendamento.");
}
$sqlMedico->close();

// ==========================================
// O HORÁRIO PRECISA ESTAR REALMENTE LIVRE
// ==========================================
// horariosLivres() é a mesma função que monta a lista mostrada no calendário:
// respeita a grade de duração da consulta (30 em 30 min, por exemplo), exclui
// horários já ocupados e os que já passaram. A checagem antiga só olhava se o
// horário caía "dentro" do bloco, então 08:07 era aceito.

if(!in_array($horario, horariosLivres($conexao, $medico_id, $data_consulta), true)){
    die("Esse horário não está disponível. Escolha um horário livre no calendário.");
}

// ==========================================
// LOCAL (opcional, mas se vier precisa existir e estar ativo)
// ==========================================

if($local_id !== null){
    $verLocal = $conexao->prepare("SELECT 1 FROM locais WHERE id = ? AND ativo = 1");
    $verLocal->bind_param("i", $local_id);
    $verLocal->execute();
    if($verLocal->get_result()->num_rows === 0){
        die("Local de atendimento inválido.");
    }
    $verLocal->close();
}

// ==========================================
// VALOR — SEMPRE CALCULADO NO SERVIDOR
// ==========================================
// O campo "valor" do formulário é apenas informativo. Se o valor viesse do
// POST, o paciente poderia editar o HTML e marcar a própria consulta por
// R$ 0,00. Aqui ele é sempre recalculado a partir do banco.

if($tipo_atendimento === 'convenio'){
    if(!$convenio_id || !$plano_id){
        die("Selecione o convênio e o plano.");
    }
    $verPlano = $conexao->prepare("SELECT valor FROM planos WHERE id = ? AND convenio_id = ? AND ativo = 1");
    $verPlano->bind_param("ii", $plano_id, $convenio_id);
    $verPlano->execute();
    $verPlano->bind_result($valorPlano);
    if(!$verPlano->fetch()){
        die("Plano inválido para o convênio selecionado.");
    }
    $verPlano->close();
    $valor = (float) $valorPlano;

} elseif($tipo_atendimento === 'SUS'){
    // Atendimento pelo SUS não é cobrado do paciente
    $convenio_id = null;
    $plano_id = null;
    $valor = 0.00;

} else {
    // Particular: o preço é o que o próprio médico cadastrou no perfil dele
    $convenio_id = null;
    $plano_id = null;
    $valor = (float) $valorParticularMedico;
}

// ==========================================
// GRAVAÇÃO
// ==========================================

// Converte paciente_usuario_id para paciente_id (tabela pacientes)
$stmt = $conexao->prepare("SELECT id FROM pacientes WHERE usuario_id = ?");
$stmt->bind_param("i", $paciente_usuario_id);
$stmt->execute();
$stmt->bind_result($paciente_id);
if(!$stmt->fetch()){
    die("Paciente não encontrado. Faça login como paciente para agendar.");
}
$stmt->close();

// Insere consulta
$ins = $conexao->prepare(
    "INSERT INTO consultas (paciente_id, medico_id, local_id, convenio_id, plano_id, data_consulta, horario, tipo_atendimento, tipo_consulta, valor, observacoes)
     VALUES (?,?,?,?,?,?,?,?,?,?,?)"
);
$ins->bind_param(
    "iiiiissssds",
    $paciente_id, $medico_id, $local_id, $convenio_id, $plano_id,
    $data_consulta, $horario, $tipo_atendimento, $tipo_consulta, $valor, $observacoes
);

if($ins->execute()){
    echo "<script>alert('Consulta agendada com sucesso!'); window.location='"
        . htmlspecialchars(urlBase() . "/paginas/pacientedash.php", ENT_QUOTES) . "';</script>";
    exit;
} elseif($ins->errno === 1062){
    // Rede de segurança contra a condição de corrida: mesmo que duas
    // requisições passem pela checagem acima quase ao mesmo tempo, a
    // constraint UNIQUE do banco (uq_consulta_horario_ativo) rejeita a
    // segunda inserção em vez de duplicar o horário.
    die("Horário indisponível. Escolha outro horário.");
} else {
    error_log("FacilMed - erro ao agendar consulta: " . $ins->error);
    die("Erro ao agendar consulta. Tente novamente.");
}
