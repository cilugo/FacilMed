<?php
/*=========================================================
                        FacilMed
                       sessao.php

    Um único lugar que sabe COMO abrir a sessão. Antes cada
    arquivo chamava session_start() sozinho, com configurações
    diferentes (ou nenhuma), e o cookie de sessão saía sem
    proteção alguma.

    Quem precisa apenas abrir a sessão (login.php, logout.php)
    usa iniciarSessao(). Quem precisa de usuário JÁ logado usa
    php/verificarsessao.php, que chama esta função por baixo.
==========================================================*/

/** Tempo máximo de inatividade antes de derrubar a sessão (em segundos). */
const SESSAO_TEMPO_INATIVIDADE = 30 * 60; // 30 minutos

/**
 * Abre a sessão com cookie protegido. Pode ser chamada várias vezes
 * sem problema — se a sessão já estiver aberta, ela não faz nada.
 */
function iniciarSessao(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // HttpOnly: JavaScript não consegue ler o cookie (barra roubo de sessão por XSS).
    // SameSite=Lax: o cookie não é enviado em requisições vindas de outros sites (barra CSRF).
    // Secure: só liga sozinho quando o site está em HTTPS, para não quebrar o XAMPP local.
    session_set_cookie_params([
        "lifetime" => 0,
        "path"     => "/",
        "httponly" => true,
        "samesite" => "Lax",
        "secure"   => (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off"),
    ]);

    session_start();
}

/**
 * Derruba a sessão se o usuário ficou tempo demais parado.
 * Retorna true se a sessão continua válida, false se expirou.
 */
function sessaoAindaValida(): bool
{
    $agora = time();

    if (isset($_SESSION["ultimo_acesso"]) &&
        ($agora - $_SESSION["ultimo_acesso"]) > SESSAO_TEMPO_INATIVIDADE) {

        $_SESSION = [];
        session_destroy();
        return false;
    }

    $_SESSION["ultimo_acesso"] = $agora;
    return true;
}
