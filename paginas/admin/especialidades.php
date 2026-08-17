<?php
require_once("../../php/conexao.php");
require_once("../../php/verificarsessao.php");

if($tipoUsuario !== 'admin'){
    die("Acesso negado.");
}

$erro = null;
$sucesso = null;

// ==========================================
// CRIAR / EDITAR
// ==========================================
if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar'){
    if(!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
        die("Token de segurança inválido. Recarregue a página e tente novamente.");
    }

    $id = (int) ($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');

    if(empty($nome)){
        $erro = "Informe o nome da especialidade.";
    } else {
        if($id > 0){
            $stmt = $conexao->prepare("UPDATE especialidades SET nome = ? WHERE id = ?");
            $stmt->bind_param("si", $nome, $id);
        } else {
            $stmt = $conexao->prepare("INSERT INTO especialidades (nome) VALUES (?)");
            $stmt->bind_param("s", $nome);
        }
        if($stmt->execute()){
            $sucesso = "Especialidade salva com sucesso!";
        } else {
            $erro = ($conexao->errno === 1062) ? "Já existe uma especialidade com esse nome." : "Erro ao salvar.";
        }
    }
}

// ==========================================
// EXCLUIR
// ==========================================
if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir'){
    if(!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
        die("Token de segurança inválido. Recarregue a página e tente novamente.");
    }
    $id = (int) ($_POST['id'] ?? 0);
    // Médicos com essa especialidade ficam com especialidade_id = NULL (ON DELETE SET NULL)
    $stmt = $conexao->prepare("DELETE FROM especialidades WHERE id = ?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()){
        $sucesso = "Especialidade excluída.";
    } else {
        $erro = "Erro ao excluir.";
    }
}

// Registro sendo editado (se veio ?editar=ID)
$editando = null;
if(isset($_GET['editar'])){
    $idEditar = (int) $_GET['editar'];
    $stmt = $conexao->prepare("SELECT id, nome FROM especialidades WHERE id = ?");
    $stmt->bind_param("i", $idEditar);
    $stmt->execute();
    $res = $stmt->get_result();
    $editando = $res->fetch_assoc();
}

$lista = $conexao->query("SELECT id, nome FROM especialidades ORDER BY nome ASC");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Especialidades | Admin FacilMed</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/admin.css">
</head>
<body>
<main class="container">
    <p><a href="../paineladmin.php">&larr; Voltar ao painel</a></p>
    <h1>Especialidades</h1>

    <?php if($erro): ?><p class="mensagem-erro"><?= htmlspecialchars($erro) ?></p><?php endif; ?>
    <?php if($sucesso): ?><p class="mensagem-sucesso"><?= htmlspecialchars($sucesso) ?></p><?php endif; ?>

    <section class="card-admin">
        <h2><?= $editando ? 'Editar especialidade' : 'Nova especialidade' ?></h2>
        <form method="POST" action="especialidades.php" class="form-admin">
            <input type="hidden" name="acao" value="salvar">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" value="<?= $editando ? (int) $editando['id'] : 0 ?>">
            <label>Nome</label>
            <input type="text" name="nome" value="<?= $editando ? htmlspecialchars($editando['nome']) : '' ?>" required>
            <button type="submit"><?= $editando ? 'Salvar alterações' : 'Adicionar' ?></button>
            <?php if($editando): ?>
                <a href="especialidades.php" class="botao-secundario">Cancelar</a>
            <?php endif; ?>
        </form>
    </section>

    <table border="1" cellpadding="8" cellspacing="0">
        <thead><tr><th>Nome</th><th>Ações</th></tr></thead>
        <tbody>
        <?php while($row = $lista->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['nome']) ?></td>
                <td>
                    <a href="?editar=<?= (int) $row['id'] ?>">Editar</a> |
                    <form action="especialidades.php" method="POST" class="form-inline"
                          onsubmit="return confirm('Excluir esta especialidade? Médicos vinculados ficarão sem especialidade.')">
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
