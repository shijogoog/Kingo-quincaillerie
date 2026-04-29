<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(['administrateur', 'gestionnaire']);

$mois = isset($_GET['mois']) ? (int)$_GET['mois'] : date('m');
$annee = isset($_GET['annee']) ? (int)$_GET['annee'] : date('Y');
$date_debut = "$annee-$mois-01";
$date_fin = date('Y-m-t', strtotime($date_debut));

// 1. Chiffre d'affaires (ventes validées du mois)
$stmt = mysqli_prepare($conn, "SELECT SUM(montant_total) as ca FROM ventes WHERE statut='validée' AND DATE(date_vente) BETWEEN ? AND ?");
mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
mysqli_stmt_execute($stmt);
$ca = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['ca'] ?? 0;

// 2. Coût des marchandises vendues (somme des prix d'achat * quantités vendues)
$stmt = mysqli_prepare($conn, "
    SELECT SUM(a.prix_achat * dv.quantite) as cmv
    FROM details_vente dv
    JOIN articles a ON a.id = dv.article_id
    JOIN ventes v ON v.id = dv.vente_id
    WHERE v.statut='validée' AND DATE(v.date_vente) BETWEEN ? AND ?
");
mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
mysqli_stmt_execute($stmt);
$cmv = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cmv'] ?? 0;

$marge_brute = $ca - $cmv;
$taux_marge = $ca > 0 ? ($marge_brute / $ca) * 100 : 0;

// 3. Dépenses du mois (hors achats fournisseurs, déjà dans CMV)
$stmt = mysqli_prepare($conn, "SELECT SUM(montant) as total FROM transactions WHERE type='depense' AND DATE(date_transaction) BETWEEN ? AND ?");
mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
mysqli_stmt_execute($stmt);
$depenses = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0;

// 4. Impôts et taxes (dans dépenses, mais on extrait le montant pour affichage)
$stmt = mysqli_prepare($conn, "SELECT SUM(montant) as total FROM transactions WHERE type='depense' AND categorie='impots_taxes' AND DATE(date_transaction) BETWEEN ? AND ?");
mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
mysqli_stmt_execute($stmt);
$impots = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0;

$resultat_exploitation = $marge_brute - $depenses;
$benefice_net = $resultat_exploitation; // après impôts déjà inclus dans dépenses

// Dépenses par catégorie pour graphique
$stmt = mysqli_prepare($conn, "
    SELECT categorie, SUM(montant) as total
    FROM transactions
    WHERE type='depense' AND DATE(date_transaction) BETWEEN ? AND ?
    GROUP BY categorie
    ORDER BY total DESC
");
mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
mysqli_stmt_execute($stmt);
$depenses_par_cat = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Compte de résultat - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        <div class="page-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Compte de résultat</h1>
                <div>
                    <a href="tresorerie.php" class="btn btn-outline"><i class="bi bi-cash-stack"></i> Trésorerie</a>
                </div>
            </div>

            <!-- Filtre mois -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3"><label class="form-label">Mois</label><select name="mois" class="form-select"><?php for ($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $mois==$m?'selected':'' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option><?php endfor; ?></select></div>
                        <div class="col-md-3"><label class="form-label">Année</label><select name="annee" class="form-select"><?php $y=date('Y'); for($y=$y-2;$y<=$y+1;$y++): ?><option <?= $annee==$y?'selected':'' ?>><?= $y ?></option><?php endfor; ?></select></div>
                        <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-primary">Calculer</button></div>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">Compte de résultat - <?= date('F Y', strtotime($date_debut)) ?></div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr><td class="fw-bold">Chiffre d'affaires (ventes)</td><td class="text-end"><?= fcfa($ca) ?></td></tr>
                                <tr><td class="fw-bold">– Coût des marchandises vendues (achats)</td><td class="text-end text-danger">- <?= fcfa($cmv) ?></td></tr>
                                <tr class="table-primary"><td class="fw-bold">= Marge brute</td><td class="text-end fw-bold"><?= fcfa($marge_brute) ?> (<?= round($taux_marge, 1) ?>%)</td></tr>
                                <tr><td colspan="2"><hr></td></tr>
                                <tr><td class="fw-bold">– Dépenses d'exploitation</td><td class="text-end text-danger">- <?= fcfa($depenses) ?></td></tr>
                                <?php foreach ($depenses_par_cat as $d): ?>
                                <tr><td style="padding-left: 2rem;"><?= ucfirst(str_replace('_', ' ', $d['categorie'])) ?></td><td class="text-end"><?= fcfa($d['total']) ?></td></tr>
                                <?php endforeach; ?>
                                <tr><td colspan="2"><hr></td></tr>
                                <tr class="table-success"><td class="fw-bold">= Résultat d'exploitation</td><td class="text-end fw-bold"><?= fcfa($resultat_exploitation) ?></td></tr>
                                <tr><td class="fw-bold">– Impôts et taxes (déjà inclus ci-dessus)</td><td class="text-end"><?= fcfa($impots) ?></td></tr>
                                <tr class="table-info"><td class="fw-bold">= Bénéfice net</td><td class="text-end fw-bold"><?= fcfa($benefice_net) ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">Répartition des dépenses</div>
                        <div class="card-body">
                            <canvas id="depensesChart" height="300"></canvas>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-header">Indicateurs de performance</div>
                        <div class="card-body">
                            <p><strong>Taux de marge brute :</strong> <?= round($taux_marge, 1) ?>%</p>
                            <p><strong>Ratio charges / CA :</strong> <?= $ca > 0 ? round(($depenses/$ca)*100, 1) : 0 ?>%</p>
                            <p><strong>Bénéfice net / CA :</strong> <?= $ca > 0 ? round(($benefice_net/$ca)*100, 1) : 0 ?>%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        const ctx = document.getElementById('depensesChart').getContext('2d');
        const catLabels = <?= json_encode(array_column($depenses_par_cat, 'categorie')) ?>;
        const catValues = <?= json_encode(array_column($depenses_par_cat, 'total')) ?>;
        new Chart(ctx, { type: 'pie', data: { labels: catLabels, datasets: [{ data: catValues, backgroundColor: ['#1E4A6F','#F4A261','#2D3E50','#2E7D32','#D32F2F'] }] } });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/script.js"></script>
</body>
</html>