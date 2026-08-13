<?php
//=========================================
// FacilMed
// Cadastro de Pacientes
//=========================================

require_once("conexao.php");

//=========================================
// Verifica se veio do formulário
//=========================================

if($_SERVER["REQUEST_METHOD"] != "POST") {
    die("Acesso inválido.");
}

//=========================================
// Recebe os dados
//=========================================

$nome = trim($_POST["nome"]);
$cpf = trim($_POST["cpf"]);
$email = trim($_POST["email"]);
$telefone = trim($_POST["telefone"]);
$dataNascimento = $_POST["dataNascimento"];
$senha = $_POST["senha"];
$confirmarSenha = $_POST["confirmarSenha"];

//=========================================
// Validação básica
//=========================================

if(empty($nome) || empty($cpf) || empty($email) || empty($telefone) || empty($senha) || empty($confirmarSenha)) {
    die("Preencha todos os campos.");
}

if($senha != $confirmarSenha) {
    die("As senhas não coincidem.");
}

//=========================================
// Criptografa a senha
//=========================================

$senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);

//=========================================
// Verifica CPF
//=========================================

$sql = $conexao->prepare("SELECT id FROM usuarios WHERE cpf=?");
$sql->bind_param("s", $cpf);
$sql->execute();
$sql->store_result();

if($sql->num_rows > 0) {
    die("CPF já cadastrado.");
}

//=========================================
// Verifica Email
//=========================================

$sql = $conexao->prepare("SELECT id FROM usuarios WHERE email=?");
$sql->bind_param("s", $email);
$sql->execute();
$sql->store_result();

if($sql->num_rows > 0) {
    die("Email já cadastrado.");
}

//=========================================
// Cadastra Usuário
//=========================================

$stmt = $conexao->prepare("INSERT INTO usuarios (nome,cpf,email,telefone,senha,tipo) VALUES (?,?,?,?,?,'paciente')");
$stmt->bind_param("sssss", $nome, $cpf, $email, $telefone, $senhaCriptografada);

//=========================================

if($stmt->execute()) {
    $usuarioID = $stmt->insert_id;
    $paciente = $conexao->prepare("INSERT INTO pacientes (usuario_id,data_nascimento) VALUES (?,?)");
    $paciente->bind_param("is", $usuarioID, $dataNascimento);
    $paciente->execute();
    echo "<script>
        alert('Paciente cadastrado com sucesso!');
        window.location='../paginas/login.html';
    </script>";
} else {
    echo "Erro ao cadastrar.";
}
?>