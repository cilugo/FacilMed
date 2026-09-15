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

        /* =========================================
           CONFIGURAÇÕES GERAIS
        ========================================= */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #ffffff;
            color: #23479f;
            min-height: 100vh;
        }


        /* =========================================
           CABEÇALHO
        ========================================= */

        header {
            width: 100%;
            height: 70px;

            border-bottom: 1px solid #e1e1e1;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 30px;

            background-color: #ffffff;
        }


        /* =========================================
           LOGO
        ========================================= */

        .logo {
            display: flex;
            align-items: center;

            text-decoration: none;

            color: #2850ad;
        }

        .logo img {
            width: 55px;
            height: 55px;

            object-fit: contain;
        }

        .logo-text {
            color: #2850ad;

            font-size: 21px;

            font-weight: bold;
        }


        /* =========================================
           PARTE DIREITA
        ========================================= */

        .header-direita {
            display: flex;

            align-items: center;

            gap: 12px;
        }


        /* =========================================
           BOTÕES DO CABEÇALHO
        ========================================= */

        .entrar,
        .cadastre-se {
            background-color: #ffffff;

            border: 1px solid #2850ad;

            color: #2850ad;

            border-radius: 18px;

            padding: 7px 16px;

            font-size: 13px;

            font-family: Arial, Helvetica, sans-serif;

            text-decoration: none;

            cursor: pointer;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            transition: 0.2s;
        }


        /* Efeito ao passar o mouse */

        .entrar:hover,
        .cadastre-se:hover {
            background-color: #2850ad;

            color: #ffffff;
        }


        /* =========================================
           CONTEÚDO PRINCIPAL
        ========================================= */

        main {
            width: 100%;

            max-width: 1100px;

            margin: 0 auto;

            padding: 50px 30px 40px;
        }


        /* =========================================
           APRESENTAÇÃO
        ========================================= */

        .inicio {
            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 60px;

            min-height: 330px;
        }


        /* =========================================
           TEXTO
        ========================================= */

        .texto {
            flex: 1;

            max-width: 600px;
        }


        .texto h2 {
            color: #23479f;

            font-size: 42px;

            line-height: 1.12;

            margin-bottom: 25px;
        }


        .texto p {
            color: #30405e;

            font-size: 16px;

            line-height: 1.5;

            max-width: 500px;

            margin-bottom: 25px;
        }


        /* =========================================
           BOTÕES PRINCIPAIS
        ========================================= */

        .botoes {
            display: flex;

            align-items: center;

            gap: 12px;

            flex-wrap: wrap;
        }


        .botoes button {
            background-color: #2c52b5;

            color: #ffffff;

            border: none;

            border-radius: 22px;

            padding: 11px 18px;

            font-size: 13px;

            cursor: pointer;

            transition: 0.2s;
        }


        .botoes button:hover {
            background-color: #1f3f91;

            transform: translateY(-1px);
        }


        /* =========================================
           IMAGEM
        ========================================= */

        .imagem {
            width: 300px;

            display: flex;

            justify-content: center;

            align-items: center;
        }


        .imagem img {
            width: 100%;

            max-width: 300px;

            height: auto;

            object-fit: contain;
        }


        /* =========================================
           PLACEHOLDER DA IMAGEM
        ========================================= */

        .placeholder {
            width: 250px;

            height: 250px;

            background-color: #cbd5e3;

            border-radius: 60px;

            position: relative;

            overflow: hidden;
        }


        .placeholder::before {
            content: "";

            position: absolute;

            width: 140%;

            height: 2px;

            background-color: #ffffff;

            top: 50%;

            left: -20%;

            transform: rotate(45deg);
        }


        .placeholder::after {
            content: "";

            position: absolute;

            width: 140%;

            height: 2px;

            background-color: #ffffff;

            top: 50%;

            left: -20%;

            transform: rotate(-45deg);
        }


        /* =========================================
           COMO FUNCIONA
        ========================================= */

        .como-funciona {
            margin-top: 70px;
        }


        .como-funciona > h2 {
            color: #23479f;

            font-size: 28px;

            margin-bottom: 35px;
        }


        /* =========================================
           ETAPAS
        ========================================= */

        .etapas {
            display: grid;

            grid-template-columns: repeat(3, 1fr);

            gap: 35px;
        }


        .etapa {
            display: flex;

            align-items: flex-start;

            gap: 15px;
        }


        /* =========================================
           ÍCONES
        ========================================= */

        .icone {
            min-width: 50px;

            width: 50px;

            height: 50px;

            background-color: #d3ddea;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 23px;

            color: #2850ad;
        }


        /* =========================================
           TEXTO DAS ETAPAS
        ========================================= */

        .etapa h3 {
            color: #23479f;

            font-size: 15px;

            margin-bottom: 7px;
        }


        .etapa p {
            color: #303b4f;

            font-size: 12px;

            line-height: 1.5;
        }


        /* =========================================
           RODAPÉ
        ========================================= */

        footer {
            width: 100%;

            background-color: #25489f;

            color: #b8c5e5;

            text-align: center;

            padding: 20px;

            font-size: 11px;

            margin-top: 50px;
        }


        /* =========================================
           TABLET
        ========================================= */

        @media (max-width: 800px) {

            header {
                padding: 0 20px;
            }

            main {
                padding: 40px 25px;
            }

            .inicio {
                gap: 30px;
            }

            .texto h2 {
                font-size: 34px;
            }

            .imagem {
                width: 220px;
            }

            .placeholder {
                width: 190px;

                height: 190px;

                border-radius: 45px;
            }

            .etapas {
                grid-template-columns: 1fr;
            }

            .etapa {
                max-width: 500px;
            }

        }


        /* =========================================
           CELULAR
        ========================================= */

        @media (max-width: 600px) {

            header {
                height: 65px;

                padding: 0 16px;
            }


            .logo-text {
                font-size: 19px;
            }


            .header-direita {
                gap: 8px;
            }


            .entrar,
            .cadastre-se {
                padding: 6px 11px;

                font-size: 11px;
            }


            main {
                padding: 35px 20px;
            }


            .inicio {
                flex-direction: column;

                align-items: flex-start;

                gap: 30px;
            }


            .texto {
                width: 100%;
            }


            .texto h2 {
                font-size: 30px;

                line-height: 1.1;

                margin-bottom: 20px;
            }


            .texto p {
                font-size: 14px;

                margin-bottom: 22px;
            }


            .botoes {
                width: 100%;

                flex-direction: column;

                align-items: stretch;
            }


            .botoes button {
                width: 100%;

                padding: 11px;
            }


            .imagem {
                width: 100%;

                display: flex;

                justify-content: center;
            }


            .placeholder {
                width: 160px;

                height: 160px;

                border-radius: 40px;
            }


            .como-funciona {
                margin-top: 45px;
            }


            .como-funciona > h2 {
                font-size: 24px;

                margin-bottom: 25px;
            }


            .etapas {
                gap: 25px;
            }


            .etapa {
                gap: 12px;
            }


            .icone {
                min-width: 45px;

                width: 45px;

                height: 45px;
            }


            .etapa h3 {
                font-size: 14px;
            }


            .etapa p {
                font-size: 11px;
            }

        }


        /* =========================================
           CELULAR PEQUENO
        ========================================= */

        @media (max-width: 380px) {

            header {
                padding: 0 12px;
            }


            .logo-text {
                font-size: 17px;
            }


            .header-direita {
                gap: 5px;
            }


            .entrar,
            .cadastre-se {
                padding: 5px 9px;

                font-size: 10px;
            }


            .texto h2 {
                font-size: 27px;
            }


            .placeholder {
                width: 140px;

                height: 140px;
            }

        }

    </style>

