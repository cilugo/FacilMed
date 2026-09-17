<?php
require_once(__DIR__ . "/lib/sessao.php");
require_once(__DIR__ . "/lib/helpers.php");
require_once(__DIR__ . "/conexao.php");

iniciarSessao();

if($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acesso inválido.");
}

// O "?? ''" evita o warning "Undefined array key" quando o formulário
// é enviado sem os campos (ou por um POST feito fora da página).
$email = trim($_POST["email"] ?? "");
$senha = trim($_POST["senha"] ?? "");

// Validação básica
if(empty($email) || empty($senha)) {
    die("Preencha todos os campos.");
}

// Busca o usuário no banco
$stmt = $conexao->prepare("SELECT id, nome, email, tipo, senha, status FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0) {
    die("Email ou senha incorretos.");
}

$usuario = $result->fetch_assoc();

// Verifica a senha
if(!password_verify($senha, $usuario["senha"])) {
    die("Email ou senha incorretos.");
}

// Bloqueia login de contas inativas/bloqueadas
if($usuario["status"] === "bloqueado") {
    die("Sua conta está bloqueada. Entre em contato com o suporte.");
}
if($usuario["status"] === "inativo") {
    die("Sua conta está inativa. Entre em contato com o suporte.");
}

// Troca o identificador da sessão ANTES de gravar os dados do usuário.
// Sem isso, um atacante que conseguisse fixar um id de sessão no navegador
// da vítima (session fixation) continuaria com o mesmo id depois do login —
// e entraria junto na conta dela.
session_regenerate_id(true);

// Cria a sessão
$_SESSION["id"]            = $usuario["id"];
$_SESSION["nome"]          = $usuario["nome"];
$_SESSION["email"]         = $usuario["email"];
$_SESSION["tipo"]          = $usuario["tipo"];
$_SESSION["ultimo_acesso"] = time();

// Redireciona baseado no tipo de usuário
$destinos = [
    "paciente" => "paginas/pacientedash.php",
    "medico"   => "php/medico/dashboard.php",
    "admin"    => "paginas/paineladmin.php",
];

$destino = $destinos[$usuario["tipo"]] ?? "paginas/login.html";

echo "<script>alert('Login realizado com sucesso!'); window.location='"
    . htmlspecialchars(urlBase() . "/" . $destino, ENT_QUOTES)
    . "';</script>";
exit;
