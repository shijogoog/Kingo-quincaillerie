<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(['administrateur']); // Seul admin

// Actions POST (ajout, modification, suppression)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Erreur CSRF');
    }

    $action = $_POST['action'] ?? '';

    // Ajout
    if ($action === 'ajouter') {
        $nom = trim($_POST['nom'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');

        if (!empty($nom)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO fournisseurs (nom, telephone, email, adresse) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssss", $nom, $telephone, $email, $adresse);
            mysqli_stmt_execute($stmt);
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Fournisseur ajouté.'];
        }
    }

    // Modification
    elseif ($action === 'modifier') {
        $id = (int)$_POST['id'];
        $nom = trim($_POST['nom'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');

        if (!empty($nom)) {
            $stmt = mysqli_prepare($conn, "UPDATE fournisseurs SET nom=?, telephone=?, email=?, adresse=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssssi", $nom, $telephone, $email, $adresse, $id);
            mysqli_stmt_execute($stmt);
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Fournisseur modifié.'];
        }
    }

    // Suppression
    elseif ($action === 'supprimer') {
        $id = (int)$_POST['id'];
        // Vérifier nb articles liés
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as nb FROM articles WHERE fournisseur_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $nb = mysqli_fetch_assoc($result)['nb'];

        if ($nb == 0) {
            $stmt = mysqli_prepare($conn, "DELETE FROM fournisseurs WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Fournisseur supprimé.'];
        } else {
            $_SESSION['toast'] = ['type' => 'danger', 'msg' => 'Impossible de supprimer : des articles sont liés.'];
        }
    }

    header('Location: fournisseurs.php');
    exit;
}

// Récupérer la liste des fournisseurs avec nombre d'articles
$stmt = mysqli_prepare($conn, "
    SELECT f.*, COUNT(a.id) as nb_articles 
    FROM fournisseurs f
    LEFT JOIN articles a ON a.fournisseur_id = f.id
    GROUP BY f.id
    ORDER BY f.nom
");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$fournisseurs = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fournisseurs - QuincaStore</title>
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
                <h1 class="h3">Gestion des fournisseurs</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajoutModal"><i class="bi bi-plus"></i> Ajouter</button>
            </div>

            <!-- Tableau -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Téléphone</th>
                            <th>Email</th>
                            <th>Adresse</th>
                            <th>Nb articles</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fournisseurs as $f): ?>
                        <tr>
                            <td><?= htmlspecialchars($f['nom']) ?></td>
                            <td><?= htmlspecialchars($f['telephone'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($f['email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($f['adresse'] ?? '-') ?></td>
                            <td><?= $f['nb_articles'] ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline" data-bs-toggle="modal" data-bs-target="#editModal<?= $f['id'] ?>"><i class="bi bi-pencil"></i></button>
                                <?php if ($f['nb_articles'] == 0): ?>
                                <form method="POST" class="d-inline delete-form">
                                    <input type="hidden" name="action" value="supprimer">
                                    <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline text-danger"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php else: ?>
                                <button class="btn btn-sm btn-outline text-muted" disabled><i class="bi bi-trash"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Modal Édition -->
                        <div class="modal fade" id="editModal<?= $f['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <form method="POST" class="modal-content">
                                    <input type="hidden" name="action" value="modifier">
                                    <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Modifier fournisseur</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label class="form-label">Nom *</label>
                                            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($f['nom']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Téléphone</label>
                                            <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($f['telephone']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($f['email']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Adresse</label>
                                            <textarea name="adresse" class="form-control" rows="3"><?= htmlspecialchars($f['adresse']) ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Ajout -->
    <div class="modal fade" id="ajoutModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <input type="hidden" name="action" value="ajouter">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un fournisseur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="telephone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Adresse</label>
                        <textarea name="adresse" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/script.js"></script>
    <?php
    // Affichage toast si présent en session
    if (isset($_SESSION['toast'])) {
        $toast = $_SESSION['toast'];
        echo "<script>showToast('".addslashes($toast['msg'])."', '".$toast['type']."');</script>";
        unset($_SESSION['toast']);
    }
    ?>
</body>
</html>