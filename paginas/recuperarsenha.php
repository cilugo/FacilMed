<?php
require_once("../php/conexao.php");
require_once("../php/env.php");
require_once("../php/lib/SimpleMailer.php");

// Verifica o método
if($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acesso inválido.");
}

// Recebe o e-mail
$email = trim($_POST["email"]);

if(empty($email)) {
    die("Informe o e-mail.");
}

// Procura o usuário
$stmt = $conexao->prepare("SELECT id, nome, email FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$resultado = $stmt->get_result();

// Verifica se encontrou
if($resultado->num_rows === 0) {
    die("Não encontramos uma conta com esse e-mail.");
}

$usuario = $resultado->fetch_assoc();

// Gera código aleatório
$codigo = str_pad(random_int(0, 999999), 6, "0", STR_PAD_LEFT);

// Código expira em 15 minutos
$expiracao = date("Y-m-d H:i:s", time() + (15 * 60));

// Invalida códigos anteriores ainda não usados desse usuário
$invalidar = $conexao->prepare("UPDATE recuperacao_senha SET utilizado = 1 WHERE usuario_id = ? AND utilizado = 0");
$invalidar->bind_param("i", $usuario["id"]);
$invalidar->execute();

// Salva o novo código na tabela própria de recuperação de senha
$inserir = $conexao->prepare("INSERT INTO recuperacao_senha (usuario_id, codigo, expiracao) VALUES (?, ?, ?)");
$inserir->bind_param("iss", $usuario["id"], $codigo, $expiracao);
$inserir->execute();

// Mascara o e-mail
function mascararEmail($email) {
    $partes = explode("@", $email);
    $usuarioEmail = $partes[0];
    $dominio = $partes[1];
    if(strlen($usuarioEmail) <= 2) {
        $mascarado = substr($usuarioEmail, 0, 1) . "****";
    } else {
        $mascarado = substr($usuarioEmail, 0, 2) . "****";
    }
    return $mascarado . "@" . $dominio;
}

$emailMascarado = mascararEmail($email);

// ==========================================
// ENVIO DO E-MAIL
//
// Se SMTP_HOST/SMTP_USER/SMTP_PASS/SMTP_FROM_EMAIL estiverem
// preenchidos (via .env ou variáveis de ambiente reais do
// servidor), manda o e-mail de verdade e NÃO mostra o código
// na tela. Se não estiverem configurados, cai em modo de
// desenvolvimento e mostra o código na tela (só para testar
// localmente sem precisar configurar SMTP).
// ==========================================

$mailer = SimpleMailer::fromEnv();
$modoDesenvolvimento = ($mailer === null);
$erroEnvio = null;

if(!$modoDesenvolvimento) {
    $corpoHtml = "
        <p>Olá, " . htmlspecialchars($usuario['nome']) . "!</p>
        <p>Recebemos uma solicitação de recuperação de senha para sua conta no FacilMed.</p>
        <p>Seu código de recuperação é:</p>
        <p style='font-size:28px; font-weight:bold; letter-spacing:4px;'>" . htmlspecialchars($codigo) . "</p>
        <p>Este código expira em 15 minutos. Não compartilhe este código com outras pessoas.</p>
        <p>Se você não solicitou essa recuperação, ignore este e-mail.</p>
    ";

    try {
        $mailer->send($usuario["email"], $usuario["nome"], "Recuperação de senha - FacilMed", $corpoHtml);
    } catch (SimpleMailerException $e) {
        // Não expõe o código nem detalhes internos do erro ao usuário;
        // registra no log do servidor para o admin investigar.
        error_log("Falha ao enviar e-mail de recuperação: " . $e->getMessage());
        $erroEnvio = "Não foi possível enviar o e-mail agora. Tente novamente em alguns minutos.";
    }
}

if($erroEnvio) {
    die(htmlspecialchars($erroEnvio));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de recuperação | FacilMed</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/recuperarsenha.css">
</head>
<body>
<header>
    <h1>FacilMed</h1>
</header>
<main class="container">
<section class="email-card">
    <h2>Recuperação de senha</h2>

    <?php if($modoDesenvolvimento): ?>
    <!-- Modo de desenvolvimento: SMTP não configurado, mostra o código aqui só para teste -->
    <p style="background:#fff3cd; color:#856404; padding:10px 15px; border-radius:6px; font-size:13px;">
        ⚠️ Modo de desenvolvimento: SMTP não configurado (veja o arquivo <code>.env.example</code>).
        O código está sendo exibido aqui só para teste local — em produção ele vai por e-mail.
    </p>
    <div class="email">
        <div class="email-topo">
            <strong>Para:</strong>
            <span><?php echo htmlspecialchars($emailMascarado); ?></span>
        </div>
        <div class="email-topo">
            <strong>Assunto:</strong>
            <span>Recuperação de senha - FacilMed</span>
        </div>
        <hr>
        <p>Olá, <?php echo htmlspecialchars($usuario["nome"]); ?>!</p>
        <p>Seu código de recuperação é:</p>
        <div class="codigo"><?php echo $codigo; ?></div>
        <p>Este código expira em:</p>
        <div id="cronometro" class="cronometro">15:00</div>
        <p id="mensagemExpiracao" class="mensagem-expiracao">Não compartilhe este código com outras pessoas.</p>
    </div>
    <?php else: ?>
    <!-- Produção: e-mail já foi enviado de verdade, não mostramos o código -->
    <div class="email">
        <p>Enviamos um código de verificação para <strong><?php echo htmlspecialchars($emailMascarado); ?></strong>.</p>
        <p>Confira sua caixa de entrada (e o spam, por garantia).</p>
        <p>Este código expira em:</p>
        <div id="cronometro" class="cronometro">15:00</div>
        <p id="mensagemExpiracao" class="mensagem-expiracao">Não compartilhe este código com outras pessoas.</p>
    </div>
    <?php endif; ?>

    <!-- Formulário do código -->
    <form action="../paginas/verificarcodigo.php" method="POST" id="formCodigo">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <div class="campo">
            <label for="codigo">Digite o código recebido</label>
            <input type="text" id="codigo" name="codigo" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" required>
        </div>
        <button type="submit" id="botaoVerificar" class="botao">Verificar código</button>
    </form>
    <p class="novo-codigo">
        Não recebeu o código?
        <a href="recuperarsenha.html">Solicitar novo código</a>
    </p>
</section>
</main>
<footer>
    © 2026 FacilMed
</footer>
<!-- JavaScript -->
<script src="../js/recuperarsenha.js"></script>
<script>
    /* Passa o horário de expiração do PHP para o JavaScript. */
    const dataExpiracao = new Date("<?php echo $expiracao; ?>");
</script>
</body>
</html>
