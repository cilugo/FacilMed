<?php
/*=========================================================
            VERIFICAÇÃO DE SESSÃO
                    FacilMed

    Inclua este arquivo no topo de toda página que só pode
    ser vista por usuário logado. Ele:
      1. abre a sessão com cookie protegido;
      2. derruba quem passou do tempo de inatividade;
      3. manda para o login quem não estiver logado;
      4. deixa prontas as variáveis do usuário e o token CSRF.

    Para restringir por perfil, chame exigirPerfil() logo
    depois do require. Ex: exigirPerfil("admin").
=========================================================*/

require_once(__DIR__ . "/lib/sessao.php");
require_once(__DIR__ . "/lib/helpers.php");

iniciarSessao();

// Sessão parada há tempo demais é descartada e volta para o login
if (!sessaoAindaValida()) {
    redirecionar("paginas/login.html?expirada=1");
}

// Verifica se existe usuário logado
if (!isset($_SESSION["id"])) {
    // Antes aqui era header("Location: /paginas/login.html"), caminho absoluto
    // a partir da RAIZ do servidor — que dá 404 no XAMPP, onde o projeto fica
    // em htdocs/FacilMed/. redirecionar() calcula a pasta certa sozinho.
    redirecionar("paginas/login.html");
}

// Caso exista a sessão, cria variáveis para facilitar
$idUsuario    = $_SESSION["id"];
$nomeUsuario  = $_SESSION["nome"];
$emailUsuario = $_SESSION["email"];
$tipoUsuario  = $_SESSION["tipo"];

// Token CSRF reutilizável durante a sessão (protege ações destrutivas como exclusões)
if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION["csrf_token"];

/**
 * Barra o acesso de quem não tem o perfil exigido.
 *
 * Aceita um perfil ("admin") ou vários (["admin", "medico"]).
 * Encerra o script com 403 se o usuário logado não for nenhum deles.
 */
function exigirPerfil($perfis): void
{
    $permitidos = is_array($perfis) ? $perfis : [$perfis];

    if (!in_array($_SESSION["tipo"] ?? "", $permitidos, true)) {
        http_response_code(403);
        die("Acesso negado: esta página é restrita.");
    }
}

/**
 * Confere o token CSRF de um POST. Encerra o script se não bater.
 * Usar em TODA ação que grava, edita ou apaga alguma coisa.
 */
function exigirCsrf(): void
{
    $enviado = $_POST["csrf_token"] ?? "";

    if (!is_string($enviado) || !hash_equals($_SESSION["csrf_token"] ?? "", $enviado)) {
        http_response_code(403);
        die("Requisição inválida (token de segurança não confere). Recarregue a página e tente de novo.");
    }
}
