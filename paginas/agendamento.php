<?php
require_once("../php/conexao.php");
$sql = $conexao->query("SELECT m.id, u.nome FROM medicos m INNER JOIN usuarios u ON m.usuario_id = u.id ORDER BY u.nome");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Consulta</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <main class="container">
        <h1>Agendar Consulta</h1>
        <form id="formAgendamento" action="../php/agendarconsulta.php" method="POST">

            <label for="especialidade">Especialidade</label>
            <input type="text" id="especialidade" name="especialidade" required>

            <label for="medico">Médico</label>
            <select id="medico" name="medico_id" required>
                <option value="">Selecione</option>
                <?php while ($medico = $sql->fetch_assoc()): ?>
                    <option value="<?= (int) $medico['id'] ?>">
                        <?= htmlspecialchars($medico['nome'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="dataConsulta">Data</label>
            <input type="date" id="dataConsulta" name="data_consulta" required>

            <label for="horario">Horário</label>
            <select id="horario" name="horario" required></select>

            <label for="tipoConsulta">Tipo de Consulta</label>
            <select id="tipoConsulta" name="tipo_consulta" required>
                <option value="">Selecione</option>
                <option>Presencial</option>
                <option>Teleconsulta</option>
            </select>

            <label for="observacoes">Observações</label>
            <textarea id="observacoes" name="observacoes"></textarea>

            <button type="submit">Agendar</button>
        </form>

        <div id="resumo"></div>
    </main>

    <script src="../js/agendamento.js"></script>
</body>
</html>