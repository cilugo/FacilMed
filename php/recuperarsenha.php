<?php

require_once("conexao.php");


/*
|--------------------------------------------------------------------------
| Verifica o método
|--------------------------------------------------------------------------
*/

if($_SERVER["REQUEST_METHOD"] !== "POST"){

    die("Acesso inválido.");

}


/*
|--------------------------------------------------------------------------
| Recebe o e-mail
|--------------------------------------------------------------------------
*/

$email = trim($_POST["email"]);


if(empty($email)){

    die("Informe o e-mail.");

}


/*
|--------------------------------------------------------------------------
| Procura o usuário
|--------------------------------------------------------------------------
*/

$stmt = $conexao->prepare(

    "SELECT id, nome, email
     FROM usuarios
     WHERE email = ?"

);

$stmt->bind_param(
    "s",
    $email
);

$stmt->execute();

$resultado = $stmt->get_result();


/*
|--------------------------------------------------------------------------
| Verifica se encontrou
|--------------------------------------------------------------------------
*/

if($resultado->num_rows === 0){

    die("Não encontramos uma conta com esse e-mail.");

}


$usuario = $resultado->fetch_assoc();


/*
|--------------------------------------------------------------------------
| Gera código aleatório
|--------------------------------------------------------------------------
*/

$codigo = str_pad(

    random_int(0, 999999),

    6,

    "0",

    STR_PAD_LEFT

);


/*
|--------------------------------------------------------------------------
| Código expira em 15 minutos
|--------------------------------------------------------------------------
*/

$expiracao = date(

    "Y-m-d H:i:s",

    time() + (15 * 60)

);


/*
|--------------------------------------------------------------------------
| Salva código no banco
|--------------------------------------------------------------------------
*/

$atualizar = $conexao->prepare(

    "UPDATE usuarios
     SET codigo_recuperacao = ?,
         codigo_expira = ?
     WHERE id = ?"

);

$atualizar->bind_param(

    "ssi",

    $codigo,
    $expiracao,
    $usuario["id"]

);

$atualizar->execute();


/*
|--------------------------------------------------------------------------
| Mascara o e-mail
|--------------------------------------------------------------------------
*/

function mascararEmail($email){

    $partes = explode("@", $email);

    $usuarioEmail = $partes[0];

    $dominio = $partes[1];


    if(strlen($usuarioEmail) <= 2){

        $mascarado =
            substr($usuarioEmail, 0, 1)
            .
            "****";

    }else{

        $mascarado =
            substr($usuarioEmail, 0, 2)
            .
            "****";

    }


    return $mascarado . "@" . $dominio;

}


$emailMascarado = mascararEmail($email);

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Código de recuperação | FacilMed
    </title>


    <link
        rel="stylesheet"
        href="../css/style.css">


    <link
        rel="stylesheet"
        href="../css/recuperarSenha.css">

</head>


<body>


<header>

    <h1>FacilMed</h1>

</header>


<main class="container">


<section class="email-card">


    <h2>
        Recuperação de senha
    </h2>


    <!-- Simulação do e-mail -->

    <div class="email">


        <div class="email-topo">

            <strong>
                Para:
            </strong>

            <span>
                <?php
                echo htmlspecialchars(
                    $emailMascarado
                );
                ?>
            </span>

        </div>


        <div class="email-topo">

            <strong>
                Assunto:
            </strong>

            <span>
                Recuperação de senha - FacilMed
            </span>

        </div>


        <hr>


        <p>

            Olá,
            <?php
            echo htmlspecialchars(
                $usuario["nome"]
            );
            ?>!

        </p>


        <p>

            Seu código de recuperação é:

        </p>


        <div class="codigo">

            <?php
            echo $codigo;
            ?>

        </div>


        <p>

            Este código expira em:

        </p>


        <!-- Cronômetro -->

        <div
            id="cronometro"
            class="cronometro">

            15:00

        </div>


        <p
            id="mensagemExpiracao"
            class="mensagem-expiracao">

            Não compartilhe este código
            com outras pessoas.

        </p>


    </div>


    <!-- Formulário do código -->

    <form
        action="verificarCodigo.php"
        method="POST"
        id="formCodigo">


        <input
            type="hidden"
            name="email"
            value="<?php
                echo htmlspecialchars($email);
            ?>">


        <div class="campo">

            <label for="codigo">

                Digite o código recebido

            </label>


            <input

                type="text"

                id="codigo"

                name="codigo"

                maxlength="6"

                inputmode="numeric"

                pattern="[0-9]{6}"

                placeholder="000000"

                required>

        </div>


        <button
            type="submit"
            id="botaoVerificar"
            class="botao">

            Verificar código

        </button>


    </form>


    <p class="novo-codigo">

        Não recebeu o código?

        <a
            href="recuperarSenha.html">

            Solicitar novo código

        </a>

    </p>


</section>


</main>


<footer>

    © 2026 FacilMed

</footer>


<!-- JavaScript -->

<script
    src="../js/recuperarSenha.js">
</script>


<script>

    /*
     * Passa o horário de expiração
     * do PHP para o JavaScript.
     */

    const dataExpiracao =
        new Date(
            "<?php echo $expiracao; ?>"
        );

</script>


</body>

</html>