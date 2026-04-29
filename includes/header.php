<header class="header">
    <div class="header-left">
        <button class="menu-toggle"><i class="bi bi-list"></i></button>

        <!-- Barre de recherche globale avec dropdown AJAX -->
        <div class="search-bar" id="globalSearchBar">
            <i class="bi bi-search"></i>
            <input type="text"
                   placeholder="Rechercher article, vente, fournisseur…"
                   id="globalSearch"
                   autocomplete="off"
                   spellcheck="false">
            <span class="search-shortcut" id="searchShortcut">Ctrl+K</span>
        </div>
    </div>

    <div class="header-right">
        <!-- Bouton Nouvelle vente rapide -->
        <a href="ajouter_vente.php" class="btn btn-sm btn-primary" style="padding:6px 12px;font-size:.8rem">
            <i class="bi bi-plus-lg"></i>
            <span class="d-none d-md-inline">Vente</span>
        </a>

        <!-- Cloche alertes -->
        <div class="notification-badge" onclick="location.href='alertes.php'" title="Alertes stock">
            <i class="bi bi-bell"></i>
            <?php
            $stmtH = mysqli_prepare($conn, "SELECT COUNT(*) as nb FROM articles WHERE quantite <= stock_minimum");
            mysqli_stmt_execute($stmtH);
            $nbAlertesH = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtH))['nb'];
            if ($nbAlertesH > 0): ?>
            <span class="badge-count"><?= $nbAlertesH > 9 ? '9+' : $nbAlertesH ?></span>
            <?php endif; ?>
        </div>

        <!-- Avatar utilisateur -->
        <div class="dropdown">
            <div class="avatar" data-bs-toggle="dropdown" style="cursor:pointer" title="Mon compte">
                <?= strtoupper(substr($_SESSION['nom'], 0, 1)) ?>
            </div>
            <ul class="dropdown-menu dropdown-menu-end" style="min-width:180px;border-radius:10px;border:1.5px solid #e2e8f0;padding:6px">
                <li>
                    <div style="padding:8px 14px;border-bottom:1px solid #f1f5f9;margin-bottom:4px">
                        <div style="font-size:.82rem;font-weight:600;color:#1e293b"><?= htmlspecialchars($_SESSION['nom']) ?></div>
                        <div style="font-size:.72rem;color:#94a3b8"><?= htmlspecialchars($_SESSION['email']) ?></div>
                        <span class="role-badge role-<?= $_SESSION['role'] ?>" style="margin-top:4px;display:inline-flex">
                            <?= ucfirst($_SESSION['role']) ?>
                        </span>
                    </div>
                </li>
                <?php if ($_SESSION['role'] === 'administrateur'): ?>
                <li>
                    <a class="dropdown-item" href="users.php" style="font-size:.85rem;border-radius:7px">
                        <i class="bi bi-people me-2"></i>Utilisateurs
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a class="dropdown-item text-danger" href="logout.php" style="font-size:.85rem;border-radius:7px">
                        <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>