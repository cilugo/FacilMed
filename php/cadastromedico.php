<?php
//=========================================
// FacilMed
// Cadastro de Médicos
//=========================================

require_once("conexao.php");
require_once("validarCRM.php");

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
$especialidadeId = (int) ($_POST["especialidade_id"] ?? 0);
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

if(!validarCRM($crm, $uf)) {
    die("CRM inválido. Informe apenas números (mínimo 4 dígitos) e uma UF válida.");
}

if($especialidadeId <= 0) {
    die("Selecione uma especialidade.");
}

//=========================================
// Verifica se já existe médico com o mesmo CRM/UF
//=========================================

$sqlCrm = $conexao->prepare("SELECT id FROM medicos WHERE crm=? AND uf=?");
$sqlCrm->bind_param("ss", $crm, $uf);
$sqlCrm->execute();
$sqlCrm->store_result();

if($sqlCrm->num_rows > 0) {
    die("Já existe um médico cadastrado com esse CRM/UF.");
}

//=========================================
// Cadastra Usuário
//=========================================

// Transação: se o INSERT em `medicos` falhar depois do INSERT em
// `usuarios`, desfaz os dois em vez de deixar uma conta sem perfil.
$conexao->begin_transaction();

$stmt = $conexao->prepare("INSERT INTO usuarios (nome,cpf,email,telefone,senha,tipo) VALUES (?,?,?,?,?,'medico')");
$stmt->bind_param("sssss", $nome, $cpf, $email, $telefone, $senhaCriptografada);

if($stmt->execute()) {
    $usuarioID = $stmt->insert_id;
    $medico = $conexao->prepare("INSERT INTO medicos (usuario_id,crm,uf,especialidade_id) VALUES (?,?,?,?)");
    $medico->bind_param("issi", $usuarioID, $crm, $uf, $especialidadeId);

    if($medico->execute()) {
        $conexao->commit();
        echo "<script>
            alert('Médico cadastrado com sucesso!');
            window.location='../paginas/login.html';
        </script>";
    } else {
        $conexao->rollback();
        echo "Erro ao cadastrar o perfil de médico. Tente novamente.";
    }
} else {
    $conexao->rollback();
    echo "Erro ao cadastrar.";
}
?>