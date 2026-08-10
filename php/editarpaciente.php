<!--conteúdo à ser revisado-->
<?php
require_once("conexao.php");
require_once("validarsessao.php");

// Verifica id do paciente
if(!isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] !== 'POST'){
    die("Requisição inválida.");
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    // Recebe dados do formulário
    $paciente_id = intval($_POST['paciente_id']);
    $nome = trim($_POST['nome']);
    $cpf = trim($_POST['cpf']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $dataNascimento = $_POST['data_nascimento'];

    // Atualiza tabela usuarios
    $stmt = $conexao->prepare("UPDATE usuarios u
        INNER JOIN pacientes p ON u.id = p.usuario_id
        SET u.nome = ?, u.cpf = ?, u.email = ?, u.telefone = ?, p.data_nascimento = ?
        WHERE p.id = ?");
    $stmt->bind_param("sssssi", $nome, $cpf, $email, $telefone, $dataNascimento, $paciente_id);

    if($stmt->execute()){
        echo "<script>alert('Paciente atualizado com sucesso!'); window.location='listarPacientes.php';</script>";
        exit;
    } else {
        $erro = "Erro ao atualizar.";
    }
}

// Carrega dados para exibir no formulário
$paciente_id = intval($_GET['id']);
$sql = $conexao->prepare(
    "SELECT u.id AS usuario_id, p.id AS paciente_id, u.nome, u.cpf, u.email, u.telefone, p.data_nascimento
     FROM usuarios u
     INNER JOIN pacientes p ON u.id = p.usuario_id
     WHERE p.id = ?"
);
$sql->bind_param("i", $paciente_id);
$sql->execute();
$result = $sql->get_result();
if($result->num_rows === 0){
    die("Paciente não encontrado.");
}
$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Paciente</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<main class="container">
    <h1>Editar Paciente</h1>
    <?php if(!empty($erro)) echo "<p style='color:red;'>$erro</p>"; ?>
    <form method="POST" action="editarPaciente.php">
        <input type="hidden" name="paciente_id" value="<?= $row['paciente_id'] ?>">
        <label>Nome</label><br>
        <input type="text" name="nome" value="<?= htmlspecialchars($row['nome']) ?>" required><br>
        <label>CPF</label><br>
        <input type="text" name="cpf" value="<?= htmlspecialchars($row['cpf']) ?>" required><br>
        <label>Email</label><br>
        <input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" required><br>
        <label>Telefone</label><br>
        <input type="text" name="telefone" value="<?= htmlspecialchars($row['telefone']) ?>"><br>
        <label>Data de Nascimento</label><br>
        <input type="date" name="data_nascimento" value="<?= htmlspecialchars($row['data_nascimento']) ?>"><br><br>
        <button type="submit">Salvar</button>
        <a href="listarPacientes.php">Cancelar</a>
    </form>
</main>
</body>
</html>
