<!--conteúdo à ser revisado-->
<?php
require_once("../php/conexao.php");
require_once("../php/verificarsessao.php"); // garante sessão ativa

// Apenas admins e médicos podem ver todos os pacientes; pacientes só veem seu próprio perfil
if($tipoUsuario === 'paciente'){
    // busca apenas o paciente logado
    $sql = $conexao->prepare(
        "SELECT u.id, u.nome, u.cpf, u.email, u.telefone, p.data_nascimento, p.id AS paciente_id
         FROM usuarios u
         INNER JOIN pacientes p ON u.id = p.usuario_id
         WHERE u.id = ?"
    );
    $sql->bind_param("i", $idUsuario);
} else {
    // admin ou medico vê todos
    $sql = $conexao->prepare(
        "SELECT u.id, u.nome, u.cpf, u.email, u.telefone, p.data_nascimento, p.id AS paciente_id
         FROM usuarios u
         INNER JOIN pacientes p ON u.id = p.usuario_id
         ORDER BY u.nome ASC"
    );
}

$sql->execute();
$result = $sql->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Listar Pacientes</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include_once("../html/partials/header.php"); ?>
<main class="container">
    <h1>Pacientes</h1>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>Nome</th>
                <th>CPF</th>
                <th>Email</th>
                <th>Telefone</th>
                <th>Data Nascimento</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['nome']) ?></td>
                <td><?= htmlspecialchars($row['cpf']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['telefone']) ?></td>
                <td><?= htmlspecialchars($row['data_nascimento']) ?></td>
                <td>
                    <a href="editarpaciente.php?id=<?= $row['paciente_id'] ?>">Editar</a>
                    <?php if($tipoUsuario !== 'paciente'): ?>
                        |
                        <a href="../php/excluirpaciente.php?id=<?= $row['paciente_id'] ?>"
                           onclick="return confirm('Deseja realmente excluir este paciente?')">Excluir</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</main>
</body>
</html>
