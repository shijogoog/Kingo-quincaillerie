<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(); // Tous les rôles

// Rupture stock
$stmt = mysqli_prepare($conn, "SELECT * FROM articles WHERE quantite = 0 ORDER BY nom");
mysqli_stmt_execute($stmt);
$rupture = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

// Critique (quantite < stock_minimum/2 et >0)
$stmt = mysqli_prepare($conn, "SELECT * FROM articles WHERE quantite > 0 AND quantite < stock_minimum/2 ORDER BY quantite ASC");
mysqli_stmt_execute($stmt);
$critique = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

// Faible (quantite <= stock_minimum et quantite > 0, mais non critique)
$stmt = mysqli_prepare($conn, "SELECT * FROM articles WHERE quantite > 0 AND quantite <= stock_minimum AND quantite >= stock_minimum/2 ORDER BY quantite ASC");
mysqli_stmt_execute($stmt);
$faible = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

// Compteurs
$nbRupture = count($rupture);
$nbCritique = count($critique);
$nbFaible = count($faible);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Alertes stock - QuincaStore</title>
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
                <h1 class="h3">Centre d'alertes</h1>
                <a href="alertes.php" class="btn btn-primary"><i class="bi bi-arrow-repeat"></i> Actualiser</a>
            </div>

            <!-- Badges compteurs -->
            <div class="d-flex gap-3 mb-4">
                <span class="badge bg-danger" style="font-size:1rem;">Rupture: <?= $nbRupture ?></span>
                <span class="badge bg-warning" style="font-size:1rem;">Critique: <?= $nbCritique ?></span>
                <span class="badge bg-info" style="font-size:1rem;">Faible: <?= $nbFaible ?></span>
            </div>

            <!-- Section Rupture -->
            <?php if ($nbRupture > 0): ?>
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">Rupture de stock</div>
                <div class="card-body">
                    <table class="table">
                        <thead><tr><th>Article</th><th>Stock</th><th>Seuil</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($rupture as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['nom']) ?></td>
                                <td><?= $a['quantite'] ?></td>
                                <td><?= $a['stock_minimum'] ?></td>
                                <td><a href="stock.php?reappro=<?= $a['id'] ?>" class="btn btn-sm btn-primary">Réapprovisionner</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Section Critique -->
            <?php if ($nbCritique > 0): ?>
            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning">Stock critique</div>
                <div class="card-body">
                    <table class="table">
                        <thead><tr><th>Article</th><th>Stock</th><th>Seuil</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($critique as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['nom']) ?></td>
                                <td><?= $a['quantite'] ?></td>
                                <td><?= $a['stock_minimum'] ?></td>
                                <td><a href="stock.php?reappro=<?= $a['id'] ?>" class="btn btn-sm btn-primary">Réapprovisionner</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Section Faible -->
            <?php if ($nbFaible > 0): ?>
            <div class="card mb-4 border-info">
                <div class="card-header bg-info text-white">Stock faible</div>
                <div class="card-body">
                    <table class="table">
                        <thead><tr><th>Article</th><th>Stock</th><th>Seuil</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($faible as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['nom']) ?></td>
                                <td><?= $a['quantite'] ?></td>
                                <td><?= $a['stock_minimum'] ?></td>
                                <td><a href="stock.php?reappro=<?= $a['id'] ?>" class="btn btn-sm btn-primary">Réapprovisionner</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($nbRupture+$nbCritique+$nbFaible == 0): ?>
            <div class="alert alert-success">Aucune alerte stock pour le moment.</div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/script.js"></script>
</body>
</html>