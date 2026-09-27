<?php
require_once("../php/conexao.php");
require_once("../php/verificarsessao.php");

// Apenas admin e médicos podem ver lista de médicos; pacientes podem ver lista pública em outra página
exigirPerfil(['admin', 'medico']);

// Quantos médicos estão esperando aprovação do admin (enquanto 'pendente',
// eles não aparecem para o paciente agendar)
$pendentes = 0;
if($tipoUsuario === 'admin'){
    $res = $conexao->query("SELECT COUNT(*) AS total FROM medicos WHERE status_profissional = 'pendente'");
    $pendentes = (int) ($res->fetch_assoc()['total'] ?? 0);
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

        <?php if($tipoUsuario === 'admin' && $pendentes > 0): ?>
            <p style="background:#fff3cd; color:#856404; padding:10px 15px; border-radius:6px;">
                ⚠️ <strong><?= $pendentes ?></strong>
                <?= $pendentes === 1 ? 'médico aguardando aprovação' : 'médicos aguardando aprovação' ?>.
                Enquanto o cadastro estiver <em>pendente</em>, o médico não aparece para os pacientes agendarem.
            </p>
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
                        <td>
                            <?php
                                $cores = [
                                    'ativo'    => '#1b7f3b',
                                    'pendente' => '#b8860b',
                                    'inativo'  => '#8b0000',
                                ];
                                $cor = $cores[$row['status_profissional']] ?? '#444';
                            ?>
                            <strong style="color:<?= $cor ?>">
                                <?= htmlspecialchars(ucfirst($row['status_profissional'])) ?>
                            </strong>
                        </td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['telefone']) ?></td>
                        <?php if($tipoUsuario === 'admin'): ?>
                            <td>
                                <?php if($row['status_profissional'] !== 'ativo'): ?>
                                    <form action="../php/statusmedico.php" method="POST" class="form-inline">
                                        <input type="hidden" name="id" value="<?= $row['medico_id'] ?>">
                                        <input type="hidden" name="status" value="ativo">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <button type="submit" class="link-botao">
                                            <?= $row['status_profissional'] === 'pendente' ? 'Aprovar' : 'Reativar' ?>
                                        </button>
                                    </form> |
                                <?php else: ?>
                                    <form action="../php/statusmedico.php" method="POST" class="form-inline"
                                          onsubmit="return confirm('Suspender este médico? Ele deixa de aparecer para agendamento.')">
                                        <input type="hidden" name="id" value="<?= $row['medico_id'] ?>">
                                        <input type="hidden" name="status" value="inativo">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <button type="submit" class="link-botao">Suspender</button>
                                    </form> |
                                <?php endif; ?>
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
