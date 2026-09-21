<?php
/*=========================================================
        PERFIL PÚBLICO DO MÉDICO
                    FacilMed

    Substitui paginas/perfilmedico.html, que era um arquivo
    vazio sem nada dentro.

    Mostra os dados profissionais de um médico aprovado e os
    dias/horários em que ele atende, para o paciente decidir
    antes de ir para o agendamento.
=========================================================*/

require_once("../php/conexao.php");
require_once("../php/lib/sessao.php");
require_once("../php/lib/helpers.php");

iniciarSessao();
$logadoComoPaciente = (($_SESSION["tipo"] ?? "") === "paciente");

$medicoId = (int) ($_GET["id"] ?? 0);

if ($medicoId <= 0) {
    header("Location: especialidade.php");
    exit;
}

// Só perfil de médico aprovado é público
$sql = $conexao->prepare(
    "SELECT m.id, u.nome, m.crm, m.uf, m.anos_atuacao, m.valor_consulta,
            e.nome AS especialidade
     FROM medicos m
     INNER JOIN usuarios u ON u.id = m.usuario_id
     LEFT JOIN especialidades e ON e.id = m.especialidade_id
     WHERE m.id = ? AND m.status_profissional = 'ativo' AND u.status = 'ativo'"
);
$sql->bind_param("i", $medicoId);
$sql->execute();
$medico = $sql->get_result()->fetch_assoc();

if (!$medico) {
    http_response_code(404);
    die("Médico não encontrado ou indisponível no momento.");
}

// Horário de atendimento (blocos semanais cadastrados pelo médico)
$sqlHorarios = $conexao->prepare(
    "SELECT dia_semana, hora_inicio, hora_fim, duracao_consulta_minutos
     FROM disponibilidades
     WHERE medico_id = ? AND ativo = 1
     ORDER BY FIELD(dia_semana,'segunda','terca','quarta','quinta','sexta','sabado','domingo'), hora_inicio"
);
$sqlHorarios->bind_param("i", $medicoId);
$sqlHorarios->execute();
$horarios = $sqlHorarios->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($medico["nome"]) ?> | FacilMed</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<header>
    <h1>FacilMed</h1>
</header>

<main class="container">
    <p><a href="especialidade.php">&larr; Voltar para a lista de médicos</a></p>

    <section class="card-admin">
        <h2><?= htmlspecialchars($medico["nome"]) ?></h2>
        <p>
            <strong><?= htmlspecialchars($medico["especialidade"] ?: "Especialidade não informada") ?></strong><br>
            CRM <?= htmlspecialchars($medico["crm"]) ?>/<?= htmlspecialchars($medico["uf"]) ?>
            <?php if ((int) $medico["anos_atuacao"] > 0): ?>
                &nbsp;·&nbsp; <?= (int) $medico["anos_atuacao"] ?> anos de atuação
            <?php endif; ?>
        </p>

        <p>
            <?php if ((float) $medico["valor_consulta"] > 0): ?>
                Consulta particular: <strong>R$ <?= number_format((float) $medico["valor_consulta"], 2, ",", ".") ?></strong>
            <?php else: ?>
                Valor da consulta particular a combinar.
            <?php endif; ?>
            <br>
            <small>Atendimento por convênio e SUS conforme disponibilidade — escolha na hora de agendar.</small>
        </p>

        <h3>Horário de atendimento</h3>
        <?php if ($horarios->num_rows === 0): ?>
            <p>Este médico ainda não cadastrou horários de atendimento.</p>
        <?php else: ?>
            <table border="1" cellpadding="8" cellspacing="0">
                <thead>
                    <tr><th>Dia</th><th>Das</th><th>Até</th><th>Duração da consulta</th></tr>
                </thead>
                <tbody>
                <?php while ($h = $horarios->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars(DIAS_SEMANA_LABEL[$h["dia_semana"]] ?? $h["dia_semana"]) ?></td>
                        <td><?= date("H:i", strtotime($h["hora_inicio"])) ?></td>
                        <td><?= date("H:i", strtotime($h["hora_fim"])) ?></td>
                        <td><?= (int) $h["duracao_consulta_minutos"] ?> min</td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <p style="margin-top:20px;">
            <?php if ($logadoComoPaciente): ?>
                <a href="agendamento.php" class="botao">Agendar consulta</a>
            <?php else: ?>
                <a href="login.html" class="botao">Entrar para agendar</a>
                &nbsp;<a href="cadastropaciente.html">Criar conta</a>
            <?php endif; ?>
        </p>
    </section>
</main>

<footer>
    &copy; <?= date("Y") ?> FacilMed - Todos os direitos reservados.
</footer>
</body>
</html>
