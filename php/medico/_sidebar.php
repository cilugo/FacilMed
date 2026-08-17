<?php
// Sidebar compartilhada do painel do médico.
// Espera que a variável $paginaAtiva já tenha sido definida (ex: "dashboard.php").
?>
<aside class="sidebar">
    <div class="logo">
        <span>Facil</span>Med
    </div>
    <nav>
        <a href="dashboard.php" class="<?= $paginaAtiva === 'dashboard.php' ? 'ativo' : '' ?>">🏠 Início</a>
        <a href="agenda.php" class="<?= $paginaAtiva === 'agenda.php' ? 'ativo' : '' ?>">📅 Agenda</a>
        <a href="pacientes.php" class="<?= $paginaAtiva === 'pacientes.php' ? 'ativo' : '' ?>">👥 Pacientes</a>
        <a href="prontuarios.php" class="<?= $paginaAtiva === 'prontuarios.php' ? 'ativo' : '' ?>">📋 Prontuários</a>
        <a href="relatorios.php" class="<?= $paginaAtiva === 'relatorios.php' ? 'ativo' : '' ?>">📊 Relatórios</a>
        <a href="financeiro.php" class="<?= $paginaAtiva === 'financeiro.php' ? 'ativo' : '' ?>">💰 Financeiro</a>
        <a href="perfil.php" class="<?= $paginaAtiva === 'perfil.php' ? 'ativo' : '' ?>">👤 Meu perfil</a>
    </nav>
    <div class="menu-final">
        <a href="../logout.php">🚪 Sair</a>
    </div>
</aside>
