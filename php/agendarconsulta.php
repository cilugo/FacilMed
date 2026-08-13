<!--conteúdo à ser revisado-->
<?php
require_once("conexao.php");
require_once("verificarsessao.php");

// Recebe dados
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    die("Acesso inválido.");
}

$paciente_usuario_id = (int) $idUsuario;
$medico_id = (int) ($_POST['medico_id'] ?? 0);
$especialidade = trim($_POST['especialidade'] ?? '');
$data_consulta = $_POST['data_consulta'] ?? '';
$horario = $_POST['horario'] ?? '';
$tipo_consulta = trim($_POST['tipo_consulta'] ?? '');
$observacoes = trim($_POST['observacoes'] ?? '');

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
$ver = $conexao->prepare("SELECT id FROM consultas WHERE medico_id = ? AND data_consulta = ? AND horario = ?");
$ver->bind_param("iss", $medico_id, $data_consulta, $horario);
$ver->execute();
$ver->store_result();
if($ver->num_rows > 0){
    die("Horário indisponível. Escolha outro horário.");
}
$ver->close();

// Insere consulta
$ins = $conexao->prepare("INSERT INTO consultas (paciente_id, medico_id, especialidade, data_consulta, horario, tipo_consulta, observacoes) VALUES (?,?,?,?,?,?,?)");
$ins->bind_param("iisssss", $paciente_id, $medico_id, $especialidade, $data_consulta, $horario, $tipo_consulta, $observacoes);

if($ins->execute()){
    echo "<script>alert('Consulta agendada com sucesso!'); window.location='../paginas/agendamento.php';</script>";
    exit;
} else {
    echo "Erro ao agendar consulta.";
}
?>
