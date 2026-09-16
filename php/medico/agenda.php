<?php

session_start();

require_once("../conexao.php");
require_once("../lib/helpers.php");

// Verificar login
if (!isset($_SESSION["id"])) {
    header("Location: ../../paginas/login.html");
    exit;
}

// Verificar se é médico
if (!isset($_SESSION["tipo"]) || $_SESSION["tipo"] !== "medico") {
    die("Acesso permitido apenas para médicos.");
}

$paginaAtiva = "agenda.php";
$usuario_id = $_SESSION["id"];
$medico_id = getMedicoIdByUsuario($conexao, $usuario_id);

// ==========================================
// FILTROS (status e data, opcionais)
// ==========================================

$statusFiltro = $_GET["status"] ?? "";
$dataFiltro = $_GET["data"] ?? "";

$statusValidos = ["Agendada", "Realizada", "Cancelada"];
if (!in_array($statusFiltro, $statusValidos, true)) {
    $statusFiltro = "";
}

$sql = "SELECT c.id, c.data_consulta, c.horario, c.status, c.tipo_consulta, c.tipo_atendimento,
               u.nome AS paciente_nome, l.nome AS local_nome
        FROM consultas c
        INNER JOIN pacientes p ON p.id = c.paciente_id
        INNER JOIN usuarios u ON u.id = p.usuario_id
        LEFT JOIN locais l ON l.id = c.local_id
        WHERE c.medico_id = ?";

$tipos = "i";
$parametros = [$medico_id];

if ($statusFiltro !== "") {
    $sql .= " AND c.status = ?";
    $tipos .= "s";
    $parametros[] = $statusFiltro;
}

if ($dataFiltro !== "") {
    $sql .= " AND c.data_consulta = ?";
    $tipos .= "s";
    $parametros[] = $dataFiltro;
}

$sql .= " ORDER BY c.data_consulta DESC, c.horario DESC";

$stmt = $conexao->prepare($sql);
$stmt->bind_param($tipos, ...$parametros);
$stmt->execute();
$consultas = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda | FacilMed</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/medico.css">
</head>
<body>

<?php include("_sidebar.php"); ?>

<main class="conteudo">
    <header class="topo">
        <div>
            <h1>Agenda</h1>
        </div>
    </header>

    <section class="painel">
        <form method="GET" style="display:flex; gap:15px; align-items:end; flex-wrap:wrap; margin-bottom:20px;">
            <div>
                <label for="status">Status</label><br>
                <select id="status" name="status">
                    <option value="">Todos</option>
                    <?php foreach ($statusValidos as $status): ?>
                        <option value="<?= $status ?>" <?= $statusFiltro === $status ? "selected" : "" ?>>
                            <?= $status ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="data">Data</label><br>
                <input type="date" id="data" name="data" value="<?= htmlspecialchars($dataFiltro) ?>">
            </div>
            <div>
                <button type="submit" class="botao">Filtrar</button>
                <a href="agenda.php" class="botao">Limpar</a>
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Data</th><th>Horário</th><th>Paciente</th>
                    <th>Modalidade</th><th>Local</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($consultas->num_rows > 0): ?>
                    <?php while ($c = $consultas->fetch_assoc()): ?>
                        <tr>
                            <td><?= date("d/m/Y", strtotime($c["data_consulta"])) ?></td>
                            <td><?= date("H:i", strtotime($c["horario"])) ?></td>
                            <td><?= htmlspecialchars($c["paciente_nome"]) ?></td>
                            <td><?= htmlspecialchars($c["tipo_consulta"] ?: "—") ?></td>
                            <td>
                                <?= $c["tipo_consulta"] === "Teleconsulta"
                                    ? "Online"
                                    : htmlspecialchars($c["local_nome"] ?: "A definir") ?>
                            </td>
                            <td><?= htmlspecialchars($c["status"]) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">Nenhuma consulta encontrada para esse filtro.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>

<footer class="rodape-painel">&copy; <?= date("Y") ?> FacilMed — Painel do médico.</footer>

</body>
</html>
