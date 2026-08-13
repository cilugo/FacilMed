<?php
/*=========================================================
            VERIFICAÇÃO DE SESSÃO
                    FacilMed
=========================================================*/

// Inicia a sessão

session_start();

// Verifica se existe usuário logado

if(!isset($_SESSION["id"])){

    header("Location: ../paginas/login.html");

    exit();

}

// Caso exista a sessão, cria variáveis para facilitar

$idUsuario = $_SESSION["id"];

$nomeUsuario = $_SESSION["nome"];

$emailUsuario = $_SESSION["email"];

$tipoUsuario = $_SESSION["tipo"];

?>