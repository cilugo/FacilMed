<?php
/*=========================================================
                MEU PERFIL — MÉDICO
                    FacilMed

    Antes esta página só dizia "Esta seção ainda está em
    construção", mesmo estando no menu.

    Agora o médico:
      - vê os próprios dados de registro (CRM, UF e
        especialidade são só leitura: quem muda é o admin);
      - edita nome, telefone, anos de atuação e o valor da
        consulta particular;
      - troca a própria senha.
=========================================================*/

require_once(__DIR__ . "/../conexao.php");
require_once(__DIR__ . "/../verificarsessao.php");
require_once(__DIR__ . "/../lib/helpers.php");

exigirPerfil("medico");

$paginaAtiva = "perfil.php";
$usuario_id  = (int) $_SESSION["id"];
$medico_id   = getMedicoIdByUsuario($conexao, $usuario_id);

if ($medico_id === null) {
    die("Perfil de médico não encontrado para esta conta.");
}

$erro = null;
$sucesso = null;

// ==========================================
// SALVAR DADOS DO PERFIL
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["acao"] ?? "") === "dados") {
    exigirCsrf();

    $nome          = trim($_POST["nome"] ?? "");
    $telefone      = trim($_POST["telefone"] ?? "");
    $anosAtuacao   = (int) ($_POST["anos_atuacao"] ?? 0);
    $valorConsulta = (float) str_replace(",", ".", $_POST["valor_consulta"] ?? "0");

    if (mb_strlen($nome) < 3) {
        $erro = "Informe o nome completo.";
    } elseif ($anosAtuacao < 0 || $anosAtuacao > 70) {
        $erro = "Anos de atuação deve ser um número entre 0 e 70.";
    } elseif ($valorConsulta < 0 || $valorConsulta > 99999.99) {
        $erro = "Valor da consulta inválido.";
    } else {
        // Os dois UPDATEs precisam acontecer juntos: se o segundo falhar, o
        // primeiro é desfeito em vez de salvar o perfil pela metade.
        $conexao->begin_transaction();

        $u = $conexao->prepare("UPDATE usuarios SET nome = ?, telefone = ? WHERE id = ?");
        $u->bind_param("ssi", $nome, $telefone, $usuario_id);
        $okUsuario = $u->execute();
        $u->close();

        $m = $conexao->prepare("UPDATE medicos SET anos_atuacao = ?, valor_consulta = ? WHERE id = ?");
        $m->bind_param("idi", $anosAtuacao, $valorConsulta, $medico_id);
        $okMedico = $m->execute();
        $m->close();

        if ($okUsuario && $okMedico) {
            $conexao->commit();
            // Atualiza o nome que aparece no cabeçalho sem precisar relogar
            $_SESSION["nome"] = $nome;
            $sucesso = "Perfil atualizado com sucesso.";
        } else {
            $conexao->rollback();
            $erro = "Não foi possível salvar o perfil. Tente novamente.";
        }
    }
}

// ==========================================
// TROCAR A SENHA
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["acao"] ?? "") === "senha") {
    exigirCsrf();

    $senhaAtual     = $_POST["senha_atual"] ?? "";
    $novaSenha      = $_POST["nova_senha"] ?? "";
    $confirmarSenha = $_POST["confirmar_senha"] ?? "";

    // Confere a senha atual antes de trocar: se alguém pegar a máquina com a
    // sessão aberta, não consegue mudar a senha sem saber a antiga.
    $s = $conexao->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $s->bind_param("i", $usuario_id);
    $s->execute();
    $s->bind_result($hashAtual);
    $s->fetch();
    $s->close();

    if (!password_verify($senhaAtual, $hashAtual)) {
        $erro = "Senha atual incorreta.";
    } elseif (strlen($novaSenha) < SENHA_TAMANHO_MINIMO || strlen($novaSenha) > 72) {
        $erro = "A nova senha deve ter entre " . SENHA_TAMANHO_MINIMO . " e 72 caracteres.";
    } elseif ($novaSenha !== $confirmarSenha) {
        $erro = "A confirmação não confere com a nova senha.";
    } elseif ($novaSenha === $senhaAtual) {
        $erro = "A nova senha precisa ser diferente da atual.";
    } else {
        $novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $up = $conexao->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $up->bind_param("si", $novoHash, $usuario_id);

        if ($up->execute()) {
            $sucesso = "Senha alterada com sucesso.";
        } else {
            $erro = "Não foi possível alterar a senha.";
        }
        $up->close();
    }
}

// ==========================================
// DADOS ATUAIS PARA EXIBIR
// ==========================================

$sql = $conexao->prepare(
    "SELECT u.nome, u.email, u.cpf, u.telefone, u.criado_em,
            m.crm, m.uf, m.status_profissional, m.anos_atuacao, m.valor_consulta,
            e.nome AS especialidade
     FROM medicos m
     INNER JOIN usuarios u ON u.id = m.usuario_id
     LEFT JOIN especialidades e ON e.id = m.especialidade_id
     WHERE m.id = ?"
);
$sql->bind_param("i", $medico_id);
$sql->execute();
$medico = $sql->get_result()->fetch_assoc();

// Alguns números rápidos da agenda, para o perfil não ficar só formulário
$sqlStats = $conexao->prepare(
    "SELECT
        SUM(status = 'Realizada') AS realizadas,
        SUM(status = 'Agendada')  AS agendadas,
        COUNT(DISTINCT paciente_id) AS pacientes
     FROM consultas WHERE medico_id = ?"
);
$sqlStats->bind_param("i", $medico_id);
$sqlStats->execute();
$stats = $sqlStats->get_result()->fetch_assoc();

