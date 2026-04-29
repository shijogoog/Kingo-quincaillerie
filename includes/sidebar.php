<?php
$current_page = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
$role = $user['role'];
?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-logo-icon"><i class="bi bi-tools"></i></div>
        <div>
            <div class="sidebar-logo-name">QuincaStore</div>
            <div class="sidebar-logo-sub">Quincaillerie</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Principal</div>

        <!-- Tableau de bord : tout le monde -->
        <a href="dashboard.php" class="sidebar-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <span class="sidebar-dot"></span><i class="bi bi-speedometer2"></i><span>Tableau de bord</span>
        </a>

        <!-- Articles : seulement admin et gestionnaire -->
        <?php if ($role != 'vendeur'): ?>
        <a href="articles.php" class="sidebar-link <?= in_array($current_page, ['articles.php','ajouter_article.php','modifier_article.php']) ? 'active' : '' ?>">
            <span class="sidebar-dot"></span><i class="bi bi-box"></i><span>Articles</span>
        </a>
        <?php endif; ?>

        <!-- Fournisseurs : seulement admin (et gestionnaire ? à voir, mais je mets admin par défaut) -->
        <?php if ($role == 'administrateur'): ?>
        <a href="fournisseurs.php" class="sidebar-link <?= $current_page == 'fournisseurs.php' ? 'active' : '' ?>">
            <span class="sidebar-dot"></span><i class="bi bi-truck"></i><span>Fournisseurs</span>
        </a>
        <?php endif; ?>

        <!-- Ventes : tout le monde (mais le vendeur ne verra que ses ventes) -->
        <a href="ventes.php" class="sidebar-link <?= in_array($current_page, ['ventes.php','ajouter_vente.php','details_vente.php','facture.php']) ? 'active' : '' ?>">
            <span class="sidebar-dot"></span><i class="bi bi-cart3"></i><span>Ventes</span>
        </a>

        <!-- Gestion (stock, alertes, rapports) : admin + gestionnaire -->
        <?php if ($role != 'vendeur'): ?>
        <div class="sidebar-section-label">Gestion</div>

        <a href="stock.php" class="sidebar-link <?= $current_page == 'stock.php' ? 'active' : '' ?>">
            <span class="sidebar-dot"></span><i class="bi bi-bar-chart-line"></i><span>Stock</span>
        </a>
        <a href="alertes.php" class="sidebar-link <?= $current_page == 'alertes.php' ? 'active' : '' ?>">
            <span class="sidebar-dot"></span><i class="bi bi-bell"></i><span>Alertes</span>
            <?php
            $stmtA = mysqli_prepare($conn, "SELECT COUNT(*) as nb FROM articles WHERE quantite <= stock_minimum");
            mysqli_stmt_execute($stmtA);
            $nbA = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtA))['nb'];
            if ($nbA > 0): ?>
                <span class="sidebar-badge"><?= $nbA > 9 ? '9+' : $nbA ?></span>
            <?php endif; ?>
        </a>
        <a href="rapports.php" class="sidebar-link <?= $current_page == 'rapports.php' ? 'active' : '' ?>">
            <span class="sidebar-dot"></span><i class="bi bi-graph-up-arrow"></i><span>Rapports</span>
        </a>
        <?php endif; ?>

        <!-- Finances (si module actif) : admin + gestionnaire -->
        <?php if ($role != 'vendeur'): ?>
        <a href="tresorerie.php" class="sidebar-link <?= in_array($current_page, ['tresorerie.php','depenses.php','compte_resultat.php','rappels.php']) ? 'active' : '' ?>">
            <span class="sidebar-dot"></span><i class="bi bi-cash-stack"></i><span>Finances</span>
        </a>
        <?php endif; ?>

        <!-- Utilisateurs : seulement admin -->
        <?php if ($role == 'administrateur'): ?>
        <a href="users.php" class="sidebar-link <?= $current_page == 'users.php' ? 'active' : '' ?>">
            <span class="sidebar-dot"></span><i class="bi bi-people"></i><span>Utilisateurs</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar"><?= strtoupper(substr($user['nom'], 0, 1)) ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($user['nom']) ?></div>
                <div class="sidebar-user-role"><?= ucfirst($user['role']) ?></div>
            </div>
            <a href="logout.php" class="sidebar-logout" title="Déconnexion"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>
</aside>