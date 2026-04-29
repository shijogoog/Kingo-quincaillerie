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
    header('Location: ventes.php');
    exit;
}

// CORRECTION : calcul sous_total directement dans la requête
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
    <title>Facture #<?= $id ?> - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        @media print {
            .sidebar, .header, .btn, .no-print { display: none !important; }
            .main-content { margin-left: 0; }
            .page-content { padding: 0; }
            .card { border: none; box-shadow: none; }
        }
        .facture-box {
            max-width: 800px; margin: 0 auto; background: white;
            padding: 2rem; border-radius: 12px; box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        <div class="page-content">
            <div class="facture-box">
                <!-- En-tête facture avec coordonnées africaines -->
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h1 class="h3"><?= APP_NAME ?></h1>
                        <p class="mb-0">Quartier Commerce, Rue des Artisans<br>
                           Lomé, Togo<br>
                           Tél : +228 90 00 00 00<br>
                           contact@quincastore.tg</p>
                    </div>
                    <div class="text-end">
                        <h2>FACTURE N° <?= str_pad($id, 6, '0', STR_PAD_LEFT) ?></h2>
                        <p>Date : <?= date('d/m/Y', strtotime($vente['date_vente'])) ?></p>
                        <span class="badge badge-<?= $vente['statut'] === 'validée' ? 'success' : 'danger' ?>">
                            <?= $vente['statut'] ?>
                        </span>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-6">
                        <strong>Client :</strong> <?= htmlspecialchars($vente['client_nom'] ?: 'Client anonyme') ?>
                    </div>
                    <div class="col-6 text-end">
                        <strong>Vendeur :</strong> <?= htmlspecialchars($vente['vendeur_nom']) ?>
                    </div>
                </div>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Désignation</th>
                            <th class="text-center">Qté</th>
                            <th class="text-end">Prix unitaire</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($details as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['nom']) ?></td>
                            <td class="text-center"><?= $d['quantite'] ?></td>
                            <td class="text-end"><?= fcfa($d['prix_unitaire']) ?></td>
                            <!-- CORRECTION : utilisation de sous_total_calc -->
                            <td class="text-end"><?= fcfa($d['sous_total_calc']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Montant HT</th>
                            <th class="text-end"><?= fcfa($vente['montant_total']) ?></th>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-end">TVA (18%)</th>
                            <th class="text-end"><?= fcfa($vente['montant_total'] * 0.18) ?></th>
                        </tr>
                        <tr class="table-primary">
                            <th colspan="3" class="text-end">Total TTC</th>
                            <th class="text-end h5"><?= fcfa($vente['montant_total'] * 1.18) ?></th>
                        </tr>
                    </tfoot>
                </table>

                <p class="text-muted mt-4">
                    <small>Merci pour votre confiance. Règlement à réception de facture.</small>
                </p>

                <div class="text-center mt-4 no-print">
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="bi bi-printer"></i> Imprimer
                    </button>
                    <a href="details_vente.php?id=<?= $id ?>" class="btn btn-outline">Retour</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/script.js"></script>
</body>
</html>
