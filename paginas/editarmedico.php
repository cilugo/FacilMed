<!--conteúdo à ser revisado-->
<?php
require_once("../php/conexao.php");
require_once("../php/verificarsessao.php");

// Apenas admin pode editar dados de médicos
if($tipoUsuario !== 'admin'){
    die("Acesso negado.");
}

if(!isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] !== 'POST'){
    die("Requisição inválida.");
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
        die("Token de segurança inválido. Recarregue a página e tente novamente.");
    }
    $medico_id = intval($_POST['medico_id']);
    $nome = trim($_POST['nome']);
    $cpf = trim($_POST['cpf']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $crm = trim($_POST['crm']);
    $uf = trim($_POST['uf']);
    $especialidadeId = (int) ($_POST['especialidade_id'] ?? 0);
    $statusProfissional = trim($_POST['status_profissional'] ?? 'pendente');

    $stmt = $conexao->prepare("UPDATE usuarios u
        INNER JOIN medicos m ON u.id = m.usuario_id
        SET u.nome = ?, u.cpf = ?, u.email = ?, u.telefone = ?, m.crm = ?, m.uf = ?, m.especialidade_id = ?, m.status_profissional = ?
        WHERE m.id = ?");
    $stmt->bind_param("ssssssisi", $nome, $cpf, $email, $telefone, $crm, $uf, $especialidadeId, $statusProfissional, $medico_id);

    if($stmt->execute()){
        echo "<script>alert('Médico atualizado com sucesso!'); window.location='listarmedicos.php';</script>";
        exit;
    } else {
        $erro = "Erro ao atualizar.";
    }
}

$medico_id = intval($_GET['id']);
$sql = $conexao->prepare(
    "SELECT u.id AS usuario_id, m.id AS medico_id, u.nome, u.cpf, u.email, u.telefone, m.crm, m.uf,
            m.especialidade_id, m.status_profissional
     FROM usuarios u
     INNER JOIN medicos m ON u.id = m.usuario_id
     WHERE m.id = ?"
);
$sql->bind_param("i", $medico_id);
$sql->execute();
$result = $sql->get_result();
if($result->num_rows === 0) die("Médico não encontrado.");
$row = $result->fetch_assoc();

$especialidades = $conexao->query("SELECT id, nome FROM especialidades ORDER BY nome ASC");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Médico</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<main class="container">
    <h1>Editar Médico</h1>
    <?php if(!empty($erro)) echo "<p style='color:red;'>$erro</p>"; ?>
    <form method="POST" action="editarmedico.php">
        <input type="hidden" name="medico_id" value="<?= $row['medico_id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <label>Nome</label><br>
        <input type="text" name="nome" value="<?= htmlspecialchars($row['nome']) ?>" required><br>
        <label>CPF</label><br>
        <input type="text" name="cpf" value="<?= htmlspecialchars($row['cpf']) ?>" required><br>
        <label>Email</label><br>
        <input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" required><br>
        <label>Telefone</label><br>
        <input type="text" name="telefone" value="<?= htmlspecialchars($row['telefone']) ?>"><br>
        <label>CRM</label><br>
        <input type="text" name="crm" value="<?= htmlspecialchars($row['crm']) ?>" required><br>
        <label>UF</label><br>
        <input type="text" name="uf" value="<?= htmlspecialchars($row['uf']) ?>" required maxlength="2"><br>
        <label>Especialidade</label><br>
        <select name="especialidade_id" required>
            <?php $especialidades->data_seek(0); while($esp = $especialidades->fetch_assoc()): ?>
                <option value="<?= (int) $esp['id'] ?>" <?= $esp['id'] == $row['especialidade_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($esp['nome']) ?>
                </option>
            <?php endwhile; ?>
        </select><br>
        <label>Status profissional</label><br>
        <select name="status_profissional" required>
            <?php foreach(['pendente', 'ativo', 'inativo'] as $status): ?>
                <option value="<?= $status ?>" <?= $status === $row['status_profissional'] ? 'selected' : '' ?>>
                    <?= ucfirst($status) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>
        <button type="submit">Salvar</button>
        <a href="listarmedicos.php">Cancelar</a>
    </form>
</main>
</body>
</html>
