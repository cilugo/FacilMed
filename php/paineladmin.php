<!--conteúdo à ser revisado-->
<?php
require_once("conexao.php");
require_once("validarsessao.php");

// Apenas admin
if($tipoUsuario !== 'admin'){
    die("Acesso negado.");
}

// Busca usuários (pacientes e médicos)
$usuarios = $conexao->query(
    "SELECT u.id, u.nome, u.email, u.cpf, u.telefone, u.tipo,
     p.id AS paciente_id, m.id AS medico_id
     FROM usuarios u
     LEFT JOIN pacientes p ON u.id = p.usuario_id
     LEFT JOIN medicos m ON u.id = m.usuario_id
     ORDER BY u.tipo, u.nome"
);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Administrativo</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<main class="container">
    <h1>Painel Administrativo</h1>
    <h2>Usuários</h2>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr><th>Nome</th><th>Tipo</th><th>Email</th><th>CPF</th><th>Telefone</th><th>Ações</th></tr>
        </thead>
        <tbody>
        <?php while($u = $usuarios->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($u['nome']) ?></td>
                <td><?= htmlspecialchars($u['tipo']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['cpf']) ?></td>
                <td><?= htmlspecialchars($u['telefone']) ?></td>
                <td>
                    <?php if($u['paciente_id']): ?>
                        <a href="editarPaciente.php?id=<?= $u['paciente_id'] ?>">Editar Paciente</a> |
                        <a href="excluirPaciente.php?id=<?= $u['paciente_id'] ?>"
                           onclick="return confirm('Excluir paciente?')">Excluir Paciente</a>
                    <?php endif; ?>
                    <?php if($u['medico_id']): ?>
                        <?php if($u['paciente_id']) echo "<br>"; ?>
                        <a href="editarMedico.php?id=<?= $u['medico_id'] ?>">Editar Médico</a> |
                        <a href="excluirMedico.php?id=<?= $u['medico_id'] ?>"
                           onclick="return confirm('Excluir médico?')">Excluir Médico</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</main>
</body>
</html>
