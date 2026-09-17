<?php
require_once("../php/conexao.php");
require_once("../php/verificarsessao.php");
require_once("../php/validarCRM.php");

// Apenas admin pode editar dados de médicos
exigirPerfil("admin");

if(!isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] !== 'POST'){
    die("Requisição inválida.");
}

$erro = null;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    exigirCsrf();

    $medico_id = intval($_POST['medico_id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $crm = trim($_POST['crm'] ?? '');
    $uf = trim($_POST['uf'] ?? '');
    $especialidadeId = (int) ($_POST['especialidade_id'] ?? 0);
    $statusProfissional = trim($_POST['status_profissional'] ?? 'pendente');
    $valorConsulta = (float) str_replace(',', '.', $_POST['valor_consulta'] ?? '0');

    // A edição passou a validar as MESMAS regras do cadastro. Antes ela
    // gravava qualquer coisa: CRM com letras, UF inexistente, e-mail já
    // usado por outra conta (que só estourava como erro cru do MySQL).
    if($nome === '' || $cpf === '' || $email === ''){
        $erro = "Nome, CPF e e-mail são obrigatórios.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $erro = "E-mail inválido.";
    } elseif(!validarCRM($crm, $uf)){
        $erro = "CRM inválido. Informe apenas números (mínimo 4 dígitos) e uma UF válida.";
    } elseif(!in_array($statusProfissional, ['pendente', 'ativo', 'inativo'], true)){
        $erro = "Status profissional inválido.";
    } elseif($valorConsulta < 0 || $valorConsulta > 99999.99){
        $erro = "Valor da consulta inválido.";
    }

    // E-mail/CPF são UNIQUE em usuarios; CRM+UF é UNIQUE em medicos
    if(!$erro){
        $dup = $conexao->prepare(
            "SELECT 1 FROM usuarios u
             INNER JOIN medicos m ON u.id = m.usuario_id
             WHERE ((u.email = ? OR u.cpf = ?) OR (m.crm = ? AND m.uf = ?)) AND m.id <> ?
             LIMIT 1"
        );
        $dup->bind_param("ssssi", $email, $cpf, $crm, $uf, $medico_id);
        $dup->execute();
        if($dup->get_result()->num_rows > 0){
            $erro = "Já existe outro cadastro com esse e-mail, CPF ou CRM/UF.";
        }
        $dup->close();
    }

    if(!$erro){
        $stmt = $conexao->prepare("UPDATE usuarios u
            INNER JOIN medicos m ON u.id = m.usuario_id
            SET u.nome = ?, u.cpf = ?, u.email = ?, u.telefone = ?, m.crm = ?, m.uf = ?,
                m.especialidade_id = ?, m.status_profissional = ?, m.valor_consulta = ?
            WHERE m.id = ?");
        $stmt->bind_param("ssssssisdi", $nome, $cpf, $email, $telefone, $crm, $uf,
            $especialidadeId, $statusProfissional, $valorConsulta, $medico_id);

        if($stmt->execute()){
            echo "<script>alert('Médico atualizado com sucesso!'); window.location='listarmedicos.php';</script>";
            exit;
        }

        $erro = "Erro ao atualizar.";
    }
}

$medico_id = intval($_GET['id'] ?? $medico_id);
$sql = $conexao->prepare(
    "SELECT u.id AS usuario_id, m.id AS medico_id, u.nome, u.cpf, u.email, u.telefone, m.crm, m.uf,
            m.especialidade_id, m.status_profissional, m.valor_consulta
     FROM usuarios u
     INNER JOIN medicos m ON u.id = m.usuario_id
     WHERE m.id = ?"
);
$sql->bind_param("i", $medico_id);
$sql->execute();
$result = $sql->get_result();
if($result->num_rows === 0) die("Médico não encontrado.");
$row = $result->fetch_assoc();

$especialidades = $conexao->query("SELECT id, nome FROM especialidades ORDER BY nome ASC");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Médico | FacilMed</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <header>
        <h1>FacilMed</h1>
    </header>
    <main class="container">
        <p><a href="listarmedicos.php">&larr; Voltar à lista de médicos</a></p>

        <section class="card-admin">
            <h2>Editar médico</h2>
            <?php if($erro): ?><p class="mensagem-erro"><?= htmlspecialchars($erro) ?></p><?php endif; ?>
            <form method="POST" action="editarmedico.php" class="form-admin">
                <input type="hidden" name="medico_id" value="<?= $row['medico_id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <label>Nome</label>
                <input type="text" name="nome" value="<?= htmlspecialchars($row['nome']) ?>" required>

                <label>CPF</label>
                <input type="text" name="cpf" value="<?= htmlspecialchars($row['cpf']) ?>" required>

                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" required>

                <label>Telefone</label>
                <input type="text" name="telefone" value="<?= htmlspecialchars($row['telefone']) ?>">

                <label>CRM</label>
                <input type="text" name="crm" value="<?= htmlspecialchars($row['crm']) ?>" required>

                <label>UF</label>
                <select name="uf" required>
                    <?php foreach(UFS_BRASIL as $sigla): ?>
                        <option <?= $sigla === $row['uf'] ? 'selected' : '' ?>><?= $sigla ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Especialidade</label>
                <select name="especialidade_id" required>
                    <?php $especialidades->data_seek(0); while($esp = $especialidades->fetch_assoc()): ?>
                        <option value="<?= (int) $esp['id'] ?>" <?= $esp['id'] == $row['especialidade_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($esp['nome']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label>Status profissional</label>
                <select name="status_profissional" required>
                    <?php foreach(['pendente', 'ativo', 'inativo'] as $status): ?>
                        <option value="<?= $status ?>" <?= $status === $row['status_profissional'] ? 'selected' : '' ?>>
                            <?= ucfirst($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Valor da consulta particular (R$)</label>
                <input type="number" name="valor_consulta" step="0.01" min="0"
                       value="<?= number_format((float) $row['valor_consulta'], 2, '.', '') ?>">
                <small>É este valor que o sistema cobra quando o paciente escolhe "Particular".</small>

                <button type="submit">Salvar</button>
                <a href="listarmedicos.php" class="botao-secundario">Cancelar</a>
            </form>
        </section>
    </main>
    <footer>
        &copy; <?= date("Y") ?> FacilMed - Todos os direitos reservados.
    </footer>
</body>
</html>
