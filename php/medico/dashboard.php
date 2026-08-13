<?php

session_start();

require_once("../conexao.php");


// ==========================================
// VERIFICAR LOGIN
// ==========================================

if (!isset($_SESSION["usuario_id"])) {

    header(
        "Location: ../../paginas/login.html"
    );

    exit;

}


// ==========================================
// VERIFICAR SE É MÉDICO
// ==========================================

if (
    !isset($_SESSION["tipo"]) ||
    $_SESSION["tipo"] !== "medico"
) {

    die(
        "Acesso permitido apenas para médicos."
    );

}


// ==========================================
// ID DO USUÁRIO LOGADO
// ==========================================

$usuario_id =
    $_SESSION["usuario_id"];


// ==========================================
// BUSCAR DADOS DO MÉDICO
// ==========================================

$sqlMedico = "

    SELECT

        u.id,

        u.nome,

        u.email,

        m.id AS medico_id,

        m.crm,

        m.uf,

        m.especialidade,

        m.status_profissional,

        m.anos_atuacao

    FROM usuarios u

    INNER JOIN medicos m

        ON m.usuario_id = u.id

    WHERE u.id = ?

";


$stmt = $conexao->prepare($sqlMedico);

$stmt->bind_param(
    "i",
    $usuario_id
);

$stmt->execute();

$resultado =
    $stmt->get_result();


if ($resultado->num_rows === 0) {

    die(
        "Médico não encontrado."
    );

}


$medico =
    $resultado->fetch_assoc();


$medico_id =
    $medico["medico_id"];


// ==========================================
// DATA ATUAL
// ==========================================

$dataHoje =
    date("Y-m-d");

$dataFormatada =
    date("d/m/Y");


// ==========================================
// CONSULTAS DE HOJE
// ==========================================

$sqlConsultas = "

    SELECT

        c.id,

        c.horario,

        c.local,

        c.status,

        u.nome AS paciente_nome

    FROM consultas c

    INNER JOIN pacientes p

        ON p.id = c.paciente_id

    INNER JOIN usuarios u

        ON u.id = p.usuario_id

    WHERE c.medico_id = ?

    AND c.data_consulta = ?

    ORDER BY c.horario ASC

";


$stmtConsultas =
    $conexao->prepare(
        $sqlConsultas
    );


$stmtConsultas->bind_param(
    "is",
    $medico_id,
    $dataHoje
);


$stmtConsultas->execute();


$consultas =
    $stmtConsultas->get_result();


// ==========================================
// QUANTIDADE DE CONSULTAS
// ==========================================

$totalConsultas =
    $consultas->num_rows;


// ==========================================
// TOTAL DE PACIENTES
// ==========================================

$sqlPacientes = "

    SELECT COUNT(DISTINCT paciente_id)
    AS total

    FROM consultas

    WHERE medico_id = ?

";


$stmtPacientes =
    $conexao->prepare(
        $sqlPacientes
    );


$stmtPacientes->bind_param(
    "i",
    $medico_id
);


$stmtPacientes->execute();


$resultadoPacientes =
    $stmtPacientes->get_result();


$totalPacientes =
    $resultadoPacientes
    ->fetch_assoc()["total"];


// ==========================================
// PRÓXIMA CONSULTA
// ==========================================

$proximaConsulta = null;


foreach ($consultas as $consulta) {

    if (
        $consulta["status"]
        === "Agendada"
    ) {

        $proximaConsulta =
            $consulta;

        break;

    }

}

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width,
        initial-scale=1.0">

    <title>
        Painel Médico | FacilMed
    </title>


    <link
        rel="stylesheet"
        href="../../css/style.css">


    <link
        rel="stylesheet"
        href="../../css/medico.css">

</head>


<body>


<!-- ==========================================
     MENU LATERAL
========================================== -->

<aside class="sidebar">


    <div class="logo">

        <span>Facil</span>Med

    </div>


    <nav>


        <a
            href="dashboard.php"
            class="ativo">

            🏠
            Início

        </a>


        <a href="agenda.php">

            📅
            Agenda

        </a>


        <a href="pacientes.php">

            👥
            Pacientes

        </a>


        <a href="prontuarios.php">

            📋
            Prontuários

        </a>


        <a href="relatorios.php">

            📊
            Relatórios

        </a>


        <a href="financeiro.php">

            💰
            Financeiro

        </a>


        <a href="perfil.php">

            👤
            Meu perfil

        </a>


    </nav>


    <div class="menu-final">

        <a href="../logout.php">

            🚪
            Sair

        </a>

    </div>


</aside>



<!-- ==========================================
     CONTEÚDO
