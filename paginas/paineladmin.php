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

$totais = $conexao->query(
    "SELECT tipo, COUNT(*) AS total FROM usuarios GROUP BY tipo"
)->fetch_all(MYSQLI_ASSOC);
$totalPorTipo = ['paciente' => 0, 'medico' => 0, 'admin' => 0];
foreach($totais as $linha){
    $totalPorTipo[$linha['tipo']] = (int) $linha['total'];
}

$base = "";
$paginaAtiva = "paineladmin.php";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários | Admin FacilMed</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin-painel.css">
</head>
<body>

<?php include("_sidebar_admin.php"); ?>

<main class="conteudo">
    <header class="topo">
        <div>
            <h1>Usuários</h1>
            <p>Pacientes, médicos e administradores cadastrados no FacilMed.</p>
        </div>
    </header>

    <section class="kpis">
        <div class="kpi-card">
            <h3>Pacientes</h3>
            <strong><?= $totalPorTipo['paciente'] ?></strong>
        </div>
        <div class="kpi-card">
            <h3>Médicos</h3>
            <strong><?= $totalPorTipo['medico'] ?></strong>
        </div>
        <div class="kpi-card">
            <h3>Administradores</h3>
            <strong><?= $totalPorTipo['admin'] ?></strong>
        </div>
    </section>

    <section class="painel">
        <div class="painel-titulo">
            <h2>Todos os usuários</h2>
        </div>
        <table>
            <thead>
                <tr><th>Nome</th><th>Tipo</th><th>Email</th><th>CPF</th><th>Telefone</th><th>Ações</th></tr>
            </thead>
            <tbody>
            <?php while($u = $usuarios->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($u['nome']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($u['tipo'])) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['cpf']) ?></td>
                    <td><?= htmlspecialchars($u['telefone']) ?></td>
                    <td>
                        <?php if($u['paciente_id']): ?>
                            <a href="editarpaciente.php?id=<?= $u['paciente_id'] ?>">Editar</a> |
                            <form action="../php/excluirpaciente.php" method="POST" class="form-inline"
                                  onsubmit="return confirm('Excluir paciente?')">
                                <input type="hidden" name="id" value="<?= $u['paciente_id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <button type="submit" class="link-botao">Excluir</button>
                            </form>
                        <?php endif; ?>
                        <?php if($u['medico_id']): ?>
                            <a href="editarmedico.php?id=<?= $u['medico_id'] ?>">Editar</a> |
                            <form action="../php/excluirmedico.php" method="POST" class="form-inline"
                                  onsubmit="return confirm('Excluir médico?')">
                                <input type="hidden" name="id" value="<?= $u['medico_id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <button type="submit" class="link-botao">Excluir</button>
                            </form>
                        <?php endif; ?>
                        <?php if($u['tipo'] === 'admin'): ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </section>
</main>

<footer class="rodape-painel">&copy; <?= date("Y") ?> FacilMed — Painel administrativo.</footer>

</body>
</html>
