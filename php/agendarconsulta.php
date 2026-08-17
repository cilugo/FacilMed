<?php
require_once("conexao.php");
require_once("verificarsessao.php");

// Recebe dados
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    die("Acesso inválido.");
}

// Somente pacientes agendam consulta para si mesmos
if($tipoUsuario !== 'paciente'){
    die("Apenas pacientes podem agendar consultas.");
}

$paciente_usuario_id = (int) $idUsuario;
$medico_id = (int) ($_POST['medico_id'] ?? 0);
$local_id = !empty($_POST['local_id']) ? (int) $_POST['local_id'] : null;
$data_consulta = $_POST['data_consulta'] ?? '';
$horario = $_POST['horario'] ?? '';
$tipo_consulta = trim($_POST['tipo_consulta'] ?? '');
$tipo_atendimento = trim($_POST['tipo_atendimento'] ?? '');
$convenio_id = !empty($_POST['convenio_id']) ? (int) $_POST['convenio_id'] : null;
$plano_id = !empty($_POST['plano_id']) ? (int) $_POST['plano_id'] : null;
$valor = (float) ($_POST['valor'] ?? 0);
$observacoes = trim($_POST['observacoes'] ?? '');

if($medico_id <= 0 || empty($data_consulta) || empty($horario) || empty($tipo_consulta)){
    die("Preencha todos os campos obrigatórios.");
}

if(!in_array($tipo_atendimento, ['SUS', 'convenio', 'particular'], true)){
    die("Forma de atendimento inválida.");
}

// Se for por convênio, valida se o plano de fato pertence ao convênio escolhido
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
    $valor = $valorPlano;
} elseif($tipo_atendimento === 'SUS'){
    $convenio_id = null;
    $plano_id = null;
    $valor = 0.00;
} else {
    // particular: sem convênio/plano
    $convenio_id = null;
    $plano_id = null;
}

// Converte paciente_usuario_id para paciente_id (tabela pacientes)
$stmt = $conexao->prepare("SELECT id FROM pacientes WHERE usuario_id = ?");
$stmt->bind_param("i", $paciente_usuario_id);
$stmt->execute();
$stmt->bind_result($paciente_id);
if(!$stmt->fetch()){
    die("Paciente não encontrado. Faça login como paciente para agendar.");
}
$stmt->close();

// Verifica se já existe consulta no mesmo médico, data e horário
$ver = $conexao->prepare("SELECT id FROM consultas WHERE medico_id = ? AND data_consulta = ? AND horario = ? AND status != 'Cancelada'");
$ver->bind_param("iss", $medico_id, $data_consulta, $horario);
$ver->execute();
$ver->store_result();
if($ver->num_rows > 0){
    die("Horário indisponível. Escolha outro horário.");
}
$ver->close();

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
    echo "<script>alert('Consulta agendada com sucesso!'); window.location='../paginas/agendamento.php';</script>";
    exit;
} else {
    echo "Erro ao agendar consulta.";
}
?>
