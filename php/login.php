<?php
session_start();
require_once("conexao.php");

if($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acesso inválido.");
}

$email = trim($_POST["email"]);
$senha = trim($_POST["senha"]);

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

// Cria a sessão
$_SESSION["id"] = $usuario["id"];
$_SESSION["nome"] = $usuario["nome"];
$_SESSION["email"] = $usuario["email"];
$_SESSION["tipo"] = $usuario["tipo"];

// Redireciona baseado no tipo de usuário
if($usuario["tipo"] === "paciente") {
    echo "<script>alert('Login realizado com sucesso!'); window.location='../paginas/homepaciente.html';</script>";
} elseif($usuario["tipo"] === "medico") {
    echo "<script>alert('Login realizado com sucesso!'); window.location='../php/medico/dashboard.php';</script>";
} elseif($usuario["tipo"] === "admin") {
    echo "<script>alert('Login realizado com sucesso!'); window.location='../paginas/paineladmin.php';</script>";
}
exit;
?>