$rotuloStatus = [
    "ativo"    => "Ativo — seu perfil aparece para os pacientes agendarem",
    "pendente" => "Pendente — aguardando aprovação do administrador. Enquanto isso você não aparece na busca de médicos",
    "inativo"  => "Inativo — seu perfil está suspenso e não recebe novos agendamentos",
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu perfil | FacilMed</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/medico.css">
</head>
<body>

<?php include("_sidebar.php"); ?>

<main class="conteudo">
    <header class="topo">
        <div>
            <h1>Meu perfil</h1>
            <p><?= htmlspecialchars($medico["nome"]) ?><?= $medico["especialidade"] ? " — " . htmlspecialchars($medico["especialidade"]) : "" ?></p>
        </div>
    </header>

    <?php if ($erro): ?>
        <p style="background:#fde8e8; color:#8b0000; padding:10px 15px; border-radius:6px;">
            <?= htmlspecialchars($erro) ?>
        </p>
    <?php endif; ?>
    <?php if ($sucesso): ?>
        <p style="background:#e6f5ea; color:#1b7f3b; padding:10px 15px; border-radius:6px;">
            <?= htmlspecialchars($sucesso) ?>
        </p>
    <?php endif; ?>

    <section class="kpis">
        <div class="kpi-card"><h3>Consultas realizadas</h3><strong><?= (int) $stats["realizadas"] ?></strong></div>
        <div class="kpi-card"><h3>Consultas agendadas</h3><strong><?= (int) $stats["agendadas"] ?></strong></div>
        <div class="kpi-card"><h3>Pacientes atendidos</h3><strong><?= (int) $stats["pacientes"] ?></strong></div>
    </section>

    <section class="painel">
        <div class="painel-titulo">
            <h2>Situação do cadastro</h2>
        </div>
        <p><?= htmlspecialchars($rotuloStatus[$medico["status_profissional"]] ?? $medico["status_profissional"]) ?></p>
        <p>
            <strong>CRM:</strong> <?= htmlspecialchars($medico["crm"]) ?>/<?= htmlspecialchars($medico["uf"]) ?> &nbsp;·&nbsp;
            <strong>Especialidade:</strong> <?= htmlspecialchars($medico["especialidade"] ?: "não informada") ?> &nbsp;·&nbsp;
            <strong>Cadastrado em:</strong> <?= date("d/m/Y", strtotime($medico["criado_em"])) ?>
        </p>
        <small>CRM, UF e especialidade só podem ser alterados pelo administrador.</small>
    </section>

    <section class="painel">
        <div class="painel-titulo">
            <h2>Meus dados</h2>
        </div>
        <form method="POST" action="perfil.php">
            <input type="hidden" name="acao" value="dados">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <p>
                <label for="nome">Nome completo</label><br>
                <input type="text" id="nome" name="nome" required minlength="3" maxlength="150"
                       value="<?= htmlspecialchars($medico["nome"]) ?>">
            </p>

            <p>
                <label for="emailFixo">E-mail (usado para entrar no sistema)</label><br>
                <input type="email" id="emailFixo" value="<?= htmlspecialchars($medico["email"]) ?>" readonly
                       style="background:#f1f1f1;">
                <br><small>Para trocar o e-mail, peça ao administrador.</small>
            </p>

            <p>
                <label for="telefone">Telefone</label><br>
                <input type="text" id="telefone" name="telefone" maxlength="20"
                       value="<?= htmlspecialchars($medico["telefone"]) ?>">
            </p>

            <p>
                <label for="anos_atuacao">Anos de atuação</label><br>
                <input type="number" id="anos_atuacao" name="anos_atuacao" min="0" max="70"
                       value="<?= (int) $medico["anos_atuacao"] ?>">
            </p>

            <p>
                <label for="valor_consulta">Valor da consulta particular (R$)</label><br>
                <input type="number" id="valor_consulta" name="valor_consulta" step="0.01" min="0"
                       value="<?= number_format((float) $medico["valor_consulta"], 2, ".", "") ?>">
                <br><small>É este valor que o paciente vê ao escolher atendimento particular.</small>
            </p>

            <button type="submit">Salvar alterações</button>
        </form>
    </section>

    <section class="painel">
        <div class="painel-titulo">
            <h2>Trocar senha</h2>
        </div>
        <form method="POST" action="perfil.php">
            <input type="hidden" name="acao" value="senha">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <p>
                <label for="senha_atual">Senha atual</label><br>
                <input type="password" id="senha_atual" name="senha_atual" required>
            </p>
            <p>
                <label for="nova_senha">Nova senha</label><br>
                <input type="password" id="nova_senha" name="nova_senha" required
                       minlength="<?= SENHA_TAMANHO_MINIMO ?>" maxlength="72">
            </p>
            <p>
                <label for="confirmar_senha">Confirmar nova senha</label><br>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required
                       minlength="<?= SENHA_TAMANHO_MINIMO ?>" maxlength="72">
            </p>

            <button type="submit">Alterar senha</button>
        </form>
    </section>
</main>

<footer class="rodape-painel">&copy; <?= date("Y") ?> FacilMed — Painel do médico.</footer>

</body>
</html>