========================================== -->

<main class="conteudo">


    <!-- ======================================
         CABEÇALHO
    ======================================= -->

    <header class="topo">


        <div>

            <h1>

                Bom dia,
                Dr.
                <?= htmlspecialchars(
                    $medico["nome"]
                ); ?>!

            </h1>


            <p>

                <?= date(
                    "l, d \d\e F"
                ); ?>

            </p>

        </div>


        <div class="acoes">

            🔔

            👤

        </div>


    </header>



    <!-- ======================================
         CARDS
    ======================================= -->

    <section class="cards">


        <div class="card">

            <h3>
                Consultas hoje
            </h3>

            <strong>
                <?= $totalConsultas; ?>
            </strong>

        </div>


        <div class="card">

            <h3>
                Pacientes
            </h3>

            <strong>
                <?= $totalPacientes; ?>
            </strong>

        </div>


        <div class="card">

            <h3>
                Pendências
            </h3>

            <strong>
                3
            </strong>

        </div>


    </section>



    <!-- ======================================
         AGENDA
    ======================================= -->

    <section class="dashboard-grid">


        <div class="painel">


            <div class="painel-titulo">

                <h2>
                    Agenda de hoje
                </h2>


                <a href="agenda.php">

                    Ver agenda completa

                </a>

            </div>


            <div class="agenda">


                <?php

                if (
                    $consultas->num_rows > 0
                ):

                ?>


                    <?php

                    foreach (
                        $consultas
                        as $consulta
                    ):

                    ?>


                        <div
                            class="consulta">


                            <span
                                class="horario">

                                <?= date(
                                    "H:i",
                                    strtotime(
                                        $consulta[
                                            "horario"
                                        ]
                                    )
                                ); ?>

                            </span>


                            <span>

                                <?= htmlspecialchars(
                                    $consulta[
                                        "paciente_nome"
                                    ]
                                ); ?>

                            </span>


                            <span
                                class="status">

                                <?= htmlspecialchars(
                                    $consulta[
                                        "status"
                                    ]
                                ); ?>

                            </span>


                        </div>


                    <?php

                    endforeach;

                    ?>


                <?php

                else:

                ?>


                    <p class="sem-consultas">

                        Nenhuma consulta
                        agendada para hoje.

                    </p>


                <?php

                endif;

                ?>


            </div>


        </div>



        <!-- ==================================
             PRÓXIMA CONSULTA
        =================================== -->

        <div class="painel">


            <h2>

                Próxima consulta

            </h2>


            <?php

            if (
                $proximaConsulta
            ):

            ?>


                <div class="proxima">


                    <span
                        class="horario-grande">

                        <?= date(
                            "H:i",
                            strtotime(
                                $proximaConsulta[
                                    "horario"
                                ]
                            )
                        ); ?>

                    </span>


                    <h3>

                        <?= htmlspecialchars(
                            $proximaConsulta[
                                "paciente_nome"
                            ]
                        ); ?>

                    </h3>


                    <p>

                        <?= htmlspecialchars(
                            $proximaConsulta[
                                "local"
                            ]
                        ); ?>

                    </p>


                    <a
                        href="agenda.php"
                        class="botao">

                        Ver consulta

                    </a>


                </div>


            <?php

            else:

            ?>


                <p>

                    Não há próximas
                    consultas agendadas.

                </p>


            <?php

            endif;

            ?>


        </div>


    </section>



    <!-- ======================================
         STATUS PROFISSIONAL
    ======================================= -->

    <section class="status-profissional">


        <h2>

            Status profissional

        </h2>


        <div class="status-grid">


            <div>

                <span>

                    Status

                </span>


                <strong>

                    <?= htmlspecialchars(
                        $medico[
                            "status_profissional"
                        ]
                    ); ?>

                </strong>

            </div>


            <div>

                <span>

                    CRM

                </span>


                <strong>

                    <?= htmlspecialchars(
                        $medico["crm"]
                    ); ?>

                    /

                    <?= htmlspecialchars(
                        $medico["uf"]
                    ); ?>

                </strong>

            </div>


            <div>

                <span>

                    Especialidade

                </span>


                <strong>

                    <?= htmlspecialchars(
                        $medico[
                            "especialidade"
                        ]
                    ); ?>

                </strong>

            </div>


            <div>

                <span>

                    Tempo de atuação

                </span>


                <strong>

                    <?= htmlspecialchars(
                        $medico[
                            "anos_atuacao"
                        ]
                    ); ?>

                    anos

                </strong>

            </div>


        </div>


    </section>


</main>


<script
    src="../../js/medico.js">
</script>


</body>

</html>