<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = mysqli_prepare($conn, "SELECT v.*, u.nom as vendeur_nom FROM ventes v JOIN users u ON u.id=v.utilisateur_id WHERE v.id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$vente  = mysqli_fetch_assoc($result);

if (!$vente) {
    header("HTTP/1.0 404 Not Found");
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head><title>Vente introuvable</title><link href="assets/style.css" rel="stylesheet"></head>
    <body>
        <div class="container mt-5 text-center">
            <h2>Vente introuvable</h2>
            <p>La vente demandée n'existe pas.</p>
            <a href="ventes.php" class="btn btn-primary">Retour aux ventes</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if ($_SESSION['role'] === 'vendeur' && $vente['utilisateur_id'] != $_SESSION['user_id']) {
    header('Location: ventes.php?error=acces_refuse');
    exit;
}

// CORRECTION : calcul sous_total = quantite * prix_unitaire (évite dépendance colonne sous_total)
$stmt = mysqli_prepare($conn, "SELECT dv.*, a.nom, (dv.quantite * dv.prix_unitaire) as sous_total_calc FROM details_vente dv JOIN articles a ON a.id=dv.article_id WHERE dv.vente_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result  = mysqli_stmt_get_result($stmt);
$details = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail vente #<?= $id ?> - <?= APP_NAME ?></title>
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
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Détail de la vente #<?= $id ?></h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($vente['date_vente'])) ?></p>
                            <p><strong>Client :</strong> <?= htmlspecialchars($vente['client_nom'] ?: 'Client anonyme') ?></p>
                            <p><strong>Vendeur :</strong> <?= htmlspecialchars($vente['vendeur_nom']) ?></p>
                            <p><strong>Statut :</strong>
                                <span class="badge badge-<?= $vente['statut'] === 'validée' ? 'success' : 'danger' ?>">
                                    <?= $vente['statut'] ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">Articles vendus</div>
                        <div class="card-body p-0">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Article</th><th>Quantité</th>
                                        <th>Prix unitaire</th><th>Sous-total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($details as $d): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($d['nom']) ?></td>
                                        <td><?= $d['quantite'] ?></td>
                                        <td><?= fcfa($d['prix_unitaire']) ?></td>
                                        <!-- CORRECTION : utilisation de sous_total_calc calculé dans la requête -->
                                        <td><?= fcfa($d['sous_total_calc']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Total</th>
                                        <th><?= fcfa($vente['montant_total']) ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">Actions</div>
                        <div class="card-body">
                            <a href="facture.php?id=<?= $id ?>" class="btn btn-primary w-100 mb-2">
                                <i class="bi bi-printer"></i> Imprimer la facture
                            </a>
                            <?php if ($_SESSION['role'] === 'administrateur' && $vente['statut'] === 'validée'): ?>
                                <form method="POST" action="annuler_vente.php" class="delete-form">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="bi bi-x"></i> Annuler la vente
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a href="ventes.php" class="btn btn-outline w-100 mt-2">
                                <i class="bi bi-arrow-left"></i> Retour aux ventes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/script.js"></script>
</body>
</html>
