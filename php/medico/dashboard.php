<?php

session_start();

require_once("../conexao.php");

// ==========================================
// VERIFICAR LOGIN E TIPO
// ==========================================

if (!isset($_SESSION["id"])) {
    header("Location: ../../paginas/login.html");
    exit;
}

if (!isset($_SESSION["tipo"]) || $_SESSION["tipo"] !== "medico") {
    die("Acesso permitido apenas para médicos.");
}

$usuario_id = $_SESSION["id"];
$paginaAtiva = "dashboard.php";

// ==========================================
// DADOS DO MÉDICO LOGADO
// ==========================================

$sqlMedico = "SELECT u.id, u.nome, u.email,
                     m.id AS medico_id, m.crm, m.uf, e.nome AS especialidade,
                     m.status_profissional, m.anos_atuacao
              FROM usuarios u
              INNER JOIN medicos m ON m.usuario_id = u.id
              LEFT JOIN especialidades e ON e.id = m.especialidade_id
              WHERE u.id = ?";

$stmt = $conexao->prepare($sqlMedico);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("Médico não encontrado.");
}

$medico = $resultado->fetch_assoc();
$medico_id = $medico["medico_id"];

$dataHoje = date("Y-m-d");

// ==========================================
// CONSULTAS DE HOJE
// ==========================================

$sqlConsultas = "SELECT c.id, c.horario, c.status, u.nome AS paciente_nome, l.nome AS local
                 FROM consultas c
                 INNER JOIN pacientes p ON p.id = c.paciente_id
                 INNER JOIN usuarios u ON u.id = p.usuario_id
                 LEFT JOIN locais l ON l.id = c.local_id
                 WHERE c.medico_id = ? AND c.data_consulta = ?
                 ORDER BY c.horario ASC";

$stmtConsultas = $conexao->prepare($sqlConsultas);
$stmtConsultas->bind_param("is", $medico_id, $dataHoje);
$stmtConsultas->execute();
$consultas = $stmtConsultas->get_result();
$totalConsultasHoje = $consultas->num_rows;

// ==========================================
// TOTAL DE PACIENTES ATENDIDOS PELO MÉDICO
// ==========================================

$sqlPacientes = "SELECT COUNT(DISTINCT paciente_id) AS total FROM consultas WHERE medico_id = ?";
$stmtPacientes = $conexao->prepare($sqlPacientes);
$stmtPacientes->bind_param("i", $medico_id);
$stmtPacientes->execute();
$totalPacientes = $stmtPacientes->get_result()->fetch_assoc()["total"];

// ==========================================
// PENDÊNCIAS: consultas agendadas cuja data já passou
// e ainda não foram marcadas como Realizada/Cancelada
// ==========================================

$sqlPendencias = "SELECT COUNT(*) AS total FROM consultas
                  WHERE medico_id = ? AND status = 'Agendada' AND data_consulta < ?";
$stmtPendencias = $conexao->prepare($sqlPendencias);
$stmtPendencias->bind_param("is", $medico_id, $dataHoje);
$stmtPendencias->execute();
$totalPendencias = $stmtPendencias->get_result()->fetch_assoc()["total"];

// ==========================================
// PRÓXIMA CONSULTA (primeira "Agendada" de hoje)
// ==========================================

$proximaConsulta = null;
$consultas->data_seek(0);
foreach ($consultas as $consulta) {
    if ($consulta["status"] === "Agendada") {
        $proximaConsulta = $consulta;
        break;
    }
}
$consultas->data_seek(0);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Médico | FacilMed</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/medico.css">
</head>
<body>

<?php include("_sidebar.php"); ?>

<main class="conteudo">

    <header class="topo">
        <div>
            <h1>Bom dia, Dr. <?= htmlspecialchars($medico["nome"]) ?>!</h1>
            <p><?= date("l, d \\d\\e F") ?></p>
        </div>
        <div class="acoes">
            <span title="Notificações">🔔</span>
            <span title="Perfil">👤</span>
        </div>
    </header>

    <section class="cards">
        <div class="card">
            <h3>Consultas hoje</h3>
            <strong><?= $totalConsultasHoje ?></strong>
        </div>
        <div class="card">
            <h3>Pacientes</h3>
            <strong><?= $totalPacientes ?></strong>
        </div>
        <div class="card">
            <h3>Pendências</h3>
            <strong><?= $totalPendencias ?></strong>
        </div>
    </section>

    <section class="dashboard-grid">

        <div class="painel">
            <div class="painel-titulo">
                <h2>Agenda de hoje</h2>
            </div>
            <div class="agenda">
                <?php if ($totalConsultasHoje > 0): ?>
                    <?php foreach ($consultas as $consulta): ?>
                        <div class="consulta">
                            <span class="horario"><?= date("H:i", strtotime($consulta["horario"])) ?></span>
                            <span><?= htmlspecialchars($consulta["paciente_nome"]) ?></span>
                            <span class="status"><?= htmlspecialchars($consulta["status"]) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="sem-consultas">Nenhuma consulta agendada para hoje.</p>
                <?php endif; ?>
            </div>
            <a href="agenda.php" class="botao">Ver agenda completa</a>
        </div>

        <div class="painel">
            <h2>Próxima consulta</h2>
            <?php if ($proximaConsulta): ?>
                <div class="proxima">
                    <span class="horario-grande"><?= date("H:i", strtotime($proximaConsulta["horario"])) ?></span>
                    <h3><?= htmlspecialchars($proximaConsulta["paciente_nome"]) ?></h3>
                    <p><?= htmlspecialchars($proximaConsulta["local"] ?: "Local não informado") ?></p>
                    <a href="agenda.php" class="botao">Ver consulta</a>
                </div>
            <?php else: ?>
                <p>Não há próximas consultas agendadas.</p>
            <?php endif; ?>
        </div>

    </section>

    <section class="status-profissional">
        <h2>Status profissional</h2>
        <div class="status-grid">
            <div>
                <span>Status</span>
                <strong><?= htmlspecialchars($medico["status_profissional"]) ?></strong>
            </div>
            <div>
                <span>CRM</span>
                <strong><?= htmlspecialchars($medico["crm"]) ?> / <?= htmlspecialchars($medico["uf"]) ?></strong>
            </div>
            <div>
                <span>Especialidade</span>
                <strong><?= htmlspecialchars($medico["especialidade"] ?: "Não informada") ?></strong>
            </div>
            <div>
                <span>Tempo de atuação</span>
                <strong><?= htmlspecialchars($medico["anos_atuacao"]) ?> anos</strong>
            </div>
        </div>
    </section>

</main>

</body>
</html>
