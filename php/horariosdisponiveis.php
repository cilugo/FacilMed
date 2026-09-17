<?php
/*=========================================================
        HORÁRIOS DISPONÍVEIS DE UM MÉDICO EM UM DIA
                        FacilMed
=========================================================
Recebe medico_id e data (YYYY-MM-DD) via GET e devolve em
JSON a lista de horários livres: os blocos de disponibilidade
configurados pelo médico para aquele dia da semana, menos os
horários que já têm consulta marcada (e menos os horários que
já passaram, se a data for hoje).
*/

require_once(__DIR__ . "/conexao.php");
require_once(__DIR__ . "/lib/helpers.php");
require_once(__DIR__ . "/lib/sessao.php");

header("Content-Type: application/json; charset=utf-8");

// Esta rota estava PÚBLICA: qualquer pessoa na internet podia varrer a
// agenda de qualquer médico só trocando medico_id na URL. Agora exige
// usuário logado. Como é uma API JSON (e não uma página), respondemos 401
// em JSON em vez de redirecionar para a tela de login.
iniciarSessao();

if (!isset($_SESSION["id"])) {
    http_response_code(401);
    echo json_encode(["erro" => "Sessão expirada. Faça login novamente."]);
    exit;
}

$medicoId = (int) ($_GET["medico_id"] ?? 0);
$data = $_GET["data"] ?? "";

if ($medicoId <= 0 || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $data)) {
    http_response_code(400);
    echo json_encode(["erro" => "Parâmetros inválidos."]);
    exit;
}

$timestamp = strtotime($data);
if ($timestamp === false) {
    http_response_code(400);
    echo json_encode(["erro" => "Data inválida."]);
    exit;
}

// Data no passado não tem horário nenhum para oferecer
if ($data < date("Y-m-d")) {
    echo json_encode(["horarios" => []]);
    exit;
}

// A regra de "quais horários estão livres" mora em UM lugar só
// (php/lib/helpers.php), usado também por agendarconsulta.php na hora de
// gravar. Assim o que a tela oferece e o que o servidor aceita nunca
// divergem.
$slots = horariosLivres($conexao, $medicoId, $data);

// Devolve só "HH:MM" (sem os segundos) para exibir ao paciente
$slots = array_map(function ($h) {
    return substr($h, 0, 5);
}, $slots);

echo json_encode(["horarios" => $slots]);
