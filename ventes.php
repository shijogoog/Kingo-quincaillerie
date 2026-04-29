<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth();

$date_debut = $_GET['date_debut'] ?? '';
$date_fin   = $_GET['date_fin'] ?? '';
$vendeur_id = isset($_GET['vendeur_id']) ? (int)$_GET['vendeur_id'] : 0;
$statut     = $_GET['statut'] ?? '';

$where  = [];
$params = [];
$types  = "";

if ($_SESSION['role'] === 'vendeur') {
    $where[]  = "v.utilisateur_id = ?";
    $params[] = $_SESSION['user_id'];
    $types   .= "i";
}
if (!empty($date_debut)) {
    $where[]  = "DATE(v.date_vente) >= ?";
    $params[] = $date_debut;
    $types   .= "s";
}
if (!empty($date_fin)) {
    $where[]  = "DATE(v.date_vente) <= ?";
    $params[] = $date_fin;
    $types   .= "s";
}
if ($vendeur_id > 0 && $_SESSION['role'] !== 'vendeur') {
    $where[]  = "v.utilisateur_id = ?";
    $params[] = $vendeur_id;
    $types   .= "i";
}
if (!empty($statut)) {
    $where[]  = "v.statut = ?";
    $params[] = $statut;
    $types   .= "s";
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = 15;
$offset = ($page - 1) * $limit;

$sql = "SELECT v.*, u.nom as vendeur_nom
        FROM ventes v
        JOIN users u ON u.id = v.utilisateur_id
        $whereClause
        ORDER BY v.date_vente DESC
        LIMIT ? OFFSET ?";

$allParams  = array_merge($params, [$limit, $offset]);
$allTypes   = $types . "ii";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($allParams)) {
    mysqli_stmt_bind_param($stmt, $allTypes, ...$allParams);
}
mysqli_stmt_execute($stmt);
$ventes = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

// Compter pour pagination
$countSql    = "SELECT COUNT(*) as total FROM ventes v $whereClause";
$stmtCount   = mysqli_prepare($conn, $countSql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmtCount, $types, ...$params);
}
mysqli_stmt_execute($stmtCount);
$total      = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCount))['total'];
$totalPages = ceil($total / $limit);

// Résumé global
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total_ventes, SUM(montant_total) as ca_total FROM ventes WHERE statut='validée'");
mysqli_stmt_execute($stmt);
$resume      = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$totalVentes = $resume['total_ventes'] ?? 0;
$caTotal     = $resume['ca_total'] ?? 0;
$panierMoyen = $totalVentes > 0 ? $caTotal / $totalVentes : 0;

// Vendeurs pour le filtre
$vendeurs = [];
if ($_SESSION['role'] !== 'vendeur') {
    $stmt = mysqli_prepare($conn, "SELECT id, nom FROM users WHERE role IN ('vendeur','gestionnaire','administrateur') ORDER BY nom");
    mysqli_stmt_execute($stmt);
    $vendeurs = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ventes - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        <div class="page-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Gestion des ventes</h1>
                <a href="ajouter_vente.php" class="btn btn-primary"><i class="bi bi-plus"></i> Nouvelle vente</a>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon primary"><i class="bi bi-receipt"></i></div>
                        <div class="stat-details">
                            <div class="stat-label">Total ventes</div>
                            <div class="stat-value"><?= $totalVentes ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon success"><i class="bi bi-coin"></i></div>
                        <div class="stat-details">
                            <div class="stat-label">CA total</div>
                            <div class="stat-value"><?= fcfa($caTotal) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon accent"><i class="bi bi-cart3"></i></div>
                        <div class="stat-details">
                            <div class="stat-label">Panier moyen</div>
                            <div class="stat-value"><?= fcfa($panierMoyen) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Date début</label>
                            <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($date_debut) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date fin</label>
                            <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($date_fin) ?>">
                        </div>
                        <?php if ($_SESSION['role'] !== 'vendeur'): ?>
                        <div class="col-md-3">
                            <label class="form-label">Vendeur</label>
                            <select name="vendeur_id" class="form-select">
                                <option value="">Tous</option>
                                <?php foreach ($vendeurs as $v): ?>
                                    <option value="<?= $v['id'] ?>" <?= $vendeur_id == $v['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($v['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-2">
                            <label class="form-label">Statut</label>
                            <select name="statut" class="form-select">
                                <option value="">Tous</option>
                                <option value="validée"  <?= $statut === 'validée'  ? 'selected' : '' ?>>Validée</option>
                                <option value="annulée"  <?= $statut === 'annulée'  ? 'selected' : '' ?>>Annulée</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tableau -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th><th>Date</th><th>Client</th>
                            <th>Vendeur</th><th>Montant</th><th>Statut</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventes as $v): ?>
                        <tr>
                            <td><?= $v['id'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($v['date_vente'])) ?></td>
                            <td><?= htmlspecialchars($v['client_nom'] ?: 'Client anonyme') ?></td>
                            <td><?= htmlspecialchars($v['vendeur_nom']) ?></td>
                            <td><?= fcfa($v['montant_total']) ?></td>
                            <td>
                                <span class="badge badge-<?= $v['statut'] === 'validée' ? 'success' : 'danger' ?>">
                                    <?= $v['statut'] ?>
                                </span>
                            </td>
                            <td>
                                <a href="details_vente.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline" title="Détails">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="facture.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline" title="Facture">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <?php if ($_SESSION['role'] === 'administrateur' && $v['statut'] === 'validée'): ?>
                                    <form method="POST" action="annuler_vente.php" class="d-inline delete-form">
                                        <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline text-danger" title="Annuler">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ventes)): ?>
                        <tr><td colspan="7" class="text-center text-muted">Aucune vente trouvée</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&date_debut=<?= urlencode($date_debut) ?>&date_fin=<?= urlencode($date_fin) ?>&vendeur_id=<?= $vendeur_id ?>&statut=<?= urlencode($statut) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/script.js"></script>
</body>
</html>
