<?php

require_once("conexao.php");

$email = trim($_POST["email"]);

$codigo = trim($_POST["codigo"]);

$stmt = $conexao->prepare(

    "SELECT id
     FROM usuarios
     WHERE email = ?
     AND codigo_recuperacao = ?
     AND codigo_expira > NOW()"

);

$stmt->bind_param(

    "ss",

    $email,
    $codigo

);

$stmt->execute();

$resultado = $stmt->get_result();

if($resultado->num_rows === 0){

    die("Código inválido ou expirado.");

}

$usuario = $resultado->fetch_assoc();

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

<meta charset="UTF-8">

<title>Nova Senha | FacilMed</title>

<link rel="stylesheet"
      href="../css/style.css">

<link rel="stylesheet"
      href="../css/cadastro.css">

</head>

<body>

<header>

<h1>FacilMed</h1>

</header>

<main class="container">

<section class="card">

<h1>Nova Senha</h1>

<form
action="alterarSenha.php"
method="POST">

<input
type="hidden"
name="id"
value="<?php echo $usuario["id"]; ?>">

<input
type="hidden"
name="codigo"
value="<?php echo htmlspecialchars($codigo); ?>">

<div class="campo">

<label for="senha">

Nova senha

</label>

<input
type="password"
id="senha"
name="senha"
minlength="8"
maxlength="9"
required>

</div>

<div class="campo">

<label for="confirmarSenha">

Confirmar senha

</label>

<input
type="password"
id="confirmarSenha"
name="confirmarSenha"
minlength="8"
maxlength="9"
required>

</div>

<button
type="submit"
class="botao">

Alterar Senha

</button>

</form>

</section>

</main>

</body>

</html>