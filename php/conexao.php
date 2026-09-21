<?php
/*=========================================================
                CONEXÃO COM O BANCO DE DADOS
                        FacilMed
=========================================================*/

require_once(__DIR__ . "/env.php");

// Dados do banco.
// Por padrão usa a configuração típica do XAMPP (root, sem senha), então
// o projeto roda sem configurar nada. Em um servidor de verdade, basta
// preencher DB_HOST/DB_USER/DB_PASS/DB_NAME no arquivo .env que estes
// valores são usados no lugar — sem mexer no código.
$servidor = getenv("DB_HOST") ?: "localhost";
$usuario  = getenv("DB_USER") ?: "root";
$senha    = getenv("DB_PASS") !== false ? getenv("DB_PASS") : "";
$banco    = getenv("DB_NAME") ?: "facilmed";

// Desativa relatórios automáticos de exceção para tratar erros manualmente
mysqli_report(MYSQLI_REPORT_OFF);

/**
 * Mostra uma mensagem de erro genérica e grava o detalhe técnico no log.
 * Detalhe de conexão (usuário, host, versão do MySQL) não deve aparecer
 * na tela do visitante.
 */
function erroDeConexao(string $detalheTecnico): void
{
    error_log("FacilMed - falha de conexão com o banco: " . $detalheTecnico);
    http_response_code(500);
    die("Não foi possível conectar ao banco de dados. Verifique se o MySQL do XAMPP está ligado.");
}

// Cria a conexão
$conexao = new mysqli($servidor, $usuario, $senha, $banco);

// Verifica erro
if ($conexao->connect_error) {
    // Se o banco ainda não existir, tenta criá-lo
    if ($conexao->connect_errno === 1049) {
        $tmp = new mysqli($servidor, $usuario, $senha);
        if ($tmp->connect_error) {
            erroDeConexao($tmp->connect_error);
        }
        $tmp->query("CREATE DATABASE IF NOT EXISTS `$banco` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $tmp->close();

        $conexao = new mysqli($servidor, $usuario, $senha, $banco);
        if ($conexao->connect_error) {
            erroDeConexao($conexao->connect_error);
        }
    } else {
        erroDeConexao($conexao->connect_error);
    }
}

// Define UTF-8.
// Precisa ser utf8mb4 (e não "utf8"), porque as tabelas foram criadas em
// utf8mb4. Com charsets diferentes entre conexão e tabela, acento e emoji
// saem corrompidos ("Jo?o", "JoÃ£o").
$conexao->set_charset("utf8mb4");
