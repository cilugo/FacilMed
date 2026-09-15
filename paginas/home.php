<?php
// Página inicial do sistema Facilmed

$nomeSistema = "Facilmed";
$ano = date("Y");
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $nomeSistema; ?></title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            color: #23479f;
        }

        /* CABEÇALHO */

        header {
            height: 70px;
            border-bottom: 1px solid #ddd;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 20px;
        }

        header h1 {
            font-size: 20px;
            color: #2850ad;
        }

        /* BOTÃO ENTRAR */

        .entrar {
            background-color: white;
            border: 1px solid #2850ad;
            color: #2850ad;

            border-radius: 15px;

            padding: 5px 12px;

            cursor: pointer;
        }

        /* MENU */

        .menu {
            border: none;
            background-color: transparent;

            font-size: 30px;
            color: #2850ad;

            cursor: pointer;
        }

        /* CONTEÚDO */

        main {
            padding: 30px 20px;
        }

        /* INÍCIO */

        .inicio {
            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 20px;
        }

        .texto {
            flex: 1;
        }

        .texto h2 {
            font-size: 27px;
            line-height: 1.05;

            margin-bottom: 25px;
        }

        .texto p {
            color: #30405e;

            font-size: 14px;
            line-height: 1.4;

            margin-bottom: 15px;
        }

        /* BOTÕES */

        .botoes {
            display: flex;
            gap: 12px;
        }

        .botoes button {
            background-color: #2c52b5;

            color: white;

            border: none;

            border-radius: 20px;

            padding: 9px 14px;

            cursor: pointer;
        }

        .botoes button:hover {
            background-color: #1f3f91;
        }

        /* IMAGEM */

        .imagem {
            width: 110px;
        }

        .placeholder {
            width: 110px;
            height: 110px;

            background-color: #cbd5e3;

            border-radius: 35px;

            position: relative;
        }

        /* LINHAS DA IMAGEM */

        .placeholder::before {
            content: "";

            position: absolute;

            width: 100%;
            height: 1px;

            background-color: white;

            top: 50%;
            left: 0;

            transform: rotate(45deg);
        }

        .placeholder::after {
            content: "";

            position: absolute;

            width: 100%;
            height: 1px;

            background-color: white;

            top: 50%;
            left: 0;

            transform: rotate(-45deg);
        }

        /* COMO FUNCIONA */

        .como-funciona {
            margin-top: 40px;
        }

        .como-funciona > h2 {
            font-size: 22px;

            margin-bottom: 22px;
        }

        .etapa {
            display: flex;

            align-items: center;

            gap: 15px;

            margin-bottom: 25px;
        }

        .icone {
            min-width: 45px;
            height: 45px;

            background-color: #d3ddea;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 25px;

            color: #2850ad;
        }

        .etapa h3 {
            font-size: 14px;

            margin-bottom: 2px;
        }

        .etapa p {
            font-size: 11px;

            color: #303b4f;

            max-width: 210px;
        }

        /* NAVEGAÇÃO */

        nav {
            margin: 20px;
        }

        nav ul {
            list-style: none;

            display: flex;

            justify-content: center;

            gap: 20px;
        }

        nav a {
            text-decoration: none;

            color: #2850ad;

            font-size: 14px;
        }

        /* RODAPÉ */

        footer {
            background-color: #25489f;

            color: #b8c5e5;

            text-align: center;

            padding: 20px;

            font-size: 11px;

            margin-top: 30px;
        }

        /* RESPONSIVIDADE */

        @media (max-width: 500px) {

            .inicio {
                gap: 10px;
            }

            .texto h2 {
                font-size: 24px;
            }

            .imagem,
            .placeholder {
                width: 100px;
                height: 100px;
            }

            .botoes {
                flex-wrap: wrap;
            }

        }

    </style>

</head>


<body>

    <!-- CABEÇALHO -->

    <header>

        <button
            class="entrar"
            onclick="window.location.href='login.html'">
            Entrar
        </button>

        <h1>
            <?php echo $nomeSistema; ?>
        </h1>

        <button class="menu">
            ☰
        </button>

    </header>


    <!-- CONTEÚDO PRINCIPAL -->

    <main>

        <!-- APRESENTAÇÃO -->

        <section class="inicio">

            <div class="texto">

                <h2>
                    Cuidado que<br>
                    você merece,<br>
                    sempre que<br>
                    precisar.
                </h2>

                <p>
                    Agende consultas com facilidade de forma
                    rápida e segura.
                </p>


                <div class="botoes">

                    <button
                        onclick="window.location.href='agendamento.php'">
                        Agendar agora
                    </button>

                    <button
                        onclick="window.location.href='historico.html'">
                        Consultas agendadas
                    </button>

                </div>

            </div>


            <div class="imagem">

                <div class="placeholder"></div>

            </div>

        </section>


        <!-- COMO FUNCIONA -->

        <section class="como-funciona">

            <h2>
                Como funciona
            </h2>


            <div class="etapa">

                <div class="icone">
                    ♙
                </div>

                <div>

                    <h3>
                        Encontre um médico
                    </h3>

                    <p>
                        Pesquise por especialidade,
                        localização ou nome.
                    </p>

                </div>

            </div>


            <div class="etapa">

                <div class="icone">
                    ☑
                </div>

                <div>

                    <h3>
                        Agende sua consulta
                    </h3>

                    <p>
                        Escolha a data e horário que
                        melhor lhe atende.
                    </p>

                </div>

            </div>


            <div class="etapa">

                <div class="icone">
                    ▣
                </div>

                <div>

                    <h3>
                        Pronto
                    </h3>

                    <p>
                        Receba confirmação e lembretes
                        da consulta.
                    </p>

                </div>

            </div>

        </section>

    </main>


    <!-- MENU DE NAVEGAÇÃO -->

    <nav>

        <ul>

            <li>
                <a href="login.html">
                    Login
                </a>
            </li>

            <li>
                <a href="cadastro.html">
                    Cadastre-se
                </a>
            </li>

        </ul>

    </nav>


    <!-- RODAPÉ -->

    <footer>

        <?php echo $ano; ?> © <?php echo $nomeSistema; ?>

    </footer>


</body>

</html>