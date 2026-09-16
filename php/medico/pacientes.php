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

$paginaAtiva = "pacientes.php";
$usuario_id = $_SESSION["id"];
$medico_id = getMedicoIdByUsuario($conexao, $usuario_id);

// ==========================================
// PACIENTES QUE JÁ CONSULTARAM COM ESTE MÉDICO
// ==========================================

$sql = "SELECT u.nome, u.email, u.telefone,
               COUNT(c.id) AS total_consultas,
               MAX(c.data_consulta) AS ultima_consulta
        FROM consultas c
        INNER JOIN pacientes p ON p.id = c.paciente_id
        INNER JOIN usuarios u ON u.id = p.usuario_id
        WHERE c.medico_id = ?
        GROUP BY p.id, u.nome, u.email, u.telefone
        ORDER BY ultima_consulta DESC";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $medico_id);
$stmt->execute();
$pacientes = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pacientes | FacilMed</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/medico.css">
</head>
<body>

<?php include("_sidebar.php"); ?>

<main class="conteudo">
    <header class="topo">
        <div>
            <h1>Pacientes</h1>
        </div>
    </header>

    <section class="painel">
        <table>
            <thead>
                <tr>
                    <th>Nome</th><th>E-mail</th><th>Telefone</th>
                    <th>Consultas</th><th>Última consulta</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pacientes->num_rows > 0): ?>
                    <?php while ($p = $pacientes->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($p["nome"]) ?></td>
                            <td><?= htmlspecialchars($p["email"]) ?></td>
                            <td><?= htmlspecialchars($p["telefone"] ?: "—") ?></td>
                            <td><?= (int) $p["total_consultas"] ?></td>
                            <td><?= date("d/m/Y", strtotime($p["ultima_consulta"])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">Você ainda não atendeu nenhum paciente.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>

<footer class="rodape-painel">&copy; <?= date("Y") ?> FacilMed — Painel do médico.</footer>

</body>
</html>
