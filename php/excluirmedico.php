<!--conteúdo à ser revisado-->
<?php
require_once("conexao.php");
require_once("validarsessao.php");

// Apenas admin pode excluir
if($tipoUsuario !== 'admin'){
    die("Acesso negado.");
}

if(!isset($_GET['id'])){
    die("ID não informado.");
}

$medico_id = intval($_GET['id']);

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
$stmt->execute();
$stmt->close();

$stmt2 = $conexao->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt2->bind_param("i", $usuario_id);
$stmt2->execute();
$stmt2->close();

header("Location: listarMedicos.php");
exit;
?>
