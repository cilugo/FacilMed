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

const MAX_TENTATIVAS_CODIGO = 5;

// Busca a solicitação de recuperação mais recente e ainda válida (não usada,
// não expirada) para esse e-mail, seja o código informado certo ou errado.
// Isso permite contar tentativas erradas contra ELA, e bloquear o código
// (mesmo o correto) depois de várias tentativas, travando a força bruta.
$stmtSolicitacao = $conexao->prepare(
    "SELECT r.id, r.codigo, r.tentativas, u.id AS usuario_id FROM usuarios u
     INNER JOIN recuperacao_senha r ON r.usuario_id = u.id
     WHERE u.email = ? AND r.utilizado = 0 AND r.expiracao > NOW()
     ORDER BY r.id DESC LIMIT 1"
);
$stmtSolicitacao->bind_param("s", $email);
$stmtSolicitacao->execute();
$solicitacao = $stmtSolicitacao->get_result()->fetch_assoc();

if(!$solicitacao) {
    die("Código inválido ou expirado.");
}

if($solicitacao["tentativas"] >= MAX_TENTATIVAS_CODIGO) {
    $bloquear = $conexao->prepare("UPDATE recuperacao_senha SET utilizado = 1 WHERE id = ?");
    $bloquear->bind_param("i", $solicitacao["id"]);
    $bloquear->execute();
    die("Muitas tentativas incorretas. Solicite um novo código.");
}

if(!hash_equals($solicitacao["codigo"], $codigo)) {
    $incrementar = $conexao->prepare("UPDATE recuperacao_senha SET tentativas = tentativas + 1 WHERE id = ?");
    $incrementar->bind_param("i", $solicitacao["id"]);
    $incrementar->execute();
    die("Código inválido ou expirado.");
}

$usuario = ["id" => $solicitacao["usuario_id"]];
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
<footer>
    &copy; <?= date("Y") ?> FacilMed - Todos os direitos reservados.
</footer>
</body>
</html>