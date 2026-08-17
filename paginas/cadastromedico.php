<?php
// Convertido de .html para .php só para popular o <select> de
// especialidade dinamicamente a partir da tabela especialidades.
require_once("../php/conexao.php");
$especialidades = $conexao->query("SELECT id, nome FROM especialidades ORDER BY nome ASC");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<title>Cadastro de Médico</title>
	<link rel="stylesheet" href="../css/style.css">
</head>
<body>
	<div class="container">
		<h1>Cadastro de Médico</h1>
		<form action="../php/cadastromedico.php" method="POST">
			<input type="text" name="nome" placeholder="Nome completo" required>
			<input type="text" name="cpf" placeholder="CPF" required>
			<input type="text" name="crm" placeholder="CRM" required>
			<select name="uf" required>
				<option value="">UF do CRM</option>
				<option>SP</option>
				<option>RJ</option>
				<option>MG</option>
				<option>PR</option>
			</select>
			<select name="especialidade_id" required>
				<option value="">Especialidade</option>
				<?php while($esp = $especialidades->fetch_assoc()): ?>
					<option value="<?= (int) $esp['id'] ?>"><?= htmlspecialchars($esp['nome']) ?></option>
				<?php endwhile; ?>
			</select>
			<input type="email" name="email" placeholder="E-mail" required>
			<input type="text" name="telefone" placeholder="Telefone" required>
			<input type="password" name="senha" placeholder="Senha" required>
			<input type="password" name="confirmarSenha" placeholder="Confirmar senha" required>
			<button type="submit">Cadastrar</button>
		</form>
	</div>
</body>
</html>
