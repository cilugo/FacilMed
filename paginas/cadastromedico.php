<?php
// Popula o <select> de especialidade dinamicamente a partir da tabela especialidades.
require_once("../php/conexao.php");
// Traz a constante UFS_BRASIL, usada para montar o <select> de UF do CRM
require_once("../php/validarCRM.php");
$especialidades = $conexao->query("SELECT id, nome FROM especialidades ORDER BY nome ASC");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Médico | FacilMed</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/cadastro.css">
</head>
<body>
    <header>
        <h1>FacilMed</h1>
    </header>
    <nav>
        <ul>
            <li><a href="login.html">Login</a></li>
            <li><a href="cadastro.html">Cadastre-se</a></li>
        </ul>
    </nav>
    <main class="container">
        <section class="card">
            <h1>Cadastro de Médico</h1>
            <p>Preencha todos os campos abaixo para criar sua conta profissional no FacilMed.</p>
            <form id="formCadastro" action="../php/cadastromedico.php" method="POST">
                <div class="campo">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" maxlength="100" required placeholder="Digite seu nome completo">
                </div>
                <div class="campo">
                    <label for="cpf">CPF</label>
                    <input type="text" id="cpf" name="cpf" maxlength="14" required placeholder="000.000.000-00">
                </div>
                <div class="campo">
                    <label for="crm">CRM</label>
                    <input type="text" id="crm" name="crm" required placeholder="Somente números, mínimo 4 dígitos">
                </div>
                <div class="campo">
                    <label for="uf">UF do CRM</label>
                    <select id="uf" name="uf" required>
                        <option value="">Selecione</option>
                        <?php foreach(UFS_BRASIL as $sigla): ?>
                            <option><?= $sigla ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="especialidade_id">Especialidade</label>
                    <select id="especialidade_id" name="especialidade_id" required>
                        <option value="">Selecione</option>
                        <?php while($esp = $especialidades->fetch_assoc()): ?>
                            <option value="<?= (int) $esp['id'] ?>"><?= htmlspecialchars($esp['nome']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" maxlength="15" placeholder="(00) 00000-0000" required>
                </div>
                <div class="campo">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" maxlength="100" placeholder="exemplo@email.com" required>
                </div>
                <div class="campo senha">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" minlength="8" maxlength="32" required>
                </div>
                <div class="campo senha">
                    <label for="confirmarSenha">Confirmar Senha</label>
                    <input type="password" id="confirmarSenha" name="confirmarSenha" minlength="8" maxlength="32" required>
                </div>
                <div class="campo">
                    <label>
                        <input type="checkbox" required>
                        Li e aceito os Termos de Uso.
                    </label>
                </div>
                <div class="botoes">
                    <button type="reset" class="botaoSecundario">Limpar</button>
                    <button type="submit" class="botao">Cadastrar</button>
                </div>
            </form>
            <hr>
            <p>Já possui uma conta?</p>
            <a href="login.html" class="botao">Fazer Login</a>
        </section>
    </main>
    <footer>
        &copy; <?= date("Y") ?> FacilMed - Todos os direitos reservados.
    </footer>
    <script src="../js/mascaras.js"></script>
    <script src="../js/validacoes.js"></script>
    <script src="../js/cadastromedico.js"></script>
</body>
</html>
