<?php
/*=========================================================
        MÉDICOS POR ESPECIALIDADE (página pública)
                    FacilMed

    A home linkava 11 vezes para "especialidade.html", um
    arquivo que nunca existiu — todos os cards de
    especialidade davam 404. Esta página é o destino real
    desses links, e a lista vem do banco em vez de ser HTML
    fixo.

    É pública de propósito (é a vitrine do site), então mostra
    só informação profissional: nome, especialidade, CRM e
    valor. Nada de e-mail, CPF ou telefone.
=========================================================*/

require_once("../php/conexao.php");
require_once("../php/lib/sessao.php");

iniciarSessao();
$logadoComoPaciente = (($_SESSION["tipo"] ?? "") === "paciente");

/**
 * Transforma "Clínica Geral" em "clinica-geral", para casar com o
 * parâmetro que vem da home (especialidade.php?especialidade=clinica-geral).
 */
function apelidoEspecialidade(string $nome): string
{
    // Troca de acentos feita "na mão" de propósito: iconv com //TRANSLIT dá
    // resultado diferente conforme o sistema (no Windows/XAMPP pode devolver
    // "Cl'inica" em vez de "Clinica"), e aí o link da home deixaria de casar.
    $acentos = [
        "á"=>"a","à"=>"a","ã"=>"a","â"=>"a","ä"=>"a",
        "é"=>"e","è"=>"e","ê"=>"e","ë"=>"e",
        "í"=>"i","ì"=>"i","î"=>"i","ï"=>"i",
        "ó"=>"o","ò"=>"o","õ"=>"o","ô"=>"o","ö"=>"o",
        "ú"=>"u","ù"=>"u","û"=>"u","ü"=>"u",
        "ç"=>"c","ñ"=>"n",
    ];

    $texto = mb_strtolower($nome, "UTF-8");
    $texto = strtr($texto, $acentos);
    $texto = preg_replace("/[^a-z0-9]+/", "-", $texto);

    return trim($texto, "-");
}

$filtro = trim($_GET["especialidade"] ?? "");

// Descobre qual especialidade do banco corresponde ao apelido recebido
$especialidades = $conexao->query("SELECT id, nome FROM especialidades ORDER BY nome");
$listaEspecialidades = $especialidades->fetch_all(MYSQLI_ASSOC);

$especialidadeSelecionada = null;
foreach ($listaEspecialidades as $e) {
    if ($filtro !== "" && apelidoEspecialidade($e["nome"]) === strtolower($filtro)) {
        $especialidadeSelecionada = $e;
        break;
    }
}

// Só médicos aprovados aparecem para o público
$sqlTexto =
    "SELECT m.id AS medico_id, u.nome, m.crm, m.uf, m.anos_atuacao, m.valor_consulta,
            e.nome AS especialidade
     FROM medicos m
     INNER JOIN usuarios u ON u.id = m.usuario_id
     LEFT JOIN especialidades e ON e.id = m.especialidade_id
     WHERE m.status_profissional = 'ativo' AND u.status = 'ativo'";

if ($especialidadeSelecionada) {
    $sqlTexto .= " AND m.especialidade_id = ?";
}

$sqlTexto .= " ORDER BY u.nome";

$stmt = $conexao->prepare($sqlTexto);
if ($especialidadeSelecionada) {
    $stmt->bind_param("i", $especialidadeSelecionada["id"]);
}
$stmt->execute();
$medicos = $stmt->get_result();

$titulo = $especialidadeSelecionada
    ? "Médicos de " . $especialidadeSelecionada["nome"]
    : "Todos os médicos";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?> | FacilMed</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<header>
    <h1>FacilMed</h1>
</header>

<main class="container">
    <p><a href="home/home/home.html">&larr; Voltar para a página inicial</a></p>

    <section class="card-admin" style="max-width:none;">
        <h2><?= htmlspecialchars($titulo) ?></h2>

        <p>
            <strong>Especialidades:</strong>
            <a href="especialidade.php"<?= $especialidadeSelecionada ? "" : ' style="font-weight:bold;"' ?>>Todas</a>
            <?php foreach ($listaEspecialidades as $e): ?>
                &nbsp;·&nbsp;
                <a href="especialidade.php?especialidade=<?= urlencode(apelidoEspecialidade($e["nome"])) ?>"
                   <?= $especialidadeSelecionada && $especialidadeSelecionada["id"] === $e["id"] ? 'style="font-weight:bold;"' : "" ?>>
                    <?= htmlspecialchars($e["nome"]) ?>
                </a>
            <?php endforeach; ?>
        </p>

        <?php if ($medicos->num_rows === 0): ?>
            <p>Nenhum médico disponível nesta especialidade no momento.</p>
        <?php endif; ?>

        <?php while ($m = $medicos->fetch_assoc()): ?>
            <div style="border:1px solid #ddd; border-radius:8px; padding:15px; margin-bottom:12px;">
                <h3 style="margin:0 0 6px;">
                    <a href="perfilmedico.php?id=<?= (int) $m["medico_id"] ?>">
                        <?= htmlspecialchars($m["nome"]) ?>
                    </a>
                </h3>
                <p style="margin:0 0 6px;">
                    <?= htmlspecialchars($m["especialidade"] ?: "Especialidade não informada") ?>
                    &nbsp;·&nbsp; CRM <?= htmlspecialchars($m["crm"]) ?>/<?= htmlspecialchars($m["uf"]) ?>
                    <?php if ((int) $m["anos_atuacao"] > 0): ?>
                        &nbsp;·&nbsp; <?= (int) $m["anos_atuacao"] ?> anos de atuação
                    <?php endif; ?>
                </p>
                <p style="margin:0 0 10px;">
                    <?php if ((float) $m["valor_consulta"] > 0): ?>
                        Consulta particular: <strong>R$ <?= number_format((float) $m["valor_consulta"], 2, ",", ".") ?></strong>
                    <?php else: ?>
                        Valor da consulta particular a combinar
                    <?php endif; ?>
                </p>

                <?php if ($logadoComoPaciente): ?>
                    <a href="agendamento.php" class="botao">Agendar consulta</a>
                <?php else: ?>
                    <a href="login.html" class="botao">Entrar para agendar</a>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </section>
</main>

<footer>
    &copy; <?= date("Y") ?> FacilMed - Todos os direitos reservados.
</footer>
</body>
</html>
