<?php
//=========================================
// FacilMed
// Cadastro de Médicos
//=========================================

require_once("conexao.php");

if($_SERVER["REQUEST_METHOD"] != "POST") {
    die("Acesso inválido.");
}

//=========================================
// Dados
//=========================================

$nome = trim($_POST["nome"]);
$cpf = trim($_POST["cpf"]);
$email = trim($_POST["email"]);
$telefone = trim($_POST["telefone"]);
$crm = trim($_POST["crm"]);
$uf = $_POST["uf"];
$senha = $_POST["senha"];
$confirmarSenha = $_POST["confirmarSenha"];

//=========================================

if($senha != $confirmarSenha) {
    die("As senhas não coincidem.");
}

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
// Validação do CRM
//=========================================

// Aqui será chamado o validarCRM.php
// if(!validarCRM($crm,$uf)){}

//=========================================
// Cadastra Usuário
//=========================================

$stmt = $conexao->prepare("INSERT INTO usuarios (nome,cpf,email,telefone,senha,tipo) VALUES (?,?,?,?,?,'medico')");
$stmt->bind_param("sssss", $nome, $cpf, $email, $telefone, $senhaCriptografada);

if($stmt->execute()) {
    $usuarioID = $stmt->insert_id;
    $medico = $conexao->prepare("INSERT INTO medicos (usuario_id,crm,uf) VALUES (?,?,?)");
    $medico->bind_param("iss", $usuarioID, $crm, $uf);
    $medico->execute();
    echo "<script>
        alert('Médico cadastrado com sucesso!');
        window.location='../paginas/login.html';
    </script>";
} else {
    echo "Erro ao cadastrar.";
}
?>