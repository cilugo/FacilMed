<?php
/*=========================================================
                    LOGOUT
                    FacilMed
=========================================================*/

require_once(__DIR__ . "/lib/sessao.php");
require_once(__DIR__ . "/lib/helpers.php");

// Inicia a sessão (com a mesma configuração de cookie usada no login,
// senão o session_destroy pode não apagar o cookie certo)
iniciarSessao();

// Remove todas as variáveis da sessão
$_SESSION = [];

// Apaga também o cookie de sessão no navegador
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), "", time() - 42000,
        $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}

// Destrói a sessão
session_destroy();

// Redireciona para o login
redirecionar("paginas/login.html");
