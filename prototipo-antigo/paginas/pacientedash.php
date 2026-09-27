<?php
require_once("../php/conexao.php");
require_once("../php/verificarsessao.php");

exigirPerfil("paciente");

// ==========================================
// DADOS DO PACIENTE LOGADO
// ==========================================

$sqlPaciente = $conexao->prepare(
    "SELECT p.id AS paciente_id, u.nome
     FROM pacientes p
     INNER JOIN usuarios u ON u.id = p.usuario_id
     WHERE p.usuario_id = ?"
);
$sqlPaciente->bind_param("i", $idUsuario);
$sqlPaciente->execute();
$resultadoPaciente = $sqlPaciente->get_result();

if ($resultadoPaciente->num_rows === 0) {
    die("Paciente não encontrado.");
}

$paciente = $resultadoPaciente->fetch_assoc();
$pacienteId = $paciente["paciente_id"];
$primeiroNome = trim(explode(" ", $paciente["nome"])[0]);

$dataHoje = date("Y-m-d");
$horaAgora = date("H:i:s");

// ==========================================
// PRÓXIMA CONSULTA
// ==========================================

$sqlProxima = $conexao->prepare(
    "SELECT c.data_consulta, c.horario, c.tipo_consulta, c.status,
            u.nome AS medico_nome, e.nome AS especialidade, l.nome AS local_nome
     FROM consultas c
     INNER JOIN medicos m ON m.id = c.medico_id
     INNER JOIN usuarios u ON u.id = m.usuario_id
     LEFT JOIN especialidades e ON e.id = m.especialidade_id
     LEFT JOIN locais l ON l.id = c.local_id
     WHERE c.paciente_id = ?
       AND c.status = 'Agendada'
       AND (c.data_consulta > ? OR (c.data_consulta = ? AND c.horario >= ?))
     ORDER BY c.data_consulta ASC, c.horario ASC
     LIMIT 1"
);
$sqlProxima->bind_param("isss", $pacienteId, $dataHoje, $dataHoje, $horaAgora);
$sqlProxima->execute();
$proximaConsulta = $sqlProxima->get_result()->fetch_assoc();

// ==========================================
// TODAS AS CONSULTAS AINDA POR ACONTECER
// ==========================================
// Usadas na seção onde o paciente pode cancelar. Antes o painel só mostrava
// a próxima consulta e não existia nenhuma forma de cancelar.

$sqlAgendadas = $conexao->prepare(
    "SELECT c.id, c.data_consulta, c.horario, c.tipo_consulta, c.tipo_atendimento, c.valor,
            u.nome AS medico_nome, e.nome AS especialidade, l.nome AS local_nome
     FROM consultas c
     INNER JOIN medicos m ON m.id = c.medico_id
     INNER JOIN usuarios u ON u.id = m.usuario_id
     LEFT JOIN especialidades e ON e.id = m.especialidade_id
     LEFT JOIN locais l ON l.id = c.local_id
     WHERE c.paciente_id = ?
       AND c.status = 'Agendada'
       AND (c.data_consulta > ? OR (c.data_consulta = ? AND c.horario >= ?))
     ORDER BY c.data_consulta ASC, c.horario ASC"
);
$sqlAgendadas->bind_param("isss", $pacienteId, $dataHoje, $dataHoje, $horaAgora);
$sqlAgendadas->execute();
$consultasAgendadas = $sqlAgendadas->get_result();

// ==========================================
// HISTÓRICO DE CONSULTAS
// ==========================================

$sqlHistorico = $conexao->prepare(
    "SELECT c.data_consulta, c.status, e.nome AS especialidade, u.nome AS medico_nome
     FROM consultas c
     INNER JOIN medicos m ON m.id = c.medico_id
     INNER JOIN usuarios u ON u.id = m.usuario_id
     LEFT JOIN especialidades e ON e.id = m.especialidade_id
     WHERE c.paciente_id = ?
       AND (c.status != 'Agendada' OR c.data_consulta < ?)
     ORDER BY c.data_consulta DESC, c.horario DESC
     LIMIT 10"
);
$sqlHistorico->bind_param("is", $pacienteId, $dataHoje);
$sqlHistorico->execute();
$historico = $sqlHistorico->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Paciente | FacilMed</title>
    <link rel="stylesheet" href="../css/pacientedash.css">
</head>
<body>

<aside class="sidebar">
    <div class="logo">FacilMed</div>
    <nav>
        <a href="pacientedash.php" class="ativo">Início</a>
        <a href="agendamento.php">Agendar consulta</a>
        <a href="historico.php">Histórico de consultas</a>
        <a href="editarpaciente.php?id=<?= (int) $pacienteId ?>">Meu perfil</a>
    </nav>
    <div class="menu-final">
        <a href="../php/logout.php">Sair</a>
    </div>
