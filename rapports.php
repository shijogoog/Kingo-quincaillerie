<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(['administrateur', 'gestionnaire']);

// Gestion des filtres de dates
$periode = $_GET['periode'] ?? 'mois';
$date_debut = '';
$date_fin = '';

switch ($periode) {
    case 'mois':
        $date_debut = date('Y-m-01');
        $date_fin = date('Y-m-t');
        break;
    case '3mois':
        $date_debut = date('Y-m-d', strtotime('-3 months'));
        $date_fin = date('Y-m-d');
        break;
    case '6mois':
        $date_debut = date('Y-m-d', strtotime('-6 months'));
        $date_fin = date('Y-m-d');
        break;
    case 'annee':
        $date_debut = date('Y-01-01');
        $date_fin = date('Y-12-31');
        break;
    case 'custom':
        $date_debut = $_GET['date_debut'] ?? date('Y-m-01');
        $date_fin = $_GET['date_fin'] ?? date('Y-m-d');
        break;
    default:
        $date_debut = date('Y-m-01');
        $date_fin = date('Y-m-t');
}

// Requêtes avec dates
$stmt = mysqli_prepare($conn, "
    SELECT 
        COUNT(*) as total_ventes,
        SUM(montant_total) as ca_total,
        AVG(montant_total) as panier_moyen
    FROM ventes 
    WHERE statut='validée' AND DATE(date_vente) BETWEEN ? AND ?
");
mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
mysqli_stmt_execute($stmt);
$resume = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Meilleur jour (date avec le plus de ventes)
$stmt = mysqli_prepare($conn, "
    SELECT DATE(date_vente) as jour, COUNT(*) as nb
    FROM ventes
    WHERE statut='validée' AND DATE(date_vente) BETWEEN ? AND ?
    GROUP BY DATE(date_vente)
    ORDER BY nb DESC
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
mysqli_stmt_execute($stmt);
$meilleurJour = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Évolution journalière pour graphique
$stmt = mysqli_prepare($conn, "
    SELECT DATE(date_vente) as jour, COUNT(*) as nb_ventes, SUM(montant_total) as ca
    FROM ventes
    WHERE statut='validée' AND DATE(date_vente) BETWEEN ? AND ?
    GROUP BY DATE(date_vente)
    ORDER BY jour
");
mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$evolution = [];
while ($row = mysqli_fetch_assoc($result)) {
    $evolution[] = $row;
}

// Top 10 articles
$stmt = mysqli_prepare($conn, "
    SELECT a.nom, SUM(dv.quantite) as total_vendu, SUM(dv.sous_total) as ca
    FROM details_vente dv
    JOIN articles a ON a.id = dv.article_id
    JOIN ventes v ON v.id = dv.vente_id
    WHERE v.statut='validée' AND DATE(v.date_vente) BETWEEN ? AND ?
    GROUP BY dv.article_id
    ORDER BY total_vendu DESC
    LIMIT 10
");
mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
mysqli_stmt_execute($stmt);
$topArticles = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

// Ventes par catégorie
$stmt = mysqli_prepare($conn, "
    SELECT a.categorie, SUM(dv.quantite) as total_vendu
    FROM details_vente dv
    JOIN articles a ON a.id = dv.article_id
    JOIN ventes v ON v.id = dv.vente_id
    WHERE v.statut='validée' AND DATE(v.date_vente) BETWEEN ? AND ? AND a.categorie IS NOT NULL
    GROUP BY a.categorie
    ORDER BY total_vendu DESC
");
mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
mysqli_stmt_execute($stmt);
$catVentes = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

// Ventes mensuelles (agrégées)
$stmt = mysqli_prepare($conn, "
    SELECT DATE_FORMAT(date_vente, '%Y-%m') as mois, COUNT(*) as nb_ventes, SUM(montant_total) as ca
    FROM ventes
    WHERE statut='validée' AND DATE(date_vente) BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(date_vente, '%Y-%m')
    ORDER BY mois
");
mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
mysqli_stmt_execute($stmt);
$mensuel = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapports - QuincaStore</title>
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
            <h1 class="h3 mb-4">Rapports analytiques</h1>

            <!-- Filtres -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Période</label>
                            <select name="periode" class="form-select" onchange="toggleCustomDates()">
                                <option value="mois" <?= $periode=='mois'?'selected':'' ?>>Ce mois</option>
                                <option value="3mois" <?= $periode=='3mois'?'selected':'' ?>>3 mois</option>
                                <option value="6mois" <?= $periode=='6mois'?'selected':'' ?>>6 mois</option>
                                <option value="annee" <?= $periode=='annee'?'selected':'' ?>>Cette année</option>
                                <option value="custom" <?= $periode=='custom'?'selected':'' ?>>Personnalisée</option>
                            </select>
                        </div>
                        <div class="col-md-2 custom-date" style="display: <?= $periode=='custom'?'block':'none' ?>;">
                            <label class="form-label">Date début</label>
                            <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($date_debut) ?>">
                        </div>
                        <div class="col-md-2 custom-date" style="display: <?= $periode=='custom'?'block':'none' ?>;">
                            <label class="form-label">Date fin</label>
                            <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($date_fin) ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Générer</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cartes résumé -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon primary"><i class="bi bi-receipt"></i></div>
                        <div class="stat-details">
                            <div class="stat-label">Total ventes</div>
                            <div class="stat-value"><?= $resume['total_ventes'] ?? 0 ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon success"><i class="bi bi-currency-euro"></i></div>
                        <div class="stat-details">
                            <div class="stat-label">CA total</div>
                            <div class="stat-value"><?= fcfa($resume['ca_total'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon accent"><i class="bi bi-cart3"></i></div>
                        <div class="stat-details">
                            <div class="stat-label">Panier moyen</div>
                            <div class="stat-value"><?= fcfa($resume['panier_moyen'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon accent"><i class="bi bi-calendar"></i></div>
                        <div class="stat-details">
                            <div class="stat-label">Meilleur jour</div>
                            <div class="stat-value"><?= $meilleurJour ? date('d/m', strtotime($meilleurJour['jour'])) : '-' ?></div>
                            <div class="stat-change"><?= $meilleurJour['nb'] ?? '' ?> ventes</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Graphique évolution -->
            <div class="card mb-4">
                <div class="card-header">Évolution des ventes</div>
                <div class="card-body">
                    <canvas id="evolutionChart" height="300"></canvas>
                </div>
            </div>

            <!-- Top articles + catégories -->
            <div class="row g-4 mb-4">
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header">Top 10 articles vendus</div>
                        <div class="card-body">
                            <?php foreach ($topArticles as $a): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span><?= htmlspecialchars($a['nom']) ?></span>
                                    <span><?= $a['total_vendu'] ?> unités (<?= fcfa($a['ca']) ?>)</span>
                                </div>
                                <div class="progress" style="height:10px;">
                                    <div class="progress-bar bg-primary" style="width: <?= ($a['total_vendu'] / max($topArticles[0]['total_vendu'],1))*100 ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header">Ventes par catégorie</div>
                        <div class="card-body">
                            <canvas id="categorieChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau ventes mensuelles -->
            <div class="card">
                <div class="card-header">Ventes mensuelles</div>
                <div class="card-body p-0">
                    <table class="table">
                        <thead>
                            <tr><th>Mois</th><th>Nb ventes</th><th>CA</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mensuel as $m): ?>
                            <tr>
                                <td><?= $m['mois'] ?></td>
                                <td><?= $m['nb_ventes'] ?></td>
                                <td><?= fcfa($m['ca']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 no-print">
                <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Exporter PDF</button>
                <a href="exporter_rapports_csv.php?date_debut=<?= urlencode($date_debut) ?>&date_fin=<?= urlencode($date_fin) ?>" class="btn btn-outline"><i class="bi bi-filetype-csv"></i> Exporter CSV</a>
            </div>
        </div>
    </div>

    <script>
        function toggleCustomDates() {
            const periode = document.querySelector('select[name="periode"]').value;
            document.querySelectorAll('.custom-date').forEach(el => el.style.display = periode === 'custom' ? 'block' : 'none');
        }

        // Graphiques
        const evolutionCtx = document.getElementById('evolutionChart').getContext('2d');
        const jours = <?= json_encode(array_column($evolution, 'jour')) ?>;
        const nbVentes = <?= json_encode(array_column($evolution, 'nb_ventes')) ?>;
        const ca = <?= json_encode(array_column($evolution, 'ca')) ?>;

        new Chart(evolutionCtx, {
            type: 'line',
            data: {
                labels: jours,
                datasets: [
                    { label: 'Nb ventes', data: nbVentes, borderColor: '#1E4A6F', yAxisID: 'y' },
                    { label: 'CA (FCFA)', data: ca, borderColor: '#F4A261', yAxisID: 'y1' }
                ]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true }, y1: { beginAtZero: true, position: 'right' } }
            }
        });

        const catCtx = document.getElementById('categorieChart').getContext('2d');
        const catLabels = <?= json_encode(array_column($catVentes, 'categorie')) ?>;
        const catData = <?= json_encode(array_column($catVentes, 'total_vendu')) ?>;
        new Chart(catCtx, {
            type: 'pie',
            data: {
                labels: catLabels,
                datasets: [{ data: catData, backgroundColor: ['#1E4A6F','#F4A261','#2D3E50','#2E7D32','#D32F2F'] }]
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/script.js"></script>
</body>
</html>