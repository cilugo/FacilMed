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

// Dias da semana em que cada médico tem algum bloco de disponibilidade
// cadastrado, para o calendário já mostrar esses dias como clicáveis
// sem precisar de uma requisição extra ao trocar de médico.
$disponibilidades = $conexao->query("SELECT DISTINCT medico_id, dia_semana FROM disponibilidades WHERE ativo = 1");
$diasDisponiveisPorMedico = [];
while($d = $disponibilidades->fetch_assoc()){
    $diasDisponiveisPorMedico[$d['medico_id']][] = $d['dia_semana'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Consulta | FacilMed</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<header>
    <h1>FacilMed</h1>
</header>
<main class="container">
    <section class="card">
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

            <div id="avisoSelecioneMedico" class="aviso-calendario">
                Selecione um médico para ver os dias e horários disponíveis.
            </div>

            <div id="blocoCalendario" style="display:none;">
                <label>Escolha o dia</label>
                <div class="calendario">
                    <div class="calendario-cabecalho">
                        <button type="button" id="mesAnterior" class="calendario-nav" aria-label="Mês anterior">&laquo;</button>
                        <span id="calendarioMesAno"></span>
                        <button type="button" id="mesProximo" class="calendario-nav" aria-label="Próximo mês">&raquo;</button>
                    </div>
                    <div class="calendario-dias-semana">
                        <span>Dom</span><span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span>
                    </div>
                    <div class="calendario-grade" id="calendarioGrade"></div>
                </div>

                <label>Horários disponíveis</label>
                <div class="horarios-grade" id="horariosGrade">
                    <p class="aviso-calendario">Escolha um dia no calendário acima.</p>
                </div>
            </div>

            <input type="hidden" id="dataConsulta" name="data_consulta" required>
            <input type="hidden" id="horario" name="horario" required>

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
    </section>
</main>
<footer>
    © 2026 FacilMed
</footer>

    <script>
        // Planos de cada convênio, gerados pelo PHP, para o JS popular o <select> de plano
        // e sugerir o valor sem precisar de outra requisição ao servidor.
        const planosPorConvenio = <?= json_encode($planosPorConvenio, JSON_UNESCAPED_UNICODE) ?>;

        // Dias da semana (segunda, terca...) em que cada médico tem disponibilidade
        // cadastrada, para o calendário saber quais dias são clicáveis.
        const diasDisponiveisPorMedico = <?= json_encode($diasDisponiveisPorMedico, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="../js/agendamento.js"></script>
</body>
</html>
