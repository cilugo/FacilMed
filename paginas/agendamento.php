<?php
require_once("../php/conexao.php");

$medicos = $conexao->query(
    "SELECT m.id, u.nome, e.nome AS especialidade
     FROM medicos m
     INNER JOIN usuarios u ON m.usuario_id = u.id
     LEFT JOIN especialidades e ON e.id = m.especialidade_id
     ORDER BY u.nome"
);

$locais = $conexao->query("SELECT id, nome, cidade FROM locais WHERE ativo = 1 ORDER BY nome");

$convenios = $conexao->query("SELECT id, nome FROM convenios WHERE ativo = 1 ORDER BY nome");

// Planos agrupados por convênio, para o JS filtrar sem precisar de outra requisição
$planos = $conexao->query("SELECT id, convenio_id, nome, valor FROM planos WHERE ativo = 1 ORDER BY nome");
$planosPorConvenio = [];
while($p = $planos->fetch_assoc()){
    $planosPorConvenio[$p['convenio_id']][] = $p;
}
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

            <label for="medico">Médico</label>
            <select id="medico" name="medico_id" required>
                <option value="">Selecione</option>
                <?php while ($medico = $medicos->fetch_assoc()): ?>
                    <option value="<?= (int) $medico['id'] ?>">
                        <?= htmlspecialchars($medico['nome'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($medico['especialidade']): ?>
                            — <?= htmlspecialchars($medico['especialidade'], ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="local">Local de atendimento</label>
            <select id="local" name="local_id">
                <option value="">A definir</option>
                <?php while ($local = $locais->fetch_assoc()): ?>
                    <option value="<?= (int) $local['id'] ?>">
                        <?= htmlspecialchars($local['nome']) ?> — <?= htmlspecialchars($local['cidade']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="dataConsulta">Data</label>
            <input type="date" id="dataConsulta" name="data_consulta" required>

            <label for="horario">Horário</label>
            <select id="horario" name="horario" required></select>

            <label for="tipoConsulta">Modalidade</label>
            <select id="tipoConsulta" name="tipo_consulta" required>
                <option value="">Selecione</option>
                <option>Presencial</option>
                <option>Teleconsulta</option>
            </select>

            <label for="tipoAtendimento">Forma de atendimento</label>
            <select id="tipoAtendimento" name="tipo_atendimento" required>
                <option value="">Selecione</option>
                <option value="particular">Particular</option>
                <option value="convenio">Convênio</option>
                <option value="SUS">SUS</option>
            </select>

            <div id="blocoConvenio" style="display:none;">
                <label for="convenio">Convênio</label>
                <select id="convenio" name="convenio_id">
                    <option value="">Selecione</option>
                    <?php $convenios->data_seek(0); while ($convenio = $convenios->fetch_assoc()): ?>
                        <option value="<?= (int) $convenio['id'] ?>"><?= htmlspecialchars($convenio['nome']) ?></option>
                    <?php endwhile; ?>
                </select>

                <label for="plano">Plano</label>
                <select id="plano" name="plano_id">
                    <option value="">Selecione o convênio primeiro</option>
                </select>
            </div>

            <label for="valor">Valor da consulta (R$)</label>
            <input type="number" id="valor" name="valor" step="0.01" min="0" value="0.00">

            <label for="observacoes">Observações</label>
            <textarea id="observacoes" name="observacoes"></textarea>

            <button type="submit">Agendar</button>
        </form>

        <div id="resumo"></div>
    </main>

    <script>
        // Planos de cada convênio, gerados pelo PHP, para o JS popular o <select> de plano
        // e sugerir o valor sem precisar de outra requisição ao servidor.
        const planosPorConvenio = <?= json_encode($planosPorConvenio, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="../js/agendamento.js"></script>
</body>
</html>
