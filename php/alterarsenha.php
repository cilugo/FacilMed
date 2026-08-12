<?php

require_once("conexao.php");

if($_SERVER["REQUEST_METHOD"] !== "POST"){

    die("Acesso inválido.");

}

$id = $_POST["id"];

$codigo = $_POST["codigo"];

$senha = $_POST["senha"];

$confirmarSenha = $_POST["confirmarSenha"];


// Verifica tamanho

if(strlen($senha) < 8 || strlen($senha) > 9){

    die(
        "A senha deve possuir entre 8 e 9 caracteres."
    );

}


// Verifica confirmação

if($senha !== $confirmarSenha){

    die(
        "As senhas não coincidem."
    );

}


// Confirma novamente o código

$stmt = $conexao->prepare(

    "SELECT id
     FROM usuarios
     WHERE id = ?
     AND codigo_recuperacao = ?
     AND codigo_expira > NOW()"

);

$stmt->bind_param(

    "is",

    $id,
    $codigo

);

$stmt->execute();

$resultado = $stmt->get_result();

if($resultado->num_rows === 0){

    die(
        "Código inválido ou expirado."
    );

}


// Criptografa a senha

$senhaHash = password_hash(

    $senha,

    PASSWORD_DEFAULT

);


// Atualiza

$atualizar = $conexao->prepare(

    "UPDATE usuarios
     SET senha = ?,
         codigo_recuperacao = NULL,
         codigo_expira = NULL
     WHERE id = ?"

);

$atualizar->bind_param(

    "si",

    $senhaHash,
    $id

);

if($atualizar->execute()){

    echo "

    <script>

        alert(
            'Senha alterada com sucesso!'
        );

        window.location='../login.html';

    </script>

    ";

}
else{

    echo "Erro ao alterar a senha.";

}

?>