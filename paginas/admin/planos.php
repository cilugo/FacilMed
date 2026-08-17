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
    $convenioId = (int) ($_POST['convenio_id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $valor = (float) str_replace(',', '.', $_POST['valor'] ?? '0');
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if($convenioId <= 0 || empty($nome)){
        $erro = "Selecione o convênio e informe o nome do plano.";
    } else {
        if($id > 0){
            $stmt = $conexao->prepare("UPDATE planos SET convenio_id=?, nome=?, descricao=?, valor=?, ativo=? WHERE id=?");
            $stmt->bind_param("issdii", $convenioId, $nome, $descricao, $valor, $ativo, $id);
        } else {
            $stmt = $conexao->prepare("INSERT INTO planos (convenio_id, nome, descricao, valor, ativo) VALUES (?,?,?,?,?)");
            $stmt->bind_param("issdi", $convenioId, $nome, $descricao, $valor, $ativo);
        }
        $sucesso = $stmt->execute() ? "Plano salvo com sucesso!" : "Erro ao salvar.";
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir'){
    if(!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
        die("Token de segurança inválido. Recarregue a página e tente novamente.");
    }
    $id = (int) ($_POST['id'] ?? 0);
    // Consultas que usavam esse plano ficam com plano_id = NULL (ON DELETE SET NULL)
    $stmt = $conexao->prepare("DELETE FROM planos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $sucesso = $stmt->execute() ? "Plano excluído." : "Erro ao excluir.";
}

$editando = null;
if(isset($_GET['editar'])){
    $idEditar = (int) $_GET['editar'];
    $stmt = $conexao->prepare("SELECT * FROM planos WHERE id = ?");
    $stmt->bind_param("i", $idEditar);
    $stmt->execute();
    $editando = $stmt->get_result()->fetch_assoc();
}

$convenios = $conexao->query("SELECT id, nome FROM convenios ORDER BY nome ASC");

// Filtro opcional por convênio (link vindo de convenios.php)
$filtroConvenio = isset($_GET['convenio']) ? (int) $_GET['convenio'] : 0;

$sqlLista = "SELECT p.*, c.nome AS convenio_nome FROM planos p INNER JOIN convenios c ON c.id = p.convenio_id";
if($filtroConvenio > 0){
    $sqlLista .= " WHERE p.convenio_id = ?";
    $stmtLista = $conexao->prepare($sqlLista . " ORDER BY p.nome ASC");
    $stmtLista->bind_param("i", $filtroConvenio);
    $stmtLista->execute();
    $lista = $stmtLista->get_result();
} else {
    $lista = $conexao->query($sqlLista . " ORDER BY c.nome, p.nome ASC");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Planos | Admin FacilMed</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/admin.css">
</head>
<body>
<main class="container">
    <p><a href="../paineladmin.php">&larr; Voltar ao painel</a> | <a href="convenios.php">Convênios</a></p>
    <h1>Planos<?php if($filtroConvenio): $convenios->data_seek(0); foreach($convenios as $c){ if($c['id'] == $filtroConvenio) echo " — " . htmlspecialchars($c['nome']); } endif; ?></h1>

    <?php if($erro): ?><p class="mensagem-erro"><?= htmlspecialchars($erro) ?></p><?php endif; ?>
    <?php if($sucesso): ?><p class="mensagem-sucesso"><?= htmlspecialchars($sucesso) ?></p><?php endif; ?>

    <section class="card-admin">
        <h2><?= $editando ? 'Editar plano' : 'Novo plano' ?></h2>
        <form method="POST" action="planos.php" class="form-admin">
            <input type="hidden" name="acao" value="salvar">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" value="<?= $editando ? (int) $editando['id'] : 0 ?>">

            <label>Convênio</label>
            <select name="convenio_id" required>
                <option value="">Selecione</option>
                <?php $convenios->data_seek(0); while($c = $convenios->fetch_assoc()): ?>
                    <option value="<?= (int) $c['id'] ?>"
                        <?= (($editando['convenio_id'] ?? $filtroConvenio) == $c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nome']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Nome do plano</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($editando['nome'] ?? '') ?>" required>

            <label>Descrição</label>
            <textarea name="descricao"><?= htmlspecialchars($editando['descricao'] ?? '') ?></textarea>

            <label>Valor (R$)</label>
            <input type="number" step="0.01" min="0" name="valor" value="<?= htmlspecialchars($editando['valor'] ?? '0.00') ?>">

            <label class="checkbox-inline">
                <input type="checkbox" name="ativo" <?= (!$editando || $editando['ativo']) ? 'checked' : '' ?>>
                Ativo (visível no agendamento)
            </label>

            <button type="submit"><?= $editando ? 'Salvar alterações' : 'Adicionar' ?></button>
            <?php if($editando): ?>
                <a href="planos.php" class="botao-secundario">Cancelar</a>
            <?php endif; ?>
        </form>
    </section>

    <table border="1" cellpadding="8" cellspacing="0">
        <thead><tr><th>Convênio</th><th>Plano</th><th>Valor</th><th>Ativo</th><th>Ações</th></tr></thead>
        <tbody>
        <?php while($row = $lista->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['convenio_nome']) ?></td>
                <td><?= htmlspecialchars($row['nome']) ?></td>
                <td>R$ <?= number_format((float) $row['valor'], 2, ',', '.') ?></td>
                <td><?= $row['ativo'] ? 'Sim' : 'Não' ?></td>
                <td>
                    <a href="?editar=<?= (int) $row['id'] ?>">Editar</a> |
                    <form action="planos.php" method="POST" class="form-inline" onsubmit="return confirm('Excluir este plano?')">
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
