<?php
/*=========================================================
        APROVAÇÃO / SUSPENSÃO DE MÉDICO
                    FacilMed

    Todo médico que se cadastra nasce com status_profissional
    = 'pendente', e a tela de agendamento só mostra médicos
    'ativo'. Este arquivo é o que o admin usa para aprovar o
    cadastro (pendente -> ativo) ou suspender um médico
    (ativo -> inativo), sem precisar mexer no banco à mão.
=========================================================*/

require_once(__DIR__ . "/conexao.php");
require_once(__DIR__ . "/verificarsessao.php");

// Apenas admin pode aprovar ou suspender médicos
exigirPerfil("admin");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Requisição inválida.");
}

exigirCsrf();

$medicoId = (int) ($_POST["id"] ?? 0);
$novoStatus = $_POST["status"] ?? "";

if ($medicoId <= 0) {
    die("Médico não informado.");
}

// Só aceita os valores previstos no ENUM da tabela. Assim um POST
// forjado não consegue gravar qualquer texto na coluna.
$statusPermitidos = ["pendente", "ativo", "inativo"];

if (!in_array($novoStatus, $statusPermitidos, true)) {
    die("Status inválido.");
}

$stmt = $conexao->prepare("UPDATE medicos SET status_profissional = ? WHERE id = ?");
$stmt->bind_param("si", $novoStatus, $medicoId);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    // affected_rows = 0 também acontece quando o status já era esse,
    // então não tratamos como erro — só seguimos para a listagem.
    error_log("FacilMed - statusmedico.php: nada alterado para medico_id={$medicoId}");
}

$stmt->close();

redirecionar("paginas/listarmedicos.php?status=ok");
