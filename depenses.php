<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(['administrateur', 'gestionnaire']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ajout / modification / suppression d'une dépense périodique
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Erreur CSRF');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter') {
        $libelle = trim($_POST['libelle']);
        $categorie = trim($_POST['categorie']);
        $montant = (float)$_POST['montant'];
        $frequence = $_POST['frequence'];
        $actif = isset($_POST['actif']) ? 1 : 0;
        $stmt = mysqli_prepare($conn, "INSERT INTO depenses_periodiques (libelle, categorie, montant, frequence, actif) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssdsi", $libelle, $categorie, $montant, $frequence, $actif);
        mysqli_stmt_execute($stmt);
        $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Dépense périodique ajoutée.'];
    } elseif ($action === 'modifier') {
        $id = (int)$_POST['id'];
        $libelle = trim($_POST['libelle']);
        $categorie = trim($_POST['categorie']);
        $montant = (float)$_POST['montant'];
        $frequence = $_POST['frequence'];
        $actif = isset($_POST['actif']) ? 1 : 0;
        $stmt = mysqli_prepare($conn, "UPDATE depenses_periodiques SET libelle=?, categorie=?, montant=?, frequence=?, actif=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssdsii", $libelle, $categorie, $montant, $frequence, $actif, $id);
        mysqli_stmt_execute($stmt);
        $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Dépense périodique modifiée.'];
    } elseif ($action === 'supprimer') {
        $id = (int)$_POST['id'];
        $stmt = mysqli_prepare($conn, "DELETE FROM depenses_periodiques WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Dépense périodique supprimée.'];
    }
    header('Location: depenses.php');
    exit;
}

// Récupération des dépenses périodiques
$stmt = mysqli_prepare($conn, "SELECT * FROM depenses_periodiques ORDER BY categorie, libelle");
mysqli_stmt_execute($stmt);
$depenses = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dépenses périodiques - <?= APP_NAME ?></title>
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
                <h1 class="h3">Dépenses périodiques (loyer, salaires, taxes...)</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajoutModal"><i class="bi bi-plus"></i> Ajouter</button>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <table class="table">
                        <thead>
                            <tr><th>Libellé</th><th>Catégorie</th><th class="text-end">Montant</th><th>Fréquence</th><th>Actif</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($depenses as $d): ?>
                            <tr>
                                <td><?= htmlspecialchars($d['libelle']) ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', $d['categorie'])) ?></td>
                                <td class="text-end"><?= fcfa($d['montant']) ?></td>
                                <td><?= ucfirst($d['frequence']) ?></td>
                                <td><?= $d['actif'] ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline" data-bs-toggle="modal" data-bs-target="#editModal<?= $d['id'] ?>"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" class="d-inline delete-form">
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline text-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Modal modification -->
                            <div class="modal fade" id="editModal<?= $d['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <form method="POST" class="modal-content">
                                        <input type="hidden" name="action" value="modifier">
                                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <div class="modal-header"><h5 class="modal-title">Modifier dépense</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                        <div class="modal-body">
                                            <div class="form-group mb-2"><label>Libellé</label><input type="text" name="libelle" class="form-control" value="<?= htmlspecialchars($d['libelle']) ?>" required></div>
                                            <div class="form-group mb-2"><label>Catégorie</label>
                                                <select name="categorie" class="form-select" required>
                                                    <option value="loyer" <?= $d['categorie']=='loyer'?'selected':'' ?>>Loyer</option>
                                                    <option value="salaires" <?= $d['categorie']=='salaires'?'selected':'' ?>>Salaires</option>
                                                    <option value="services" <?= $d['categorie']=='services'?'selected':'' ?>>Services (eau, élec, internet)</option>
                                                    <option value="impots_taxes" <?= $d['categorie']=='impots_taxes'?'selected':'' ?>>Impôts et taxes</option>
                                                    <option value="divers" <?= $d['categorie']=='divers'?'selected':'' ?>>Divers</option>
                                                </select>
                                            </div>
                                            <div class="form-group mb-2"><label>Montant (FCFA)</label><input type="number" name="montant" class="form-control" value="<?= $d['montant'] ?>" required></div>
                                            <div class="form-group mb-2"><label>Fréquence</label>
                                                <select name="frequence" class="form-select">
                                                    <option value="mensuel" <?= $d['frequence']=='mensuel'?'selected':'' ?>>Mensuel</option>
                                                    <option value="trimestriel" <?= $d['frequence']=='trimestriel'?'selected':'' ?>>Trimestriel</option>
                                                    <option value="annuel" <?= $d['frequence']=='annuel'?'selected':'' ?>>Annuel</option>
                                                </select>
                                            </div>
                                            <div class="form-check"><input class="form-check-input" type="checkbox" name="actif" id="actif<?= $d['id'] ?>" <?= $d['actif'] ? 'checked' : '' ?>><label class="form-check-label">Actif</label></div>
                                        </div>
                                        <div class="modal-footer"><button type="button" class="btn btn-outline" data-bs-dismiss="modal">Annuler</button><button type="submit" class="btn btn-primary">Enregistrer</button></div>
                                    </form>
                                </div>
                            </div>
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
                <div class="modal-header"><h5 class="modal-title">Ajouter une dépense périodique</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="form-group mb-2"><label>Libellé</label><input type="text" name="libelle" class="form-control" placeholder="Ex: Loyer boutique" required></div>
                    <div class="form-group mb-2"><label>Catégorie</label>
                        <select name="categorie" class="form-select" required>
                            <option value="loyer">Loyer</option>
                            <option value="salaires">Salaires</option>
                            <option value="services">Services (eau, élec, internet)</option>
                            <option value="impots_taxes">Impôts et taxes</option>
                            <option value="divers">Divers</option>
                        </select>
                    </div>
                    <div class="form-group mb-2"><label>Montant (FCFA)</label><input type="number" name="montant" class="form-control" required></div>
                    <div class="form-group mb-2"><label>Fréquence</label>
                        <select name="frequence" class="form-select">
                            <option value="mensuel">Mensuel</option>
                            <option value="trimestriel">Trimestriel</option>
                            <option value="annuel">Annuel</option>
                        </select>
                    </div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="actif" checked><label class="form-check-label">Actif</label></div>
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