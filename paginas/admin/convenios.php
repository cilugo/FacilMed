<?php
require_once("../../php/conexao.php");
require_once("../../php/verificarsessao.php");

if($tipoUsuario !== 'admin'){
    die("Acesso negado.");
}

$erro = null;
$sucesso = null;

if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar'){
    if(!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
        die("Token de segurança inválido. Recarregue a página e tente novamente.");
    }

    $id = (int) ($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if(empty($nome)){
        $erro = "Informe o nome do convênio.";
    } else {
        if($id > 0){
            $stmt = $conexao->prepare("UPDATE convenios SET nome=?, descricao=?, ativo=? WHERE id=?");
            $stmt->bind_param("ssii", $nome, $descricao, $ativo, $id);
        } else {
            $stmt = $conexao->prepare("INSERT INTO convenios (nome, descricao, ativo) VALUES (?,?,?)");
            $stmt->bind_param("ssi", $nome, $descricao, $ativo);
        }
        $sucesso = $stmt->execute() ? "Convênio salvo com sucesso!" : "Erro ao salvar.";
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir'){
    if(!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
        die("Token de segurança inválido. Recarregue a página e tente novamente.");
    }
    $id = (int) ($_POST['id'] ?? 0);
    // Excluir um convênio remove em cascata os planos dele (ON DELETE CASCADE);
    // consultas que usavam esses planos ficam com plano_id/convenio_id = NULL.
    $stmt = $conexao->prepare("DELETE FROM convenios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $sucesso = $stmt->execute() ? "Convênio excluído (planos vinculados também foram removidos)." : "Erro ao excluir.";
}

$editando = null;
if(isset($_GET['editar'])){
    $idEditar = (int) $_GET['editar'];
    $stmt = $conexao->prepare("SELECT * FROM convenios WHERE id = ?");
    $stmt->bind_param("i", $idEditar);
    $stmt->execute();
    $editando = $stmt->get_result()->fetch_assoc();
}

$lista = $conexao->query(
    "SELECT c.*, (SELECT COUNT(*) FROM planos p WHERE p.convenio_id = c.id) AS total_planos
     FROM convenios c ORDER BY c.nome ASC"
);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Convênios | Admin FacilMed</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/admin.css">
</head>
<body>
<main class="container">
    <p><a href="../paineladmin.php">&larr; Voltar ao painel</a></p>
    <h1>Convênios</h1>

    <?php if($erro): ?><p class="mensagem-erro"><?= htmlspecialchars($erro) ?></p><?php endif; ?>
    <?php if($sucesso): ?><p class="mensagem-sucesso"><?= htmlspecialchars($sucesso) ?></p><?php endif; ?>

    <section class="card-admin">
        <h2><?= $editando ? 'Editar convênio' : 'Novo convênio' ?></h2>
        <form method="POST" action="convenios.php" class="form-admin">
            <input type="hidden" name="acao" value="salvar">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" value="<?= $editando ? (int) $editando['id'] : 0 ?>">

            <label>Nome</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($editando['nome'] ?? '') ?>" required>

            <label>Descrição</label>
            <textarea name="descricao"><?= htmlspecialchars($editando['descricao'] ?? '') ?></textarea>

            <label class="checkbox-inline">
                <input type="checkbox" name="ativo" <?= (!$editando || $editando['ativo']) ? 'checked' : '' ?>>
                Ativo (visível no agendamento)
            </label>

            <button type="submit"><?= $editando ? 'Salvar alterações' : 'Adicionar' ?></button>
            <?php if($editando): ?>
                <a href="convenios.php" class="botao-secundario">Cancelar</a>
            <?php endif; ?>
        </form>
    </section>

    <table border="1" cellpadding="8" cellspacing="0">
        <thead><tr><th>Nome</th><th>Descrição</th><th>Planos</th><th>Ativo</th><th>Ações</th></tr></thead>
        <tbody>
        <?php while($row = $lista->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['nome']) ?></td>
                <td><?= htmlspecialchars($row['descricao']) ?></td>
                <td><?= (int) $row['total_planos'] ?> — <a href="planos.php?convenio=<?= (int) $row['id'] ?>">ver planos</a></td>
                <td><?= $row['ativo'] ? 'Sim' : 'Não' ?></td>
                <td>
                    <a href="?editar=<?= (int) $row['id'] ?>">Editar</a> |
                    <form action="convenios.php" method="POST" class="form-inline"
                          onsubmit="return confirm('Excluir este convênio? Os planos vinculados também serão excluídos.')">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <button type="submit" class="link-botao">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</main>
</body>
</html>
