<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(['administrateur', 'gestionnaire']); // seulement admin et gestionnaire

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filtres
$where = [];
$params = [];
$types = "";

if (!empty($_GET['categorie'])) {
    $where[] = "categorie = ?";
    $params[] = $_GET['categorie'];
    $types .= "s";
}
if (!empty($_GET['fournisseur'])) {
    $where[] = "fournisseur_id = ?";
    $params[] = $_GET['fournisseur'];
    $types .= "i";
}
$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Récupérer les catégories distinctes pour le filtre
$stmt = mysqli_prepare($conn, "SELECT DISTINCT categorie FROM articles WHERE categorie IS NOT NULL ORDER BY categorie");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row['categorie'];
}

// Récupérer les fournisseurs pour le filtre
$fournisseurs = getFournisseurs();

// Requête principale avec JOIN fournisseur
$sql = "SELECT a.*, f.nom as fournisseur_nom FROM articles a 
        LEFT JOIN fournisseurs f ON f.id = a.fournisseur_id 
        $whereClause
        ORDER BY a.nom
        LIMIT ? OFFSET ?";
$types .= "ii";
$params[] = $limit;
$params[] = $offset;

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$articles = [];
while ($row = mysqli_fetch_assoc($result)) {
    $articles[] = $row;
}

// Compter total pour pagination
$countSql = "SELECT COUNT(*) as total FROM articles a $whereClause";
$stmtCount = mysqli_prepare($conn, $countSql);
if (!empty($params) && count($params) > 2) { // on enlève limit/offset
    $countParams = array_slice($params, 0, -2);
    $countTypes = substr($types, 0, -2);
    mysqli_stmt_bind_param($stmtCount, $countTypes, ...$countParams);
}
mysqli_stmt_execute($stmtCount);
$countResult = mysqli_stmt_get_result($stmtCount);
$total = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Articles - QuincaStore</title>
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
                <h1 class="h3">Gestion des articles</h1>
                <div>
                    <a href="exporter_articles.php" class="btn btn-outline me-2"><i class="bi bi-download"></i> Exporter CSV</a>
                    <a href="ajouter_article.php" class="btn btn-primary"><i class="bi bi-plus"></i> Nouvel article</a>
                </div>
            </div>

            <!-- Filtres -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Recherche</label>
                            <input type="text" class="form-control table-search" placeholder="Nom, catégorie...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Catégorie</label>
                            <select name="categorie" class="form-select">
                                <option value="">Toutes</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>" <?= ($_GET['categorie'] ?? '') === $cat ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fournisseur</label>
                            <select name="fournisseur" class="form-select">
                                <option value="">Tous</option>
                                <?php foreach ($fournisseurs as $f): ?>
                                    <option value="<?= $f['id'] ?>" <?= ($_GET['fournisseur'] ?? '') == $f['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($f['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                            <a href="articles.php" class="btn btn-outline ms-2">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tableau -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nom</th>
                            <th>Catégorie</th>
                            <th>Fournisseur</th>
                            <th>Prix vente</th>
                            <th>Stock</th>
                            <th>Stock min</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $a): ?>
                        <tr <?= $a['quantite'] <= $a['stock_minimum'] ? 'class="stock-low"' : '' ?>>
                            <td><?= $a['id'] ?></td>
                            <td><?= htmlspecialchars($a['nom']) ?></td>
                            <td><?= htmlspecialchars($a['categorie'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($a['fournisseur_nom'] ?? '-') ?></td>
                            <td><?= fcfa($a['prix_vente']) ?></td>
                            <td>
                                <?= $a['quantite'] ?>
                                <?php if ($a['quantite'] <= $a['stock_minimum']): ?>
                                    <span class="badge badge-warning">Faible</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $a['stock_minimum'] ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline" data-bs-toggle="modal" data-bs-target="#detailModal<?= $a['id'] ?>"><i class="bi bi-eye"></i></button>
                                <a href="modifier_article.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="supprimer_article.php" class="d-inline delete-form">
                                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline text-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>

                        <!-- Modal Détail -->
                        <div class="modal fade" id="detailModal<?= $a['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Détail article #<?= $a['id'] ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Nom :</strong> <?= htmlspecialchars($a['nom']) ?></p>
                                        <p><strong>Catégorie :</strong> <?= htmlspecialchars($a['categorie'] ?? '-') ?></p>
                                        <p><strong>Fournisseur :</strong> <?= htmlspecialchars($a['fournisseur_nom'] ?? '-') ?></p>
                                        <p><strong>Prix achat :</strong> <?= fcfa($a['prix_achat']) ?></p>
                                        <p><strong>Prix vente :</strong> <?= fcfa($a['prix_vente']) ?></p>
                                        <p><strong>Stock :</strong> <?= $a['quantite'] ?> (min: <?= $a['stock_minimum'] ?>)</p>
                                        <p><strong>Description :</strong> <?= nl2br(htmlspecialchars($a['description'] ?? '')) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&categorie=<?= urlencode($_GET['categorie'] ?? '') ?>&fournisseur=<?= urlencode($_GET['fournisseur'] ?? '') ?>"><?= $i ?></a>
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