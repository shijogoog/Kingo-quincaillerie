<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(['administrateur', 'gestionnaire']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ajout d'un rappel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) die('CSRF');
    
    $libelle = trim($_POST['libelle']);
    $date_echeance = $_POST['date_echeance'];
    $montant = !empty($_POST['montant']) ? (float)$_POST['montant'] : null;
    $description = trim($_POST['description']);
    
    $stmt = mysqli_prepare($conn, "INSERT INTO rappels (libelle, date_echeance, montant, description) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssds", $libelle, $date_echeance, $montant, $description);
    mysqli_stmt_execute($stmt);
    $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Rappel ajouté.'];
    header('Location: rappels.php');
    exit;
}

// Marquer comme effectué
if (isset($_GET['effectuer']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $id = (int)$_GET['effectuer'];
    $stmt = mysqli_prepare($conn, "UPDATE rappels SET effectue = 1 WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Rappel marqué comme effectué.'];
    header('Location: rappels.php');
    exit;
}

// Suppression
if (isset($_GET['supprimer']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $id = (int)$_GET['supprimer'];
    $stmt = mysqli_prepare($conn, "DELETE FROM rappels WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Rappel supprimé.'];
    header('Location: rappels.php');
    exit;
}

// Récupérer les rappels à venir et passés
$stmt = mysqli_prepare($conn, "SELECT * FROM rappels ORDER BY effectue ASC, date_echeance ASC");
mysqli_stmt_execute($stmt);
$rappels = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rappels de paiement - <?= APP_NAME ?></title>
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
                <h1 class="h3">Rappels de paiement</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajoutModal"><i class="bi bi-plus"></i> Ajouter un rappel</button>
            </div>

            <!-- Alerte si échéances proches -->
            <?php
            $proches = array_filter($rappels, fn($r) => !$r['effectue'] && strtotime($r['date_echeance']) <= strtotime('+7 days'));
            if (count($proches) > 0): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong><?= count($proches) ?> rappel(s) à venir dans les 7 jours :</strong>
                <?php foreach ($proches as $p): ?>
                    <div>- <?= htmlspecialchars($p['libelle']) ?> le <?= date('d/m/Y', strtotime($p['date_echeance'])) ?> (<?= fcfa($p['montant']) ?>)</div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body p-0">
                    <table class="table">
                        <thead>
                            <tr><th>Libellé</th><th>Échéance</th><th>Montant</th><th>Description</th><th>Statut</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rappels as $r): ?>
                            <tr class="<?= !$r['effectue'] && strtotime($r['date_echeance']) < time() ? 'table-danger' : ($r['effectue'] ? 'table-secondary' : '') ?>">
                                <td><?= htmlspecialchars($r['libelle']) ?></td>
                                <td><?= date('d/m/Y', strtotime($r['date_echeance'])) ?>
                                    <?php if (!$r['effectue'] && strtotime($r['date_echeance']) < time()): ?> <span class="badge bg-danger">En retard</span><?php endif; ?>
                                </td>
                                <td><?= $r['montant'] ? fcfa($r['montant']) : '-' ?></td>
                                <td><?= nl2br(htmlspecialchars($r['description'])) ?></td>
                                <td><?= $r['effectue'] ? '<span class="badge bg-success">Effectué</span>' : '<span class="badge bg-warning">En attente</span>' ?></td>
                                <td>
                                    <?php if (!$r['effectue']): ?>
                                    <a href="?effectuer=<?= $r['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Marquer comme payé ?')"><i class="bi bi-check"></i></a>
                                    <?php endif; ?>
                                    <a href="?supprimer=<?= $r['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-sm btn-outline text-danger" onclick="return confirm('Supprimer ce rappel ?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajout -->
    <div class="modal fade" id="ajoutModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <input type="hidden" name="action" value="ajouter">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-header"><h5 class="modal-title">Nouveau rappel</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="form-group mb-2"><label>Libellé *</label><input type="text" name="libelle" class="form-control" required></div>
                    <div class="form-group mb-2"><label>Date d'échéance *</label><input type="date" name="date_echeance" class="form-control" required></div>
                    <div class="form-group mb-2"><label>Montant (FCFA)</label><input type="number" name="montant" class="form-control" step="1"></div>
                    <div class="form-group mb-2"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline" data-bs-dismiss="modal">Annuler</button><button type="submit" class="btn btn-primary">Ajouter</button></div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/script.js"></script>
    <?php if (isset($_SESSION['toast'])): ?>
    <script>showToast('<?= addslashes($_SESSION['toast']['msg']) ?>', '<?= $_SESSION['toast']['type'] ?>');</script>
    <?php unset($_SESSION['toast']); endif; ?>
</body>
</html>