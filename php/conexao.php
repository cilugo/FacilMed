<?php
/*=========================================================
                CONEXÃO COM O BANCO DE DADOS
                        FacilMed
=========================================================*/

// Dados do banco

$servidor = "localhost";
$usuario  = "root";
$senha    = "";
$banco    = "facilmed";

// Cria a conexão

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

// Verifica erro

if($conexao->connect_error){

    die("Erro ao conectar ao banco de dados: "
        . $conexao->connect_error);

}

// Define UTF-8

$conexao->set_charset("utf8");
?>