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

// Desativa relatórios automáticos de exceção para tratar erros manualmente
mysqli_report(MYSQLI_REPORT_OFF);

// Cria a conexão
$conexao = new mysqli($servidor, $usuario, $senha, $banco);

// Verifica erro
if ($conexao->connect_error) {
    // Se o banco ainda não existir, tenta criá-lo
    if ($conexao->connect_errno === 1049) {
        $tmp = new mysqli($servidor, $usuario, $senha);
        if ($tmp->connect_error) {
            die("Erro ao conectar ao servidor MySQL: " . $tmp->connect_error);
        }
        $tmp->query("CREATE DATABASE IF NOT EXISTS `$banco` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $tmp->close();

        $conexao = new mysqli($servidor, $usuario, $senha, $banco);
        if ($conexao->connect_error) {
            die("Erro ao conectar ao banco de dados: " . $conexao->connect_error);
        }
    } else {
        die("Erro ao conectar ao banco de dados: " . $conexao->connect_error);
    }
}

// Define UTF-8
$conexao->set_charset("utf8");
?>