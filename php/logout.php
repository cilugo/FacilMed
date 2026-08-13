<?php
/*=========================================================
                    LOGOUT
                    FacilMed
=========================================================*/

// Inicia a sessão
session_start();

// Remove todas as variáveis da sessão
$_SESSION = [];

// Destrói a sessão
session_destroy();

// Redireciona para o login
header("Location: ../paginas/login.html");
exit;

?>