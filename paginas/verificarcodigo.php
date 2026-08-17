<?php
require_once("../php/conexao.php");

if($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acesso inválido.");
}

$email = trim($_POST["email"] ?? "");
$codigo = trim($_POST["codigo"] ?? "");

if(empty($email) || empty($codigo)) {
    die("Preencha todos os campos.");
}

$stmt = $conexao->prepare(
    "SELECT u.id FROM usuarios u
     INNER JOIN recuperacao_senha r ON r.usuario_id = u.id
     WHERE u.email = ? AND r.codigo = ? AND r.utilizado = 0 AND r.expiracao > NOW()
     ORDER BY r.id DESC LIMIT 1"
);
$stmt->bind_param("ss", $email, $codigo);
$stmt->execute();
$resultado = $stmt->get_result();

if($resultado->num_rows === 0) {
    die("Código inválido ou expirado.");
}

$usuario = $resultado->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Nova Senha | FacilMed</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/cadastro.css">
</head>
<body>
<header>
    <h1>FacilMed</h1>
</header>
<main class="container">
    <section class="card">
        <h1>Nova Senha</h1>
        <form action="../php/alterarsenha.php" method="POST">
            <input type="hidden" name="id" value="<?php echo (int) $usuario["id"]; ?>">
            <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($codigo); ?>">
            <div class="campo">
                <label for="senha">Nova senha</label>
                <input type="password" id="senha" name="senha" minlength="8" maxlength="72" required>
            </div>
            <div class="campo">
                <label for="confirmarSenha">Confirmar senha</label>
                <input type="password" id="confirmarSenha" name="confirmarSenha" minlength="8" maxlength="72" required>
            </div>
            <button type="submit" class="botao">Alterar Senha</button>
        </form>
    </section>
</main>
</body>
</html>