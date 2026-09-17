<?php
require_once("conexao.php");
require_once("verificarsessao.php");

// Apenas admin pode excluir
exigirPerfil("admin");

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    die("Requisição inválida.");
}

if(!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
    die("Token de segurança inválido. Recarregue a página e tente novamente.");
}

if(!isset($_POST['id'])){
    die("ID não informado.");
}

$medico_id = intval($_POST['id']);

// Busca usuario_id
$sql = $conexao->prepare("SELECT usuario_id FROM medicos WHERE id = ?");
$sql->bind_param("i", $medico_id);
$sql->execute();
$sql->bind_result($usuario_id);
if(!$sql->fetch()){
    die("Médico não encontrado.");
}
$sql->close();

// Exclui medico e usuario
$stmt = $conexao->prepare("DELETE FROM medicos WHERE id = ?");
$stmt->bind_param("i", $medico_id);

if(!$stmt->execute()){
    // Bloqueado pela FK de consultas (ON DELETE RESTRICT): médico com
    // histórico de consultas não pode ser excluído, para preservar o histórico.
    die("Não é possível excluir: este médico possui consultas registradas. "
        . "Em vez de excluir, edite o médico e marque o status profissional como \"inativo\".");
}
$stmt->close();

$stmt2 = $conexao->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt2->bind_param("i", $usuario_id);
$stmt2->execute();
$stmt2->close();

header("Location: ../paginas/listarmedicos.php");
exit;
?>
