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

if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$paginaAtiva = "disponibilidade.php";
$usuario_id = $_SESSION["id"];
$medico_id = getMedicoIdByUsuario($conexao, $usuario_id);

$diasSemana = DIAS_SEMANA_LABEL;

$erro = null;
$sucesso = null;

// ==========================================
// ADICIONAR BLOCO DE HORÁRIO
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["acao"] ?? "") === "adicionar") {
    if (!isset($_POST["csrf_token"]) || !hash_equals($_SESSION["csrf_token"] ?? "", $_POST["csrf_token"])) {
        die("Token de segurança inválido. Recarregue a página e tente novamente.");
    }

    $diaSemana = $_POST["dia_semana"] ?? "";
    $horaInicio = $_POST["hora_inicio"] ?? "";
    $horaFim = $_POST["hora_fim"] ?? "";
    $duracao = (int) ($_POST["duracao_consulta_minutos"] ?? 30);

    if (!array_key_exists($diaSemana, $diasSemana)) {
        $erro = "Selecione um dia da semana válido.";
    } elseif (empty($horaInicio) || empty($horaFim)) {
        $erro = "Informe o horário de início e término.";
    } elseif ($horaFim <= $horaInicio) {
        $erro = "O horário de término deve ser depois do horário de início.";
    } elseif (!in_array($duracao, [15, 20, 30, 45, 60], true)) {
        $erro = "Duração de consulta inválida.";
    } else {
        // Impede blocos sobrepostos no mesmo dia: dois intervalos [a,b) e [c,d)
        // se sobrepõem quando a < d e c < b.
        $sqlSobreposicao = $conexao->prepare(
            "SELECT COUNT(*) FROM disponibilidades
             WHERE medico_id = ? AND dia_semana = ? AND hora_inicio < ? AND ? < hora_fim"
        );
        $sqlSobreposicao->bind_param("isss", $medico_id, $diaSemana, $horaFim, $horaInicio);
        $sqlSobreposicao->execute();
        $sqlSobreposicao->bind_result($totalSobreposto);
        $sqlSobreposicao->fetch();
        $sqlSobreposicao->close();

        if ($totalSobreposto > 0) {
            $erro = "Esse horário se sobrepõe a um bloco já cadastrado para esse dia.";
        } else {
            $stmt = $conexao->prepare(
                "INSERT INTO disponibilidades (medico_id, dia_semana, hora_inicio, hora_fim, duracao_consulta_minutos)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("isssi", $medico_id, $diaSemana, $horaInicio, $horaFim, $duracao);
            $sucesso = $stmt->execute() ? "Horário adicionado com sucesso!" : "Erro ao adicionar horário.";
        }
    }
}

// ==========================================
// REMOVER BLOCO DE HORÁRIO
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["acao"] ?? "") === "remover") {
    if (!isset($_POST["csrf_token"]) || !hash_equals($_SESSION["csrf_token"] ?? "", $_POST["csrf_token"])) {
        die("Token de segurança inválido. Recarregue a página e tente novamente.");
    }

    $id = (int) ($_POST["id"] ?? 0);
    // Garante que o médico só remove os próprios blocos de horário
    $stmt = $conexao->prepare("DELETE FROM disponibilidades WHERE id = ? AND medico_id = ?");
    $stmt->bind_param("ii", $id, $medico_id);
    $sucesso = $stmt->execute() ? "Horário removido." : "Erro ao remover horário.";
}

// ==========================================
// LISTAR BLOCOS ATUAIS
// ==========================================
$sql = $conexao->prepare(
    "SELECT id, dia_semana, hora_inicio, hora_fim, duracao_consulta_minutos
     FROM disponibilidades
     WHERE medico_id = ?
     ORDER BY FIELD(dia_semana, 'segunda','terca','quarta','quinta','sexta','sabado','domingo'), hora_inicio"
);
$sql->bind_param("i", $medico_id);
$sql->execute();
$blocos = $sql->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horários de Atendimento | FacilMed</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/medico.css">
</head>
<body>

<?php include("_sidebar.php"); ?>

<main class="conteudo">
    <header class="topo">
        <div>
            <h1>Horários de Atendimento</h1>
            <p>Configure os dias e horários em que você atende. Os pacientes só conseguem agendar dentro desses blocos.</p>
        </div>
    </header>

    <?php if ($erro): ?><p class="erro"><?= htmlspecialchars($erro) ?></p><?php endif; ?>
    <?php if ($sucesso): ?><p class="sucesso"><?= htmlspecialchars($sucesso) ?></p><?php endif; ?>

    <section class="painel">
        <h2>Novo bloco de horário</h2>
        <form method="POST" style="display:flex; gap:15px; align-items:end; flex-wrap:wrap;">
            <input type="hidden" name="acao" value="adicionar">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "") ?>">

            <div>
                <label for="dia_semana">Dia da semana</label><br>
                <select id="dia_semana" name="dia_semana" required>
                    <?php foreach ($diasSemana as $valor => $rotulo): ?>
                        <option value="<?= $valor ?>"><?= $rotulo ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="hora_inicio">Início</label><br>
                <input type="time" id="hora_inicio" name="hora_inicio" required>
            </div>
            <div>
                <label for="hora_fim">Término</label><br>
                <input type="time" id="hora_fim" name="hora_fim" required>
            </div>
            <div>
                <label for="duracao_consulta_minutos">Duração de cada consulta</label><br>
                <select id="duracao_consulta_minutos" name="duracao_consulta_minutos">
                    <option value="15">15 minutos</option>
                    <option value="20">20 minutos</option>
                    <option value="30" selected>30 minutos</option>
                    <option value="45">45 minutos</option>
                    <option value="60">60 minutos</option>
                </select>
            </div>
            <div>
                <button type="submit" class="botao">Adicionar</button>
            </div>
        </form>
    </section>

    <section class="painel">
        <h2>Blocos configurados</h2>
        <table>
            <thead>
                <tr><th>Dia</th><th>Início</th><th>Término</th><th>Duração da consulta</th><th>Ações</th></tr>
            </thead>
            <tbody>
                <?php if ($blocos->num_rows > 0): ?>
                    <?php while ($b = $blocos->fetch_assoc()): ?>
                        <tr>
                            <td><?= $diasSemana[$b["dia_semana"]] ?></td>
                            <td><?= date("H:i", strtotime($b["hora_inicio"])) ?></td>
                            <td><?= date("H:i", strtotime($b["hora_fim"])) ?></td>
                            <td><?= (int) $b["duracao_consulta_minutos"] ?> min</td>
                            <td>
                                <form method="POST" class="form-inline" onsubmit="return confirm('Remover este horário?')">
                                    <input type="hidden" name="acao" value="remover">
                                    <input type="hidden" name="id" value="<?= (int) $b["id"] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "") ?>">
                                    <button type="submit" class="link-botao">Remover</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">Você ainda não configurou nenhum horário de atendimento.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>

<footer class="rodape-painel">&copy; <?= date("Y") ?> FacilMed — Painel do médico.</footer>

</body>
</html>
