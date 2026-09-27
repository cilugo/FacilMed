<?php
/*=========================================================
        HISTÓRICO COMPLETO DE CONSULTAS DO PACIENTE
                        FacilMed

    Substitui o antigo paginas/historico.html, que era um
    arquivo vazio (<body> sem nada dentro) e não estava
    ligado a lugar nenhum.

    Mostra TODAS as consultas do paciente logado, com filtro
    por status, e permite cancelar as que ainda vão acontecer.
=========================================================*/

require_once("../php/conexao.php");
require_once("../php/verificarsessao.php");

exigirPerfil("paciente");

// Paciente logado
$sqlPaciente = $conexao->prepare(
    "SELECT p.id, u.nome
     FROM pacientes p
     INNER JOIN usuarios u ON u.id = p.usuario_id
     WHERE p.usuario_id = ?"
);
$sqlPaciente->bind_param("i", $idUsuario);
$sqlPaciente->execute();
$paciente = $sqlPaciente->get_result()->fetch_assoc();

if (!$paciente) {
    die("Paciente não encontrado.");
}

$pacienteId = (int) $paciente["id"];

// Filtro por status (opcional). Só aceita os valores do ENUM do banco.
$statusValidos = ["Agendada", "Realizada", "Cancelada"];
$filtroStatus = $_GET["status"] ?? "";
if (!in_array($filtroStatus, $statusValidos, true)) {
    $filtroStatus = "";
}

$sqlTexto =
    "SELECT c.id, c.data_consulta, c.horario, c.status, c.tipo_consulta,
            c.tipo_atendimento, c.valor, c.observacoes,
            u.nome AS medico_nome, e.nome AS especialidade, l.nome AS local_nome
     FROM consultas c
     INNER JOIN medicos m ON m.id = c.medico_id
     INNER JOIN usuarios u ON u.id = m.usuario_id
     LEFT JOIN especialidades e ON e.id = m.especialidade_id
     LEFT JOIN locais l ON l.id = c.local_id
     WHERE c.paciente_id = ?";

if ($filtroStatus !== "") {
    $sqlTexto .= " AND c.status = ?";
}

$sqlTexto .= " ORDER BY c.data_consulta DESC, c.horario DESC";

$stmt = $conexao->prepare($sqlTexto);
if ($filtroStatus !== "") {
    $stmt->bind_param("is", $pacienteId, $filtroStatus);
} else {
    $stmt->bind_param("i", $pacienteId);
}
$stmt->execute();
$consultas = $stmt->get_result();

// Totais por status, para os cartões do topo
$sqlTotais = $conexao->prepare(
    "SELECT status, COUNT(*) AS total FROM consultas WHERE paciente_id = ? GROUP BY status"
);
$sqlTotais->bind_param("i", $pacienteId);
$sqlTotais->execute();
$totais = ["Agendada" => 0, "Realizada" => 0, "Cancelada" => 0];
$res = $sqlTotais->get_result();
while ($t = $res->fetch_assoc()) {
    $totais[$t["status"]] = (int) $t["total"];
}

$agora = date("Y-m-d H:i:s");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de consultas | FacilMed</title>
    <link rel="stylesheet" href="../css/pacientedash.css">
</head>
<body>

<aside class="sidebar">
    <div class="logo">FacilMed</div>
    <nav>
        <a href="pacientedash.php">Início</a>
        <a href="agendamento.php">Agendar consulta</a>
        <a href="historico.php" class="ativo">Histórico de consultas</a>
        <a href="editarpaciente.php?id=<?= $pacienteId ?>">Meu perfil</a>
    </nav>
    <div class="menu-final">
        <a href="../php/logout.php">Sair</a>
    </div>
</aside>

<main class="conteudo">
    <header class="topo">
        <div>
            <h1>Histórico de consultas</h1>
            <p>Todas as consultas de <?= htmlspecialchars($paciente["nome"]) ?>.</p>
        </div>
    </header>

    <section class="kpis">
        <div class="kpi-card"><h3>Agendadas</h3><strong><?= $totais["Agendada"] ?></strong></div>
        <div class="kpi-card"><h3>Realizadas</h3><strong><?= $totais["Realizada"] ?></strong></div>
        <div class="kpi-card"><h3>Canceladas</h3><strong><?= $totais["Cancelada"] ?></strong></div>
    </section>

    <section class="painel">
        <div class="painel-titulo">
            <h2>Consultas</h2>
            <form method="GET" action="historico.php">
                <label for="status">Filtrar:</label>
                <select name="status" id="status" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <?php foreach ($statusValidos as $s): ?>
                        <option value="<?= $s ?>" <?= $filtroStatus === $s ? "selected" : "" ?>><?= $s ?>s</option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit">Filtrar</button></noscript>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Data</th><th>Horário</th><th>Médico</th><th>Especialidade</th>
                    <th>Local</th><th>Atendimento</th><th>Valor</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if ($consultas->num_rows === 0): ?>
                <tr><td colspan="9">Nenhuma consulta encontrada<?= $filtroStatus ? " com esse filtro" : "" ?>.</td></tr>
            <?php endif; ?>

            <?php while ($c = $consultas->fetch_assoc()):
                $quando = $c["data_consulta"] . " " . $c["horario"];
                $podeCancelar = ($c["status"] === "Agendada" && $quando > $agora);
                $cores = ["Agendada" => "#1b4f9c", "Realizada" => "#1b7f3b", "Cancelada" => "#8b0000"];
            ?>
                <tr>
                    <td><?= date("d/m/Y", strtotime($c["data_consulta"])) ?></td>
                    <td><?= date("H:i", strtotime($c["horario"])) ?></td>
                    <td><?= htmlspecialchars($c["medico_nome"]) ?></td>
                    <td><?= htmlspecialchars($c["especialidade"] ?: "—") ?></td>
                    <td>
                        <?= $c["tipo_consulta"] === "Teleconsulta"
                            ? "Consulta online"
                            : htmlspecialchars($c["local_nome"] ?: "A definir") ?>
                    </td>
                    <td><?= htmlspecialchars(ucfirst($c["tipo_atendimento"])) ?></td>
                    <td>
                        <?= $c["tipo_atendimento"] === "SUS"
                            ? "—"
                            : "R$ " . number_format((float) $c["valor"], 2, ",", ".") ?>
                    </td>
                    <td>
                        <strong style="color:<?= $cores[$c["status"]] ?? "#444" ?>">
                            <?= htmlspecialchars($c["status"]) ?>
                        </strong>
                    </td>
                    <td>
                        <?php if ($podeCancelar): ?>
                            <form action="../php/statusconsulta.php" method="POST"
                                  onsubmit="return confirm('Cancelar esta consulta? O horário volta a ficar disponível.')">
                                <input type="hidden" name="consulta_id" value="<?= (int) $c["id"] ?>">
                                <input type="hidden" name="status" value="Cancelada">
                                <input type="hidden" name="voltar" value="historico">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <button type="submit">Cancelar</button>
                            </form>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </section>
</main>

<footer class="rodape-painel">&copy; <?= date("Y") ?> FacilMed — Painel do paciente.</footer>

</body>
</html>
