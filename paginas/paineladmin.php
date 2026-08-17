<!--conteúdo à ser revisado-->
<?php
require_once("../php/conexao.php");
require_once("../php/verificarsessao.php");

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
    <nav class="admin-nav">
        <a href="admin/especialidades.php">Especialidades</a>
        <a href="admin/locais.php">Locais</a>
        <a href="admin/convenios.php">Convênios</a>
        <a href="admin/planos.php">Planos</a>
    </nav>
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
                        <a href="editarpaciente.php?id=<?= $u['paciente_id'] ?>">Editar Paciente</a> |
                        <form action="../php/excluirpaciente.php" method="POST" class="form-inline"
                              onsubmit="return confirm('Excluir paciente?')">
                            <input type="hidden" name="id" value="<?= $u['paciente_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <button type="submit" class="link-botao">Excluir Paciente</button>
                        </form>
                    <?php endif; ?>
                    <?php if($u['medico_id']): ?>
                        <?php if($u['paciente_id']) echo "<br>"; ?>
                        <a href="editarmedico.php?id=<?= $u['medico_id'] ?>">Editar Médico</a> |
                        <form action="../php/excluirmedico.php" method="POST" class="form-inline"
                              onsubmit="return confirm('Excluir médico?')">
                            <input type="hidden" name="id" value="<?= $u['medico_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <button type="submit" class="link-botao">Excluir Médico</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</main>
</body>
</html>
