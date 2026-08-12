<?php

session_start();


// Verifica se o código foi validado

if(
    !isset($_SESSION["recuperacao_id"])
){

    header(
        "Location: ../recuperarSenha.html"
    );

    exit;

}

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Nova Senha | FacilMed
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
        Criar nova senha
    </h2>


    <p>

        Código confirmado com sucesso!

    </p>


    <p>

        Digite sua nova senha abaixo.

    </p>



    <form
        action="alterarSenha.php"
        method="POST"
        id="formNovaSenha">


        <div class="campo">

            <label for="senha">

                Nova senha

            </label>


            <input

                type="password"

                id="senha"

                name="senha"

                minlength="8"

                maxlength="9"

                required>


        </div>



        <div class="campo">

            <label for="confirmarSenha">

                Confirmar nova senha

            </label>


            <input

                type="password"

                id="confirmarSenha"

                name="confirmarSenha"

                minlength="8"

                maxlength="9"

                required>


        </div>



        <div class="regras-senha">

            <p>
                Sua senha deve:
            </p>

            <ul>

                <li>
                    Ter entre 8 e 9 caracteres
                </li>

                <li>
                    Ser igual à confirmação
                </li>

            </ul>

        </div>



        <div class="mostrar-senha">

            <label>

                <input
                    type="checkbox"
                    id="mostrarSenha">

                Mostrar senha

            </label>

        </div>



        <button
            type="submit"
            class="botao">

            Alterar senha

        </button>


    </form>


</section>


</main>


<footer>

    © 2026 FacilMed

</footer>


<script>

const senha =
    document.getElementById("senha");


const confirmar =
    document.getElementById(
        "confirmarSenha"
    );


const mostrar =
    document.getElementById(
        "mostrarSenha"
    );


mostrar.addEventListener(
    "change",
    function(){

        if(this.checked){

            senha.type =
                "text";

            confirmar.type =
                "text";

        }else{

            senha.type =
                "password";

            confirmar.type =
                "password";

        }

    }
);


document
.getElementById("formNovaSenha")
.addEventListener(
    "submit",
    function(event){

        if(
            senha.value !==
            confirmar.value
        ){

            event.preventDefault();

            alert(
                "As senhas não são iguais."
            );

            return;

        }


        if(
            senha.value.length < 8 ||
            senha.value.length > 9
        ){

            event.preventDefault();

            alert(
                "A senha deve possuir entre 8 e 9 caracteres."
            );

        }

    }
);

</script>


</body>

</html>