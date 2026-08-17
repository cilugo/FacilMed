<!--conteúdo à ser revisado-->
<?php
require_once("conexao.php");
require_once("verificarsessao.php");

// Somente admin pode excluir (ajuste conforme sua regra)
if($tipoUsuario !== 'admin'){
    die("Acesso negado.");
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    die("Requisição inválida.");
}

if(!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
    die("Token de segurança inválido. Recarregue a página e tente novamente.");
}

if(!isset($_POST['id'])){
    die("ID não informado.");
}

$paciente_id = intval($_POST['id']);

// Busca usuario_id correspondente
$sql = $conexao->prepare("SELECT usuario_id FROM pacientes WHERE id = ?");
$sql->bind_param("i", $paciente_id);
$sql->execute();
$sql->bind_result($usuario_id);
if(!$sql->fetch()){
    die("Paciente não encontrado.");
}
$sql->close();

// Exclui paciente (cascade deve remover usuario se configurado; para segurança removemos explicitamente)
$stmt = $conexao->prepare("DELETE FROM pacientes WHERE id = ?");
$stmt->bind_param("i", $paciente_id);
$stmt->execute();
$stmt->close();

$stmt2 = $conexao->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt2->bind_param("i", $usuario_id);
$stmt2->execute();
$stmt2->close();

header("Location: ../paginas/listarpacientes.php");
exit;
?>