</head>


<body>


    <!-- =========================================
         CABEÇALHO
    ========================================== -->

    <header>


        <!-- LOGO À ESQUERDA -->

        <a
            href="index.php"
            class="logo"
        >

            <span class="logo-text">
                <?php echo $nomeSistema; ?>
            </span>

        </a>


        <!-- BOTÕES À DIREITA -->

        <div class="header-direita">


            <!-- BOTÃO ENTRAR -->

            <button
                class="entrar"
                onclick="window.location.href='login.php'"
            >
                Entrar
            </button>


            <!-- BOTÃO CADASTRE-SE -->

            <a
                href="cadastro.php"
                class="cadastre-se"
            >
                Cadastre-se
            </a>


        </div>


    </header>



    <!-- =========================================
         CONTEÚDO
    ========================================== -->

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

                    Agende consultas com facilidade
                    de forma rápida e segura.

                </p>


                <div class="botoes">


                    <button
                        onclick="window.location.href='agendamento.php'"
                    >
                        Agendar agora
                    </button>


                    <button
                        onclick="window.location.href='historico.php'"
                    >
                        Consultas agendadas
                    </button>


                </div>


            </div>


            <!-- IMAGEM -->

            <div class="imagem">

                <div class="placeholder"></div>

            </div>


        </section>



        <!-- =========================================
             COMO FUNCIONA
        ========================================== -->

        <section class="como-funciona">


            <h2>
                Como funciona
            </h2>


            <div class="etapas">


                <!-- ETAPA 1 -->

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


                <!-- ETAPA 2 -->

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


                <!-- ETAPA 3 -->

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


            </div>


        </section>


    </main>



    <!-- =========================================
         RODAPÉ
    ========================================== -->

    <footer>

        <?php echo $ano; ?>
        ©
        <?php echo $nomeSistema; ?>

    </footer>


</body>

</html>


