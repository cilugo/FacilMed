<?php
require_once("conexao.php");

if($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acesso inválido.");
}

$id = (int) ($_POST["id"] ?? 0);
$codigo = trim($_POST["codigo"] ?? "");
$senha = $_POST["senha"] ?? "";
$confirmarSenha = $_POST["confirmarSenha"] ?? "";

if($id <= 0 || empty($codigo)) {
    die("Requisição inválida.");
}

// Verifica tamanho (bcrypt ignora além de 72 bytes, então esse é o teto real)
if(strlen($senha) < 8 || strlen($senha) > 72) {
    die("A senha deve possuir no mínimo 8 caracteres.");
}

// Verifica confirmação
if($senha !== $confirmarSenha) {
    die("As senhas não coincidem.");
}

// Confirma novamente o código (e busca o id da recuperação para marcar como usado)
$stmt = $conexao->prepare(
    "SELECT id FROM recuperacao_senha
     WHERE usuario_id = ? AND codigo = ? AND utilizado = 0 AND expiracao > NOW()
     ORDER BY id DESC LIMIT 1"
);
$stmt->bind_param("is", $id, $codigo);
$stmt->execute();
$resultado = $stmt->get_result();

if($resultado->num_rows === 0) {
    die("Código inválido ou expirado.");
}

$recuperacao = $resultado->fetch_assoc();

// Criptografa a senha
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// Atualiza a senha e marca o código como utilizado (evita reuso)
$atualizar = $conexao->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
$atualizar->bind_param("si", $senhaHash, $id);

if($atualizar->execute()) {
    $marcar = $conexao->prepare("UPDATE recuperacao_senha SET utilizado = 1 WHERE id = ?");
    $marcar->bind_param("i", $recuperacao["id"]);
    $marcar->execute();
    echo "<script>
        alert('Senha alterada com sucesso!');
        window.location='../paginas/login.html';
    </script>";
} else {
    echo "Erro ao alterar a senha.";
}
?>