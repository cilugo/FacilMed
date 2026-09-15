<?php
session_start();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    // Exemplo de autenticação.
    // Substitua pela consulta ao seu banco de dados.
    if ($email === 'admin@facilmed.com' && $senha === '123456') {
        $_SESSION['usuario'] = $email;

        header('Location: dashboard.php');
        exit;
    } else {
        $erro = 'E-mail ou senha inválidos.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width,
                   initial-scale=1.0,
                   maximum-scale=1.0,
                   user-scalable=no">

    <title>Facilmed - Login</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
            font-family: Arial, Helvetica, sans-serif;
            background: #000;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* =====================================================
           CELULAR
        ===================================================== */

        .phone {
            width: 332px;
            height: 698px;

            background: #f7f9fc;

            border-radius: 57px;

            position: relative;

            overflow: hidden;

            border: 4px solid #222;

            box-shadow:
                0 0 0 2px #888,
                inset 0 0 0 1px #ddd,
                0 0 15px rgba(255,255,255,.35);
        }

        /* Borda lateral */
        .phone::before {
            content: "";
            position: absolute;

            top: 0;
            left: -4px;

            width: 4px;
            height: 100%;

            background: linear-gradient(
                to bottom,
                #777,
                #222 20%,
                #888 50%,
                #222 80%,
                #777
            );

            z-index: 20;
        }

        /* =====================================================
           BOTÕES LATERAIS
        ===================================================== */

        .side-button {
            position: absolute;
            left: -7px;
            width: 4px;
            background: #777;
            border-radius: 3px;
            z-index: 30;
        }

        .side-button.one {
            top: 128px;
            height: 29px;
        }

        .side-button.two {
            top: 180px;
            height: 48px;
        }

        .side-button.three {
            top: 239px;
            height: 48px;
        }

        .right-button {
            position: absolute;
            right: -7px;
            top: 186px;
            width: 4px;
            height: 67px;
            background: #777;
            border-radius: 3px;
            z-index: 30;
        }

        /* =====================================================
           TELA
        ===================================================== */

        .screen {
            position: relative;

            width: 100%;
            height: 100%;

            background: #f7f9fc;

            display: flex;
            flex-direction: column;

            overflow: hidden;
        }

        /* =====================================================
           DYNAMIC ISLAND
        ===================================================== */

        .dynamic-island {
            position: absolute;

            top: 15px;
            left: 50%;

            transform: translateX(-50%);

            width: 88px;
            height: 25px;

            background: #000;

            border-radius: 20px;

            z-index: 10;
        }

        .camera {
            position: absolute;

            right: 8px;
            top: 8px;

            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: #17245b;

            box-shadow:
                inset 0 0 2px #000,
                0 0 2px #243eaa;
        }

        /* =====================================================
           CONTEÚDO
        ===================================================== */

        .content {
            width: 100%;

            padding: 71px 35px 0;

            flex: 1;
        }

        /* =====================================================
           LOGO
        ===================================================== */

        .logo {
            width: 82px;
            height: 82px;

            margin: 0 auto 10px;

            position: relative;
        }

        /*
         * Logo criada em SVG para não depender de imagens externas.
         */

        .logo svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        /* =====================================================
           TÍTULO
        ===================================================== */

        h1 {
            text-align: center;

            color: #23459e;

            font-size: 23px;
            font-weight: 700;

            line-height: 1.2;

            margin-top: 3px;
        }

        .subtitle {
            text-align: center;

            color: #4169d5;

            font-size: 13px;

            margin-top: 12px;
        }

        /* =====================================================
           FORMULÁRIO
        ===================================================== */

        .login-form {
            margin-top: 33px;
        }

        .field {
            margin-bottom: 4px;
        }

        .field label {
            display: block;

            color: #315bc1;

            font-size: 11px;

            margin-left: 4px;
            margin-bottom: 3px;
        }

        .field input {
            width: 100%;

            height: 39px;

            border: 1.5px solid #2e58bd;

            border-radius: 22px;

            background: transparent;

            outline: none;

            padding: 0 19px;

            color: #333;

            font-size: 11px;

            transition: .2s;
        }

        .field input:focus {
            border-color: #21449d;

            box-shadow:
                0 0 0 2px rgba(45, 86, 190, .08);
        }

        .field input::placeholder {
            color: #4c8fe4;

            opacity: 1;
        }

        /* =====================================================
           ESQUECI SENHA
        ===================================================== */

        .forgot {
            display: block;

            text-align: right;

            margin-top: 4px;

            color: #3e8ddd;

            font-size: 10px;

            text-decoration: underline;

            cursor: pointer;
        }

        /* =====================================================
           BOTÃO ENTRAR
        ===================================================== */

        .btn-login {
            width: 100%;

            height: 39px;

            border: none;

            border-radius: 22px;

            background: #27479e;

            color: white;

            font-size: 12px;

            margin-top: 35px;

            cursor: pointer;

            transition: .2s;
        }

        .btn-login:hover {
            background: #1f3e92;
        }

        .btn-login:active {
            transform: scale(.99);
        }

        /* =====================================================
           MENSAGEM DE ERRO
        ===================================================== */

        .error {
            color: #d93434;

            font-size: 10px;

            text-align: center;

            margin-top: 8px;
        }

        /* =====================================================
           SEPARADOR
        ===================================================== */

        .separator {
            display: flex;

            align-items: center;

            width: 100%;

            margin-top: 27px;
        }

        .separator::before,
        .separator::after {
            content: "";

            height: 1.5px;

            background: #315bc1;

            flex: 1;
        }

        .separator span {
            color: #3d8edb;

            font-size: 9px;

            margin: 0 8px;

            white-space: nowrap;
        }

        /* =====================================================
           CADASTRO
        ===================================================== */

        .register {
            text-align: center;

            margin-top: 69px;

            color: #3d8edb;

            font-size: 10px;
        }

        .register a {
            color: #3d8edb;

            text-decoration: underline;
        }

        /* =====================================================
           RODAPÉ
        ===================================================== */

        .footer {
            height: 38px;

            background: #28469c;

            display: flex;

            justify-content: center;
            align-items: center;

            color: #7791d0;

            font-size: 10px;
        }

        /* =====================================================
           RESPONSIVO
        ===================================================== */

        @media (max-height: 760px) {
            .phone {
                transform: scale(.92);
            }
        }

        @media (max-height: 680px) {
            .phone {
                transform: scale(.82);
            }
        }

        @media (max-width: 380px) {
            body {
                background: #f7f9fc;
            }

            .phone {
                width: 100vw;
                height: 100vh;

                border: none;

                border-radius: 0;

                box-shadow: none;
            }

            .side-button,
            .right-button {
                display: none;
            }

            .content {
                padding-left: 31px;
                padding-right: 31px;
            }
        }
    </style>
</head>

<body>

    <div class="phone">

        <div class="side-button one"></div>
        <div class="side-button two"></div>
        <div class="side-button three"></div>
        <div class="right-button"></div>

        <div class="screen">

            <!-- Dynamic Island -->
            <div class="dynamic-island">
                <div class="camera"></div>
            </div>


            <main class="content">

                <!-- LOGO -->
                <div class="logo">

                    <svg viewBox="0 0 100 100"
                         xmlns="http://www.w3.org/2000/svg">

                        <!-- círculo azul claro -->
                        <path
                            d="M50 10
                               C32 10 18 23 17 40
                               C16 57 26 70 42 77
                               C28 69 22 57 24 43
                               C26 28 38 18 51 18
                               C61 18 68 22 73 29
                               C67 17 59 11 50 10Z"
                            fill="#159ddd"/>

                        <!-- círculo azul escuro -->
                        <path
                            d="M72 10
                               C84 25 87 43 80 60
                               C73 79 57 88 40 85
                               C25 82 14 71 11 57
                               C20 69 31 76 44 77
                               C62 78 75 65 76 48
                               C77 35 73 24 68 17
                               C69 14 70 12 72 10Z"
                            fill="#21429c"/>

                        <!-- coração -->
                        <path
                            d="M50 66
                               C46 62 32 53 32 43
                               C32 35 38 30 45 31
                               C48 31 51 33 53 36
                               C55 33 58 31 62 31
                               C69 31 74 36 74 43
                               C74 53 61 62 50 70Z"
                            fill="#2353a8"/>

                        <!-- coração interno -->
                        <path
                            d="M51 61
                               C47 57 37 51 37 44
                               C37 39 40 36 45 36
                               C48 36 51 38 53 41
                               C55 38 58 36 61 36
                               C66 36 69 39 69 44
                               C69 51 59 57 51 64Z"
                            fill="#50a5db"/>

                    </svg>

                </div>


                <!-- TÍTULO -->

                <h1>Bem-vindo de Volta!</h1>

                <p class="subtitle">
                    Faça login para continuar
                </p>


                <!-- FORMULÁRIO -->

                <form method="POST"
                      action=""
                      class="login-form">

                    <div class="field">

                        <label for="email">
                            E-mail
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="seu@email.com"
                            autocomplete="email"
                            required>

                    </div>


                    <div class="field">

                        <label for="senha">
                            Senha
                        </label>

                        <input
                            type="password"
                            id="senha"
                            name="senha"
                            placeholder="••••••••••••"
                            autocomplete="current-password"
                            required>

                    </div>


                    <a href="esqueci-senha.php"
                       class="forgot">
                        Esqueci minha senha
                    </a>


                    <button
                        type="submit"
                        class="btn-login">
                        Entrar
                    </button>


                    <?php if ($erro): ?>

                        <div class="error">
                            <?= htmlspecialchars($erro) ?>
                        </div>

                    <?php endif; ?>

                </form>


                <!-- SEPARADOR -->

                <div class="separator">

                    <span>ou entre com</span>

                </div>


                <!-- CADASTRO -->

                <div class="register">

                    Não tem uma conta?
                    <a href="cadastro.php">
                        Cadastre-se
                    </a>

                </div>

            </main>


            <!-- RODAPÉ -->

            <footer class="footer">
                2026©Facilmed
            </footer>

        </div>

    </div>

</body>
</html>

