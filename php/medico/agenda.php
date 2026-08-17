<?php

session_start();

require_once("../conexao.php");

// Verificar login
if (!isset($_SESSION["id"])) {
    header("Location: ../../paginas/login.html");
    exit;
}

// Verificar se é médico
if (!isset($_SESSION["tipo"]) || $_SESSION["tipo"] !== "medico") {
    die("Acesso permitido apenas para médicos.");
}

$paginaAtiva = "agenda.php";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda | FacilMed</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/medico.css">
</head>
<body>

<?php include("_sidebar.php"); ?>

<main class="conteudo">
    <header class="topo">
        <div>
            <h1>Agenda</h1>
        </div>
    </header>

    <section class="painel">
        <p>Esta seção ainda está em construção.</p>
    </section>
</main>

</body>
</html>