</aside>

<main class="conteudo">
    <header class="topo">
        <div>
            <h1>Olá, <?= htmlspecialchars($primeiroNome) ?></h1>
            <p>O que você precisa hoje?</p>
        </div>
        <div class="acoes"><?= htmlspecialchars($paciente["nome"]) ?></div>
    </header>

    <section class="kpis">
        <a href="agendamento.php" class="kpi-card">
            <h3>Agendar consulta</h3>
        </a>
    </section>

    <section class="painel">
        <div class="painel-titulo">
            <h2>Próxima consulta</h2>
        </div>
        <div class="container-procon">
            <?php if ($proximaConsulta): ?>
                <div class="pendencia-item">
                    <strong><?= date("d/m/Y", strtotime($proximaConsulta["data_consulta"])) ?></strong>
                    <span>Às <?= date("H:i", strtotime($proximaConsulta["horario"])) ?></span>
                </div>
                <div class="pendencia-item">
                    <strong>Médico</strong>
                    <span>
                        <?= htmlspecialchars($proximaConsulta["medico_nome"]) ?>
                        <?php if ($proximaConsulta["especialidade"]): ?>
                            — <?= htmlspecialchars($proximaConsulta["especialidade"]) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="pendencia-item">
                    <strong>Local</strong>
                    <span>
                        <?php if ($proximaConsulta["tipo_consulta"] === "Teleconsulta"): ?>
                            Consulta online
                        <?php else: ?>
                            <?= htmlspecialchars($proximaConsulta["local_nome"] ?: "A definir") ?>
                        <?php endif; ?>
                    </span>
                </div>
                <a href="agendamento.php" class="botao">Agendar outra consulta</a>
            <?php else: ?>
                <p>Você não tem nenhuma consulta agendada.</p>
                <a href="agendamento.php" class="botao">Agendar consulta</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="painel" id="agendadas">
        <div class="painel-titulo">
            <h2>Minhas consultas agendadas</h2>
        </div>
        <table>
            <thead>
                <tr><th>Data</th><th>Horário</th><th>Médico</th><th>Local</th><th>Valor</th><th></th></tr>
            </thead>
            <tbody>
            <?php if ($consultasAgendadas->num_rows === 0): ?>
                <tr><td colspan="6">Nenhuma consulta marcada. <a href="agendamento.php">Agendar agora</a>.</td></tr>
            <?php endif; ?>
            <?php while ($c = $consultasAgendadas->fetch_assoc()): ?>
                <tr>
                    <td><?= date("d/m/Y", strtotime($c["data_consulta"])) ?></td>
                    <td><?= date("H:i", strtotime($c["horario"])) ?></td>
                    <td>
                        <?= htmlspecialchars($c["medico_nome"]) ?>
                        <?php if ($c["especialidade"]): ?>
                            <br><small><?= htmlspecialchars($c["especialidade"]) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $c["tipo_consulta"] === "Teleconsulta"
                            ? "Consulta online"
                            : htmlspecialchars($c["local_nome"] ?: "A definir") ?>
                    </td>
                    <td>
                        <?php if ($c["tipo_atendimento"] === "SUS"): ?>
                            SUS
                        <?php else: ?>
                            R$ <?= number_format((float) $c["valor"], 2, ",", ".") ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form action="../php/statusconsulta.php" method="POST"
                              onsubmit="return confirm('Cancelar esta consulta? O horário volta a ficar disponível.')">
                            <input type="hidden" name="consulta_id" value="<?= (int) $c["id"] ?>">
                            <input type="hidden" name="status" value="Cancelada">
                            <input type="hidden" name="voltar" value="paciente">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <button type="submit">Cancelar</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </section>

    <section class="painel" id="historico">
        <h2>Histórico</h2>
        <table>
            <thead>
                <tr><th>Data</th><th>Especialidade</th><th>Médico</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php if ($historico->num_rows > 0): ?>
                    <?php while ($item = $historico->fetch_assoc()): ?>
                        <tr>
                            <td><?= date("d/m/Y", strtotime($item["data_consulta"])) ?></td>
                            <td><?= htmlspecialchars($item["especialidade"] ?: "—") ?></td>
                            <td><?= htmlspecialchars($item["medico_nome"]) ?></td>
                            <td><?= htmlspecialchars($item["status"]) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4">Nenhuma consulta no histórico ainda.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <p><a href="historico.php">Ver histórico completo &rarr;</a></p>
    </section>

</main>

<footer class="rodape-painel">&copy; <?= date("Y") ?> FacilMed — Painel do paciente.</footer>

</body>
</html>
