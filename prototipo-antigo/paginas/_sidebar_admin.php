<?php
/*=========================================================
    Sidebar compartilhada do painel administrativo.
    Usada tanto por paginas/paineladmin.php (raiz de
    paginas/) quanto por paginas/admin/*.php (um nível
    abaixo) — por isso espera duas variáveis definidas
    ANTES do include:

    $base        prefixo relativo até a pasta paginas/
                 ("" a partir de paginas/, "../" a partir
                 de paginas/admin/)
    $paginaAtiva nome do arquivo atual, para destacar o
                 item correspondente no menu
=========================================================*/
$base = $base ?? "";
$paginaAtiva = $paginaAtiva ?? "";

// Contador de médicos esperando aprovação, mostrado como aviso ao lado de
// "Médicos". Enquanto um cadastro está 'pendente' o médico não aparece para
// os pacientes agendarem, então esse número precisa ficar visível.
$medicosPendentes = 0;
if (isset($conexao) && $conexao instanceof mysqli) {
    $r = $conexao->query("SELECT COUNT(*) AS total FROM medicos WHERE status_profissional = 'pendente'");
    if ($r) {
        $medicosPendentes = (int) ($r->fetch_assoc()["total"] ?? 0);
    }
}
?>
<aside class="sidebar">
    <div class="logo"><span>Facil</span>Med</div>
    <nav>
        <a href="<?= $base ?>paineladmin.php" class="<?= $paginaAtiva === 'paineladmin.php' ? 'ativo' : '' ?>">Usuários</a>
        <a href="<?= $base ?>listarmedicos.php" class="<?= $paginaAtiva === 'listarmedicos.php' ? 'ativo' : '' ?>">
            Médicos<?php if($medicosPendentes > 0): ?>
                <span title="<?= $medicosPendentes ?> aguardando aprovação"
                      style="background:#c0392b; color:#fff; border-radius:10px; padding:1px 7px; font-size:11px; margin-left:6px;"><?= $medicosPendentes ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= $base ?>admin/especialidades.php" class="<?= $paginaAtiva === 'especialidades.php' ? 'ativo' : '' ?>">Especialidades</a>
        <a href="<?= $base ?>admin/locais.php" class="<?= $paginaAtiva === 'locais.php' ? 'ativo' : '' ?>">Locais</a>
        <a href="<?= $base ?>admin/convenios.php" class="<?= $paginaAtiva === 'convenios.php' ? 'ativo' : '' ?>">Convênios</a>
        <a href="<?= $base ?>admin/planos.php" class="<?= $paginaAtiva === 'planos.php' ? 'ativo' : '' ?>">Planos</a>
    </nav>
    <div class="menu-final">
        <a href="<?= $base ?>../php/logout.php">Sair</a>
    </div>
</aside>
