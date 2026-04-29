<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(['administrateur', 'gestionnaire']);

// CORRECTION : génération du token CSRF si absent
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reappro'])) {
    // CORRECTION : vérification CSRF manquante dans l'original
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Erreur CSRF');
    }

    $id  = (int)$_POST['id'];
    $qte = (int)$_POST['quantite'];
    if ($qte > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE articles SET quantite = quantite + ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $qte, $id);
        mysqli_stmt_execute($stmt);
        $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Stock mis à jour avec succès.'];
    } else {
        $_SESSION['toast'] = ['type' => 'danger', 'msg' => 'La quantité doit être supérieure à 0.'];
    }
    header('Location: stock.php');
    exit;
}

$stmt = mysqli_prepare($conn, "
    SELECT a.*, f.nom as fournisseur_nom,
           (a.prix_vente * a.quantite) as valeur_stock
    FROM articles a
    LEFT JOIN fournisseurs f ON f.id = a.fournisseur_id
    ORDER BY a.quantite <= a.stock_minimum DESC, a.quantite ASC
");
mysqli_stmt_execute($stmt);
$result   = mysqli_stmt_get_result($stmt);
$articles = mysqli_fetch_all($result, MYSQLI_ASSOC);

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM articles");
mysqli_stmt_execute($stmt);
$totalArticles = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];

$stmt = mysqli_prepare($conn, "SELECT SUM(prix_vente * quantite) as valeur FROM articles");
mysqli_stmt_execute($stmt);
$valeurStock = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['valeur'] ?? 0;

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM articles WHERE quantite <= stock_minimum");
mysqli_stmt_execute($stmt);
$alertes = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion du stock - <?= APP_NAME ?></title>
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
            <h1 class="h3 mb-4">Gestion du stock</h1>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon primary"><i class="bi bi-box"></i></div>
                        <div class="stat-details">
                            <div class="stat-label">Total articles</div>
                            <div class="stat-value"><?= $totalArticles ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon success"><i class="bi bi-coin"></i></div>
                        <div class="stat-details">
                            <div class="stat-label">Valeur stock</div>
                            <div class="stat-value"><?= fcfa($valeurStock) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon danger"><i class="bi bi-exclamation-triangle"></i></div>
                        <div class="stat-details">
                            <div class="stat-label">Alertes</div>
                            <div class="stat-value"><?= $alertes ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Article</th><th>Catégorie</th><th>Fournisseur</th>
                            <th>Stock</th><th>Seuil</th><th>Valeur</th>
                            <th>État</th><th>Réappro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $a):
                            if ($a['quantite'] == 0) {
                                $etat = 'Rupture'; $badge = 'badge-danger';
                            } elseif ($a['quantite'] < $a['stock_minimum'] / 2) {
                                $etat = 'Critique'; $badge = 'badge-danger';
                            } elseif ($a['quantite'] < $a['stock_minimum']) {
                                $etat = 'Faible'; $badge = 'badge-warning';
                            } else {
                                $etat = 'OK'; $badge = 'badge-success';
                            }
                            $pourcent = $a['stock_minimum'] > 0 ? min(100, ($a['quantite'] / $a['stock_minimum']) * 100) : 100;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($a['nom']) ?></td>
                            <td><?= htmlspecialchars($a['categorie'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($a['fournisseur_nom'] ?? '-') ?></td>
                            <td><?= $a['quantite'] ?></td>
                            <td><?= $a['stock_minimum'] ?></td>
                            <td><?= fcfa($a['valeur_stock']) ?></td>
                            <td>
                                <span class="badge <?= $badge ?>"><?= $etat ?></span>
                                <div class="progress mt-1" style="height:5px;">
                                    <div class="progress-bar <?= $pourcent < 50 ? 'bg-danger' : ($pourcent < 100 ? 'bg-warning' : 'bg-success') ?>"
                                         style="width:<?= $pourcent ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <!-- CORRECTION : ajout du token CSRF dans le formulaire de réapprovisionnement -->
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="number" name="quantite" class="form-control form-control-sm"
                                           style="width:80px" min="1" placeholder="Qté" required>
                                    <button type="submit" name="reappro" class="btn btn-sm btn-primary">Ajouter</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/script.js"></script>
    <?php if (isset($_SESSION['toast'])): ?>
    <script>showToast('<?= addslashes($_SESSION['toast']['msg']) ?>', '<?= $_SESSION['toast']['type'] ?>');</script>
    <?php unset($_SESSION['toast']); endif; ?>
</body>
</html>
