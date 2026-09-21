<?php
/*=========================================================
            MUDANÇA DE STATUS DA CONSULTA
                    FacilMed

    Faltava no sistema: dava para agendar, mas nunca para
    cancelar ou marcar como realizada — a consulta ficava
    'Agendada' para sempre e o horário nunca voltava a ficar
    livre.

    Regras de quem pode o quê:
      - PACIENTE  : só cancela consulta DELE, e só se ainda
                    não aconteceu.
      - MÉDICO    : na agenda dele, marca como 'Realizada'
                    ou 'Cancelada'.
      - ADMIN     : pode cancelar qualquer uma.
=========================================================*/

require_once(__DIR__ . "/conexao.php");
require_once(__DIR__ . "/verificarsessao.php");
require_once(__DIR__ . "/lib/helpers.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Requisição inválida.");
}

exigirCsrf();

$consultaId  = (int) ($_POST["consulta_id"] ?? 0);
$novoStatus  = $_POST["status"] ?? "";
$voltarPara  = $_POST["voltar"] ?? "";

if ($consultaId <= 0) {
    die("Consulta não informada.");
}

if (!in_array($novoStatus, ["Realizada", "Cancelada"], true)) {
    die("Status inválido.");
}

// Busca a consulta junto com o dono (paciente) e o médico responsável,
// para conferir se quem está pedindo tem mesmo direito sobre ela.
$sql = $conexao->prepare(
    "SELECT c.id, c.status, c.data_consulta, c.horario,
            p.usuario_id AS paciente_usuario_id,
            m.usuario_id AS medico_usuario_id
     FROM consultas c
     INNER JOIN pacientes p ON p.id = c.paciente_id
     INNER JOIN medicos   m ON m.id = c.medico_id
     WHERE c.id = ?"
);
$sql->bind_param("i", $consultaId);
$sql->execute();
$consulta = $sql->get_result()->fetch_assoc();
$sql->close();

if (!$consulta) {
    die("Consulta não encontrada.");
}

if ($consulta["status"] !== "Agendada") {
    die("Esta consulta já está como \"" . htmlspecialchars($consulta["status"]) . "\".");
}

$souOPaciente = ((int) $consulta["paciente_usuario_id"] === (int) $idUsuario);
$souOMedico   = ((int) $consulta["medico_usuario_id"]   === (int) $idUsuario);

// ==========================================
// PERMISSÃO
// ==========================================

if ($tipoUsuario === "paciente") {
    if (!$souOPaciente) {
        http_response_code(403);
        die("Você só pode alterar as suas próprias consultas.");
    }
    if ($novoStatus !== "Cancelada") {
        die("O paciente só pode cancelar a consulta.");
    }

    // Consulta que já passou não é cancelada — ela simplesmente aconteceu
    // (ou não). Quem fecha esse caso é o médico, marcando como realizada.
    $jaPassou = $consulta["data_consulta"] < date("Y-m-d")
        || ($consulta["data_consulta"] === date("Y-m-d") && $consulta["horario"] <= date("H:i:s"));

    if ($jaPassou) {
        die("Não é possível cancelar uma consulta que já aconteceu. Fale com a clínica.");
    }

} elseif ($tipoUsuario === "medico") {
    if (!$souOMedico) {
        http_response_code(403);
        die("Esta consulta não é da sua agenda.");
    }

} elseif ($tipoUsuario === "admin") {
    if ($novoStatus !== "Cancelada") {
        die("O administrador só pode cancelar consultas.");
    }

} else {
    http_response_code(403);
    die("Acesso negado.");
}

// ==========================================
// GRAVAÇÃO
// ==========================================
// A condição "AND status = 'Agendada'" evita que dois cliques seguidos
// (ou duas abas abertas) gravem a mudança duas vezes.

$upd = $conexao->prepare("UPDATE consultas SET status = ? WHERE id = ? AND status = 'Agendada'");
$upd->bind_param("si", $novoStatus, $consultaId);
$upd->execute();
$upd->close();

// Cancelar libera o horário automaticamente: a coluna virtual
// horario_ativo do banco vira NULL quando o status é 'Cancelada', então o
// UNIQUE deixa de bloquear aquele horário e ele volta para o calendário.

// Só aceita voltar para páginas conhecidas — assim ninguém usa este
// parâmetro para redirecionar o usuário a um site de fora.
$destinosPermitidos = [
    "paciente" => "paginas/pacientedash.php",
    "historico" => "paginas/historico.php",
    "agenda"   => "php/medico/agenda.php",
    "admin"    => "paginas/paineladmin.php",
];

$destino = $destinosPermitidos[$voltarPara] ?? $destinosPermitidos[
    $tipoUsuario === "medico" ? "agenda" : ($tipoUsuario === "admin" ? "admin" : "paciente")
];

redirecionar($destino);
