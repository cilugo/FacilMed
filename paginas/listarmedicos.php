<!--conteúdo à ser revisado-->
<?php
require_once("../php/conexao.php");
require_once("../php/verificarsessao.php");

// Apenas admin e médicos podem ver lista de médicos; pacientes podem ver lista pública em outra página
$sql = $conexao->prepare(
    "SELECT u.id, u.nome, u.cpf, u.email, u.telefone, m.crm, m.uf, m.id AS medico_id
     FROM usuarios u
     INNER JOIN medicos m ON u.id = m.usuario_id
     ORDER BY u.nome ASC"
);
$sql->execute();
$result = $sql->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Listar Médicos</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<main class="container">
    <h1>Médicos</h1>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>Nome</th>
                <th>CRM</th>
                <th>UF</th>
                <th>Email</th>
                <th>Telefone</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['nome']) ?></td>
                <td><?= htmlspecialchars($row['crm']) ?></td>
                <td><?= htmlspecialchars($row['uf']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['telefone']) ?></td>
                <td>
                    <a href="editarmedico.php?id=<?= $row['medico_id'] ?>">Editar</a> |
                    <a href="../php/excluirmedico.php?id=<?= $row['medico_id'] ?>"
                       onclick="return confirm('Deseja realmente excluir este médico?')">Excluir</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</main>
</body>
</html>
