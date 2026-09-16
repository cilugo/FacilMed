<?php
require_once("../php/conexao.php");
require_once("../php/verificarsessao.php");

// Apenas admin e médicos podem ver lista de médicos; pacientes podem ver lista pública em outra página
if(!in_array($tipoUsuario, ['admin', 'medico'], true)){
    die("Acesso negado.");
}

$sql = $conexao->prepare(
    "SELECT u.id, u.nome, u.cpf, u.email, u.telefone, m.crm, m.uf, m.id AS medico_id,
            e.nome AS especialidade, m.status_profissional
     FROM usuarios u
     INNER JOIN medicos m ON u.id = m.usuario_id
     LEFT JOIN especialidades e ON e.id = m.especialidade_id
     ORDER BY u.nome ASC"
);
$sql->execute();
$result = $sql->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Médicos | FacilMed</title>
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
            <div class="painel-titulo" style="display:flex; justify-content:space-between; align-items:center;">
                <h2>Médicos cadastrados</h2>
                <?php if($tipoUsuario === 'admin'): ?>
                    <a href="cadastromedico.php" class="botao">+ Novo médico</a>
                <?php endif; ?>
            </div>

            <table border="1" cellpadding="8" cellspacing="0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CRM</th>
                        <th>UF</th>
                        <th>Especialidade</th>
                        <th>Status</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <?php if($tipoUsuario === 'admin'): ?><th>Ações</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if($result->num_rows === 0): ?>
                    <tr><td colspan="8">Nenhum médico cadastrado ainda.</td></tr>
                <?php endif; ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nome']) ?></td>
                        <td><?= htmlspecialchars($row['crm']) ?></td>
                        <td><?= htmlspecialchars($row['uf']) ?></td>
                        <td><?= htmlspecialchars($row['especialidade'] ?? 'Não informada') ?></td>
                        <td><?= htmlspecialchars(ucfirst($row['status_profissional'])) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['telefone']) ?></td>
                        <?php if($tipoUsuario === 'admin'): ?>
                            <td>
                                <a href="editarmedico.php?id=<?= $row['medico_id'] ?>">Editar</a> |
                                <form action="../php/excluirmedico.php" method="POST" class="form-inline"
                                      onsubmit="return confirm('Deseja realmente excluir este médico?')">
                                    <input type="hidden" name="id" value="<?= $row['medico_id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <button type="submit" class="link-botao">Excluir</button>
                                </form>
                            </td>
                        <?php endif; ?>
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
