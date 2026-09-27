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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pacientes | FacilMed</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <header>
        <h1>FacilMed</h1>
    </header>
    <main class="container">
        <?php if($tipoUsuario === 'admin'): ?>
            <p><a href="paineladmin.php">&larr; Voltar ao painel</a></p>
        <?php endif; ?>

        <section class="card-admin" style="max-width:none;">
            <h2>Pacientes</h2>
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
                <?php if($result->num_rows === 0): ?>
                    <tr><td colspan="6">Nenhum paciente encontrado.</td></tr>
                <?php endif; ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nome']) ?></td>
                        <td><?= htmlspecialchars($row['cpf']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['telefone']) ?></td>
                        <td><?= $row['data_nascimento'] ? date("d/m/Y", strtotime($row['data_nascimento'])) : '—' ?></td>
                        <td>
                            <a href="editarpaciente.php?id=<?= $row['paciente_id'] ?>">Editar</a>
                            <?php if($tipoUsuario !== 'paciente'): ?>
                                |
                                <form action="../php/excluirpaciente.php" method="POST" class="form-inline"
                                      onsubmit="return confirm('Deseja realmente excluir este paciente?')">
                                    <input type="hidden" name="id" value="<?= $row['paciente_id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <button type="submit" class="link-botao">Excluir</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </main>
    <footer>
        &copy; <?= date("Y") ?> FacilMed - Todos os direitos reservados.
    </footer>
</body>
</html>
