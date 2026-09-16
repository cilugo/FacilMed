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

require_once("conexao.php");
require_once("lib/helpers.php");

header("Content-Type: application/json; charset=utf-8");

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

$diaSemana = diaSemanaEnum($data);

// ==========================================
// BLOCOS DE DISPONIBILIDADE DO MÉDICO NESSE DIA DA SEMANA
// ==========================================

$stmt = $conexao->prepare(
    "SELECT hora_inicio, hora_fim, duracao_consulta_minutos
     FROM disponibilidades
     WHERE medico_id = ? AND dia_semana = ? AND ativo = 1"
);
$stmt->bind_param("is", $medicoId, $diaSemana);
$stmt->execute();
$blocos = $stmt->get_result();

$slots = [];
while ($bloco = $blocos->fetch_assoc()) {
    $inicio = strtotime($data . " " . $bloco["hora_inicio"]);
    $fim = strtotime($data . " " . $bloco["hora_fim"]);
    $duracaoSegundos = ((int) $bloco["duracao_consulta_minutos"]) * 60;

    for ($t = $inicio; $t + $duracaoSegundos <= $fim; $t += $duracaoSegundos) {
        $slots[] = date("H:i:s", $t);
    }
}

// Blocos de disponibilidade antigos podem ter sido cadastrados sobrepostos
// (antes da validação em disponibilidade.php); array_unique evita que o
// mesmo horário apareça duas vezes na lista para o paciente.
$slots = array_unique($slots);
sort($slots);

// ==========================================
// REMOVE HORÁRIOS JÁ OCUPADOS
// ==========================================

if (!empty($slots)) {
    $stmtOcupados = $conexao->prepare(
        "SELECT horario FROM consultas
         WHERE medico_id = ? AND data_consulta = ? AND status != 'Cancelada'"
    );
    $stmtOcupados->bind_param("is", $medicoId, $data);
    $stmtOcupados->execute();
    $resultado = $stmtOcupados->get_result();

    $ocupados = [];
    while ($row = $resultado->fetch_assoc()) {
        $ocupados[] = $row["horario"];
    }

    $slots = array_values(array_diff($slots, $ocupados));
}

// ==========================================
// REMOVE HORÁRIOS QUE JÁ PASSARAM, SE FOR HOJE
// ==========================================

if ($data === date("Y-m-d")) {
    $agora = date("H:i:s");
    $slots = array_values(array_filter($slots, function ($h) use ($agora) {
        return $h > $agora;
    }));
}

// Devolve só "HH:MM" (sem os segundos) para exibir ao paciente
$slots = array_map(function ($h) {
    return substr($h, 0, 5);
}, $slots);

echo json_encode(["horarios" => $slots]);
