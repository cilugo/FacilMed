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
    $tipo = $_POST['tipo'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $endereco = trim($_POST['endereco'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if(empty($nome) || !in_array($tipo, ['hospital', 'clinica'], true) || !in_array($categoria, ['publico', 'privado'], true)){
        $erro = "Preencha nome, tipo e categoria corretamente.";
    } else {
        if($id > 0){
            $stmt = $conexao->prepare(
                "UPDATE locais SET nome=?, tipo=?, categoria=?, endereco=?, cidade=?, bairro=?, estado=?, telefone=?, ativo=? WHERE id=?"
            );
            $stmt->bind_param("ssssssssii", $nome, $tipo, $categoria, $endereco, $cidade, $bairro, $estado, $telefone, $ativo, $id);
        } else {
            $stmt = $conexao->prepare(
                "INSERT INTO locais (nome, tipo, categoria, endereco, cidade, bairro, estado, telefone, ativo) VALUES (?,?,?,?,?,?,?,?,?)"
            );
            $stmt->bind_param("ssssssssi", $nome, $tipo, $categoria, $endereco, $cidade, $bairro, $estado, $telefone, $ativo);
        }
        $sucesso = $stmt->execute() ? "Local salvo com sucesso!" : "Erro ao salvar.";
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir'){
    if(!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
        die("Token de segurança inválido. Recarregue a página e tente novamente.");
    }
    $id = (int) ($_POST['id'] ?? 0);
    // Consultas com esse local ficam com local_id = NULL (ON DELETE SET NULL)
    $stmt = $conexao->prepare("DELETE FROM locais WHERE id = ?");
    $stmt->bind_param("i", $id);
    $sucesso = $stmt->execute() ? "Local excluído." : "Erro ao excluir.";
}

$editando = null;
if(isset($_GET['editar'])){
    $idEditar = (int) $_GET['editar'];
    $stmt = $conexao->prepare("SELECT * FROM locais WHERE id = ?");
    $stmt->bind_param("i", $idEditar);
    $stmt->execute();
    $editando = $stmt->get_result()->fetch_assoc();
}

$lista = $conexao->query("SELECT * FROM locais ORDER BY nome ASC");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Locais | Admin FacilMed</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/admin.css">
</head>
<body>
<main class="container">
    <p><a href="../paineladmin.php">&larr; Voltar ao painel</a></p>
    <h1>Locais (hospitais e clínicas)</h1>

    <?php if($erro): ?><p class="mensagem-erro"><?= htmlspecialchars($erro) ?></p><?php endif; ?>
    <?php if($sucesso): ?><p class="mensagem-sucesso"><?= htmlspecialchars($sucesso) ?></p><?php endif; ?>

    <section class="card-admin">
        <h2><?= $editando ? 'Editar local' : 'Novo local' ?></h2>
        <form method="POST" action="locais.php" class="form-admin">
            <input type="hidden" name="acao" value="salvar">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" value="<?= $editando ? (int) $editando['id'] : 0 ?>">

            <label>Nome</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($editando['nome'] ?? '') ?>" required>

            <label>Tipo</label>
            <select name="tipo" required>
                <option value="hospital" <?= ($editando['tipo'] ?? '') === 'hospital' ? 'selected' : '' ?>>Hospital</option>
                <option value="clinica" <?= ($editando['tipo'] ?? '') === 'clinica' ? 'selected' : '' ?>>Clínica</option>
            </select>

            <label>Categoria</label>
            <select name="categoria" required>
                <option value="publico" <?= ($editando['categoria'] ?? '') === 'publico' ? 'selected' : '' ?>>Público</option>
                <option value="privado" <?= ($editando['categoria'] ?? '') === 'privado' ? 'selected' : '' ?>>Privado</option>
            </select>

            <label>Endereço</label>
            <input type="text" name="endereco" value="<?= htmlspecialchars($editando['endereco'] ?? '') ?>">

            <label>Cidade</label>
            <input type="text" name="cidade" value="<?= htmlspecialchars($editando['cidade'] ?? '') ?>">

            <label>Bairro</label>
            <input type="text" name="bairro" value="<?= htmlspecialchars($editando['bairro'] ?? '') ?>">

            <label>Estado (UF)</label>
            <input type="text" name="estado" maxlength="2" value="<?= htmlspecialchars($editando['estado'] ?? '') ?>">

            <label>Telefone</label>
            <input type="text" name="telefone" value="<?= htmlspecialchars($editando['telefone'] ?? '') ?>">

            <label class="checkbox-inline">
                <input type="checkbox" name="ativo" <?= (!$editando || $editando['ativo']) ? 'checked' : '' ?>>
                Ativo (visível no agendamento)
            </label>

            <button type="submit"><?= $editando ? 'Salvar alterações' : 'Adicionar' ?></button>
            <?php if($editando): ?>
                <a href="locais.php" class="botao-secundario">Cancelar</a>
            <?php endif; ?>
        </form>
    </section>

    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr><th>Nome</th><th>Tipo</th><th>Categoria</th><th>Cidade</th><th>Ativo</th><th>Ações</th></tr>
        </thead>
        <tbody>
        <?php while($row = $lista->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['nome']) ?></td>
                <td><?= $row['tipo'] === 'hospital' ? 'Hospital' : 'Clínica' ?></td>
                <td><?= $row['categoria'] === 'publico' ? 'Público' : 'Privado' ?></td>
                <td><?= htmlspecialchars($row['cidade']) ?></td>
                <td><?= $row['ativo'] ? 'Sim' : 'Não' ?></td>
                <td>
                    <a href="?editar=<?= (int) $row['id'] ?>">Editar</a> |
                    <form action="locais.php" method="POST" class="form-inline" onsubmit="return confirm('Excluir este local?')">
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
